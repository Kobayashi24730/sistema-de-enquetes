<?php

namespace Controllers;

use Config\Database;
use Middlewares\AuthMiddleware;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;

class VotoController {

    public function vote() {
        $voterName = 'Um visitante';
        $userId = null;

        // Tenta autenticar o usuário via JWT
        try {
            $user = AuthMiddleware::authenticate();
            if (!empty($user)) {
                // Captura o ID do usuário (suporta $user['id'] ou $user['data']['id'])
                $userId = $user['id'] ?? $user['data']['id'] ?? null;
                $voterName = $user['name'] ?? $user['data']['name'] ?? 'Um usuário registrado';
            }
        } catch (\Exception $e) {
            // Se não houver token/login válido, segue como visitante anônimo
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $optionId  = $data['option_id'] ?? null;
        $enqueteId = $data['enquete_id'] ?? null;

        if (!$optionId || !$enqueteId) {
            http_response_code(400);
            echo json_encode(['error' => 'IDs da enquete e da opção são obrigatórios']);
            return;
        }

        $db = Database::getConnection();

        // Se o usuário estiver logado, verifica se ele já votou nesta enquete
        if ($userId) {
            $stmtCheck = $db->prepare("SELECT id FROM enquete_votos WHERE enquete_id = ? AND user_id = ? LIMIT 1");
            $stmtCheck->execute([$enqueteId, $userId]);
            if ($stmtCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Você já votou nesta enquete.']);
                return;
            }
        }

        $db->beginTransaction();

        try {
            // 1. Registra o voto individual na tabela enquete_votos
            $stmtInsertVote = $db->prepare("
                INSERT INTO enquete_votos (enquete_id, option_id, user_id)
                VALUES (?, ?, ?)
            ");
            $stmtInsertVote->execute([$enqueteId, $optionId, $userId]);

            // 2. Incrementa o voto na opção escolhida
            $stmtVote = $db->prepare("UPDATE enquetes_options SET votes = votes + 1 WHERE id = ? AND enquete_id = ?");
            $stmtVote->execute([$optionId, $enqueteId]);

            // Busca dados do criador e o título da enquete
            $stmtOwner = $db->prepare("
                SELECT e.title, u.name AS criador_nome, u.email AS criador_email
                FROM enquetes e
                JOIN users u ON e.user_id = u.id
                WHERE e.id = ?
            ");
            $stmtOwner->execute([$enqueteId]);
            $enqueteInfo = $stmtOwner->fetch(PDO::FETCH_ASSOC);

            if (!$enqueteInfo) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'Enquete não encontrada']);
                return;
            }

            // Confirma as alterações no banco de dados
            $db->commit();

            // Dispara a notificação por e-mail se o criador tiver e-mail válido
            if (!empty($enqueteInfo['criador_email'])) {
                $this->sendVoteNotificationEmail(
                    $enqueteInfo['criador_email'],
                    $enqueteInfo['criador_nome'],
                    $voterName,
                    $enqueteInfo['title']
                );
            }

            http_response_code(200);
            echo json_encode(['message' => 'Voto computado com sucesso!']);
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao processar voto: ' . $e->getMessage()]);
        }
    }

    // função para enviar o email de notificação de voto
    private function sendVoteNotificationEmail($toEmail, $toName, $voterName, $pollTitle) {
        $mail = new PHPMailer(true);

        try {
            // Leitura de variáveis de ambiente usando getenv() com suporte a $_ENV
            $host     = getenv('SMTP_HOST')     ?: ($_ENV['SMTP_HOST']     ?? 'smtp.gmail.com');
            $username = getenv('SMTP_USER')     ?: ($_ENV['SMTP_USER']     ?? 'seu_email@gmail.com');
            $password = getenv('SMTP_PASS')     ?: ($_ENV['SMTP_PASS']     ?? 'sua_senha_de_app');
            $port     = getenv('SMTP_PORT')     ?: ($_ENV['SMTP_PORT']     ?? 587);
            $fromName = getenv('SMTP_FROM_NAME')?: ($_ENV['SMTP_FROM_NAME']?? 'Sistema de Enquetes');

            // Configuração do PHPMailer
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $port;
            $mail->CharSet    = 'UTF-8';

            // Remetente
            $mail->setFrom($username, $fromName);
            $mail->addAddress($toEmail, $toName);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = "Novo voto na sua enquete: {$pollTitle}";
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2 style='color: #2563eb;'>Olá, {$toName}!</h2>
                    <p>O usuário <strong>{$voterName}</strong> acabou de votar na sua enquete: <em>\"{$pollTitle}\"</em>.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #777;'>Esta é uma notificação automática do Sistema de Enquetes.</p>
                </div>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erro no envio do e-mail de notificação: " . $mail->ErrorInfo);
        }
    }
}