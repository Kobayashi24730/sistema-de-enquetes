<?php
namespace Controllers;

use Config\Database;
use Middlewares\AuthMiddleware;
use PDO;

class PollController {
        // GET /enquetes - Listar todas as enquetes
        public function index() {
            try {
                $db = Database::getConnection();
                $query = "SELECT e.id, e.title, e.description, e.created_at,COALESCE(e.category, 'Geral') AS category, u.name AS criador
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

    // GET /enquetes/show?id=1 - Exibir uma enquete com suas opções
    public function show($id = null) {
        $pollId = $id ?? $_GET['id'] ?? null;

        if (!$pollId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da enquete não informado.']);
            return;
        }

        try {
            $db = Database::getConnection();

            // Busca os dados da enquete
            $stmt = $db->prepare("SELECT e.*, u.name AS criador FROM enquetes e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
            $stmt->execute([$pollId]);
            $enquete = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$enquete) {
                http_response_code(404);
                echo json_encode(['error' => 'Enquete não encontrada.']);
                return;
            }

            // Busca as opções da enquete
            $stmtOpt = $db->prepare("SELECT id, option_text, votes FROM enquetes_options WHERE enquete_id = ?");
            $stmtOpt->execute([$pollId]);
            $enquete['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

            // Verifica se o usuário já votou na enquete
            $user = AuthMiddleware::authenticate();
            $votedOptionId = null;
            if ($user) {
                $stmt = $db->prepare("SELECT option_id FROM enquete_votos WHERE poll_id = ? AND user_id = ?");
                $stmt->execute([$pollId, $user['id']]);
                $vote = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($vote) {
                    $votedOptionId = $vote['option_id'];
                }
            }
            $enquete['voted_option_id'] = $votedOptionId;
            http_response_code(200);
            echo json_encode($enquete);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao buscar enquete: ' . $e->getMessage()]);
        }
    }

    // POST /enquetes - Criar Enquete
    public function create() {
        $user = AuthMiddleware::authenticate(); // verifica se o usuário está autenticado
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
            $stmt = $db->prepare("INSERT INTO enquetes (user_id, title, description, category) VALUES (?, ?, ?, ?)");
            $category = !empty($data['category']) ? $data['category'] : 'Geral';
            $stmt->execute([
                $user['id'],
                $data['title'],
                $data['description'] ?? null,
                $category
            ]);
            $pollId = $db->lastInsertId();
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

    // PUT /enquetes/update?id=8 - Atualizar Enquete
    public function update($id = null) {
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da enquete é obrigatório']);
            return;
        }

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
            // Verifica se a enquete existe e pertence ao usuário
            $stmtCheck = $db->prepare("SELECT user_id FROM enquetes WHERE id = ?");
            $stmtCheck->execute([$id]);
            $enquete = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$enquete) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'Enquete não encontrada']);
                return;
            }

            if ($enquete['user_id'] != $user['id']) {
                $db->rollBack();
                http_response_code(403);
                echo json_encode(['error' => 'Você não tem permissão para editar esta enquete']);
                return;
            }

            // Atualiza dados principais
            $category = !empty($data['category']) ? $data['category'] : 'Geral';
            $stmtUpdate = $db->prepare("UPDATE enquetes SET title = ?, description = ?, category = ? WHERE id = ?");
            $stmtUpdate->execute([
                $data['title'],
                $data['description'] ?? null,
                $category,
                $id
            ]);

            // Atualiza opções
            $stmtDeleteOpt = $db->prepare("DELETE FROM enquetes_options WHERE enquete_id = ?");
            $stmtDeleteOpt->execute([$id]);
            $stmtInsertOpt = $db->prepare("INSERT INTO enquetes_options (enquete_id, option_text) VALUES (?, ?)");
            foreach ($data['options'] as $optionText) {
                if (!empty(trim($optionText))) {
                    $stmtInsertOpt->execute([$id, trim($optionText)]);
                }
            }

            $db->commit();
            http_response_code(200);
            echo json_encode(['message' => 'Enquete atualizada com sucesso!']);
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar enquete: ' . $e->getMessage()]);
        }
    }

    // DELETE /enquetes/delete?id=1
    public function delete($id = null) {
        $user = AuthMiddleware::authenticate(); // protegido por JWT
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