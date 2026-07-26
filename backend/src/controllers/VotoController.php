<?php

namespace Controllers;

// Imports de Namespaces
use Config\Database;
use Middlewares\AuthMiddleware; // Ajuste conforme o caminho exato do seu AuthMiddleware
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;

class VotoController {

    // POST /enquetes/vote - Computar Voto
    public function vote() {
        $user = AuthMiddleware::authenticate(); // Pega dados de quem está votando
        $data = json_decode(file_get_contents('php://input'), true);

        $optionId = $data['option_id'] ?? null;
        $enqueteId = $data['enquete_id'] ?? null;

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

            // 2. Busca informações do criador da enquete + título da enquete
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

            // Confirma a alteração no banco de dados
            $db->commit();

            // 3. Dispara a notificação por e-mail para o dono da enquete
            $this->sendVoteNotificationEmail(
                $enqueteInfo['criador_email'],
                $enqueteInfo['criador_nome'],
                $user['name'] ?? 'Um usuário', // Nome de quem votou
                $enqueteInfo['title']
            );

            http_response_code(200);
            echo json_encode(['message' => 'Voto computado com sucesso!']);

        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao processar voto: ' . $e->getMessage()]);
        }
    }

    // Método auxiliar para envio de e-mail SMTP
    private function sendVoteNotificationEmail($toEmail, $toName, $voterName, $pollTitle) {
        $mail = new PHPMailer(true);

        try {
            // Configurações do servidor de e-mail (Ex: Mailtrap para testes ou Gmail)
            $mail->isSMTP();
            $mail->Host       = 'sandbox.smtp.mailtrap.io'; // Altere para seu servidor SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'SEU_USERNAME_MAILTRAP';    // Coloque suas credenciais
            $mail->Password   = 'SUA_SENHA_MAILTRAP';
            $mail->Port       = 2525;
            $mail->CharSet    = 'UTF-8';

            // Dados do remetente e destinatário
            $mail->setFrom('nao-responda@seuapp.com', 'Sistema de Enquetes');
            $mail->addAddress($toEmail, $toName);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = "Novo voto registrado: {$pollTitle}";
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2>Olá, {$toName}!</h2>
                    <p>O usuário <strong>{$voterName}</strong> acabou de votar na sua enquete: <em>\"{$pollTitle}\"</em>.</p>
                    <hr>
                    <p style='font-size: 12px; color: #666;'>Esta é uma notificação automática do seu Sistema de Enquetes.</p>
                </div>
            ";

            $mail->send();
        } catch (Exception $e) {
            // Registra a falha no log do servidor sem interromper a resposta para o usuário React
            error_log("Erro ao enviar e-mail de notificação: " . $mail->ErrorInfo);
        }
    }
}