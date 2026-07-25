<?php
namespace Controllers;

use Config\Database;
use Middlewares\AuthMiddleware;
use PDO;

class PollController {
    // POST /api/polls - Criar Enquete
    public function create() {
        $user = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['options']) || !is_array($data['options'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Título e opções são obrigatórios']);
            return;
        }

        $countOptions = count($data['options']);
        if ($countOptions < 2 || $countOptions > 8) {
            http_response_code(400);
            echo json_encode(['error' => 'A enquete deve ter entre 2 e 8 opções']);
            return;
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("INSERT INTO polls (user_id, title, description, category, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['id'],
                $data['title'],
                $data['description'] ?? null,
                $data['category'] ?? 'Geral',
                $data['expires_at'] ?? null
            ]);
            $pollId = $db->lastInsertId();

            $stmtOpt = $db->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
            foreach ($data['options'] as $optionText) {
                $stmtOpt->execute([$pollId, trim($optionText)]);
            }

            $db->commit();
            http_response_code(201);
            echo json_encode(['message' => 'Enquete criada com sucesso!', 'poll_id' => $pollId]);

        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao criar enquete: ' . $e->getMessage()]);
        }
    }

    // POST /api/polls/{id}/vote - Registrar Voto
    public function vote($pollId) {
        $user = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['option_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione uma opção para votar']);
            return;
        }

        $db = Database::getConnection();

        try {
            $stmt = $db->prepare("INSERT INTO votes (poll_id, option_id, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$pollId, $data['option_id'], $user['id']]);

            http_response_code(200);
            echo json_encode(['message' => 'Voto computado com sucesso!']);

        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(400);
                echo json_encode(['error' => 'Você já votou nesta enquete.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao processar voto.']);
            }
        }
    }
}