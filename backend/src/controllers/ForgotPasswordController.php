<?php

namespace Controllers;

use Config\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;

class ForgotPasswordController {

    // PASSO A: Solicitar a recuperação (Gera Token e Envia E-mail)
    public function requestReset() {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'E-mail inválido.']);
            return;
        }

        $db = Database::getConnection();

        // 1. Verifica se o e-mail existe no banco
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Por segurança, você pode retornar mensagem de sucesso mesmo se o e-mail não existir (evita enumeração de usuários)
        if (!$user) {
            http_response_code(200);
            echo json_encode(['message' => 'Se o e-mail estiver cadastrado, você receberá as instruções.']);
            return;
        }

        // 2. Gera token seguro de 64 caracteres hexadecimais
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // 3. Salva o token no banco
        $stmtReset = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmtReset->execute([$email, $token, $expiresAt]);

        // 4. Envia o e-mail com o link de recuperação
        $link = "https://seusite.com/reset-password?token=" . $token; // Ou http://localhost:3000 em dev

        $this->sendResetEmail($email, $user['name'], $link);

        http_response_code(200);
        echo json_encode(['message' => 'Instruções enviadas para o seu e-mail!']);
    }

    // PASSO B: Efetuar a troca de senha
    public function resetPassword() {
        $data = json_decode(file_get_contents('php://input'), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos ou senha muito curta (mínimo 6 caracteres).']);
            return;
        }

        $db = Database::getConnection();

        // 1. Valida se o token existe e NÃO expirou
        $stmt = $db->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resetRequest) {
            http_response_code(400);
            echo json_encode(['error' => 'Link inválido ou expirado. Solicite uma nova recuperação.']);
            return;
        }

        $email = $resetRequest['email'];

        // 2. Criptografa a nova senha com BCRYPT
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $db->beginTransaction();
        try {
            // Atualiza a senha do usuário
            $stmtUpdate = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmtUpdate->execute([$hashedPassword, $email]);

            // Limpa todos os tokens desse e-mail para não serem reutilizados
            $stmtDelete = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmtDelete->execute([$email]);

            $db->commit();

            http_response_code(200);
            echo json_encode(['message' => 'Senha alterada com sucesso! Você já pode fazer login.']);
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar a senha.']);
        }
    }

    // Função interna que usa a mesma estrutura PHPMailer do seu VotoController
    private function sendResetEmail($toEmail, $toName, $resetLink) {
        $mail = new PHPMailer(true);

        try {
            $host     = getenv('SMTP_HOST')     ?: ($_ENV['SMTP_HOST']     ?? 'smtp.gmail.com');
            $username = getenv('SMTP_USER')     ?: ($_ENV['SMTP_USER']     ?? '');
            $password = getenv('SMTP_PASS')     ?: ($_ENV['SMTP_PASS']     ?? '');
            $port     = getenv('SMTP_PORT')     ?: ($_ENV['SMTP_PORT']     ?? 587);
            $fromName = getenv('SMTP_FROM_NAME')?: ($_ENV['SMTP_FROM_NAME']?? 'Sistema de Enquetes');

            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($username, $fromName);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "Recuperação de Senha";
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2>Olá, {$toName}!</h2>
                    <p>Recebemos uma solicitação para redefinir a sua senha.</p>
                    <p>Clique no botão abaixo para criar uma nova senha (válido por 30 minutos):</p>
                    <p style='margin: 25px 0;'>
                        <a href='{$resetLink}' style='background-color: #2563eb; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Redefinir Minha Senha</a>
                    </p>
                    <p style='font-size: 12px; color: #777;'>Se você não solicitou essa alteração, ignore este e-mail.</p>
                </div>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erro no envio do e-mail de recuperação: " . $mail->ErrorInfo);
        }
    }
}