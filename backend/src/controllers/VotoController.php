<?php

namespace Controllers;

use Config\Database;
use Middlewares\AuthMiddleware;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;

class VotoController {

    public function vote() {
        // Tenta obter o usuário se estiver autenticado, ou define um fallback
        $voterName = 'Um visitante';
        try {
            $user = AuthMiddleware::authenticate();
            if (!empty($user['name'])) {
                $voterName = $user['name'];
            }
        } catch (\Exception $e) {
            // Se o middleware lançar exceção por não estar logado, segue como anônimo
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Suporta tanto poll_id quanto enquete_id
        $optionId  = $data['option_id'] ?? null;
        $enqueteId = $data['poll_id']   ?? $data['enquete_id'] ?? null;

        if (!$optionId || !$enqueteId) {
            http_response_code(400);
            echo json_encode(['error' => 'IDs da enquete e da opção são obrigatórios']);
            return;
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // 1. Incrementa o voto na opção escolhida
            $stmtVote = $db->prepare("UPDATE enquetes_options SET votes = votes + 1 WHERE id = ? AND enquete_id = ?");
            $stmtVote->execute([$optionId, $enqueteId]);

            // 2. Busca dados do criador + título da enquete
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

            // Confirma a gravação no banco
            $db->commit();

            // 3. Dispara a notificação por e-mail se o criador possuir um e-mail válido
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

    private function sendVoteNotificationEmail($toEmail, $toName, $voterName, $pollTitle) {
        $mail = new PHPMailer(true);

        try {
            // Lê das variáveis de ambiente ou utiliza os fallbacks
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST']     ?? 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER']     ?? 'SEU_USERNAME_MAILTRAP';
            $mail->Password   = $_ENV['SMTP_PASS']     ?? 'SUA_SENHA_MAILTRAP';
            $mail->SMTPSecure = $_ENV['SMTP_SECURE']   ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT']     ?? 2525;
            $mail->CharSet    = 'UTF-8';

            // Remetente e Destinatário
            $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'nao-responda@sistemaenquetes.com';
            $fromName  = $_ENV['SMTP_FROM_NAME']  ?? 'Sistema de Enquetes';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);

            // Conteúdo do E-mail
            $mail->isHTML(true);
            $mail->Subject = "Novo voto na sua enquete: {$pollTitle}";
            $mail->Body    = "
                <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                    <h2>Olá, {$toName}!</h2>
                    <p>O usuário <strong>{$voterName}</strong> acabou de votar na sua enquete: <em>\"{$pollTitle}\"</em>.</p>
                    <hr style='border: 0; border-top: 1px solid #eee;'>
                    <p style='font-size: 12px; color: #777;'>Notificação automática do Sistema de Enquetes.</p>
                </div>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail via PHPMailer: " . $mail->ErrorInfo);
        }
    }
}