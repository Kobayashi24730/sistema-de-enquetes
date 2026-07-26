<?php
namespace Controllers;

use Config\Database;
use Middlewares\AuthMiddleware;
use PDO;

class PollController {

// GET /enquetes - Listar todas as enquetes (PÚBLICO)
    public function index() {
        try {
            $db = Database::getConnection();

            // Consulta ajustada sem a coluna 'expires_at' que causava o erro 500
            $query = "SELECT e.id, e.title, e.description, e.created_at, u.name AS criador
                      FROM enquetes e
                      JOIN users u ON e.user_id = u.id
                      ORDER BY e.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $enquetes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prepara a busca das opções de cada enquete
            $stmtOpt = $db->prepare("SELECT id, option_text, votes FROM enquetes_options WHERE enquete_id = ?");

            foreach ($enquetes as &$enquete) {
                $stmtOpt->execute([$enquete['id']]);
                $options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

                $enquete['options'] = $options;
                $enquete['total_votes'] = array_sum(array_column($options, 'votes'));
            }

            http_response_code(200);
            echo json_encode($enquetes);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao buscar enquetes: ' . $e->getMessage()]);
        }
    }

    // GET /enquetes/show?id=1 - Exibir uma enquete com suas opções (PÚBLICO)
    public function show($id = null) {
        $pollId = $id ?? $_GET['id'] ?? null;

        if (!$pollId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da enquete não informado.']);
            return;
        }

        try {
            $db = Database::getConnection();

            // 1. Busca os dados da enquete
            $stmt = $db->prepare("SELECT e.*, u.name AS criador FROM enquetes e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
            $stmt->execute([$pollId]);
            $enquete = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$enquete) {
                http_response_code(404);
                echo json_encode(['error' => 'Enquete não encontrada.']);
                return;
            }

            // 2. Busca as opções da enquete
            $stmtOpt = $db->prepare("SELECT id, option_text, votes FROM enquetes_options WHERE enquete_id = ?");
            $stmtOpt->execute([$pollId]);
            $enquete['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode($enquete);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao buscar enquete: ' . $e->getMessage()]);
        }
    }

    // POST /enquetes - Criar Enquete (PROTEGIDO POR JWT)
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
            // Ajustado para 'enquetes' (plural)
            $stmt = $db->prepare("INSERT INTO enquetes (user_id, title, description) VALUES (?, ?, ?)");
            $stmt->execute([
                $user['id'],
                $data['title'],
                $data['description'] ?? null
            ]);
            $pollId = $db->lastInsertId();

            // Ajustado para 'enquete_id' para casar com a Migration
            $stmtOpt = $db->prepare("INSERT INTO enquetes_options (enquete_id, option_text) VALUES (?, ?)");
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

    // POST /enquetes/vote - Registrar Voto (PROTEGIDO POR JWT)
    public function vote() {
        $user = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['option_id']) || empty($data['poll_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione uma opção e informe a enquete.']);
            return;
        }

        $db = Database::getConnection();

        try {
            // Incrementa a contagem na tabela 'enquetes_options'
            $stmt = $db->prepare("UPDATE enquetes_options SET votes = votes + 1 WHERE id = ? AND enquete_id = ?");
            $stmt->execute([$data['option_id'], $data['poll_id']]);

            http_response_code(200);
            echo json_encode(['message' => 'Voto computado com sucesso!']);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao processar voto: ' . $e->getMessage()]);
        }
    }

    // DELETE /enquetes/delete?id=1 (PROTEGIDO POR JWT)
        public function delete($id = null) {
            $user = AuthMiddleware::authenticate();
            $pollId = $id ?? $_GET['id'] ?? null;

            if (!$pollId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID da enquete não informado.']);
                return;
            }

            $db = Database::getConnection();

            try {
                // Garante que apenas o criador pode excluir a enquete
                $stmt = $db->prepare("DELETE FROM enquetes WHERE id = ? AND user_id = ?");
                $stmt->execute([$pollId, $user['id']]);

                if ($stmt->rowCount() === 0) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Você não tem permissão para excluir esta enquete ou ela não existe.']);
                    return;
                }

                http_response_code(200);
                echo json_encode(['message' => 'Enquete excluída com sucesso.']);
            } catch (\PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao excluir enquete: ' . $e->getMessage()]);
            }
        }
}