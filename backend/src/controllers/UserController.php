<?php
namespace Controllers;

use Exception;
use PDO;

/**
 * (CRUD) Controller responsável por gerenciar operações relacionadas a o usuários.
 */

class UserController {
    private $db;
    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome, e-mail e senha são obrigatórios.']);
            return;
        }
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $data['email']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'E-mail já cadastrado.']);
            return;
        }
        $passwordHash = password_hash($data['senha'], PASSWORD_BCRYPT);
        $query = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute([
                ':nome' => $data['nome'],
                ':email' => $data['email'],
                ':senha' => $passwordHash
            ]);
            http_response_code(201);
            echo json_encode(['message' => 'Usuário criado com sucesso!']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao criar usuário: ' . $e->getMessage()]);
        }
    }

    //   BUSCAR PERFIL POR ID
    public function show() {
        $stmt = $this->db->prepare("SELECT id, nome, email, created_at FROM usuarios WHERE id = :id");
        $stmt->execute([':id' =>  $this->id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuário não encontrado.']);
            return;
        }
        echo json_encode($user);
    }

    // UPDATE ( ATUALIZAR DADOS DO USUSARIO )
    public function update($id = null) {
        $data = json_decode(file_get_contents("php://input"), true);

        // Garante que o ID venha pelo parâmetro ou do corpo da requisição (JSON)
        $userId = $id ?? $data['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário não informado.']);
            return;
        }

        // Validação corrigida ($data em vez de $date)
        if (empty($data['nome']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome e e-mail são obrigatórios.']);
            return;
        }

        // Se informou senha, atualiza com a senha hasheada
        if (!empty($data['senha'])) {
            $query = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id";
            $params = [
                ':nome'  => $data['nome'],
                ':email' => $data['email'],
                ':senha' => password_hash($data['senha'], PASSWORD_BCRYPT),
                ':id'    => $userId
            ];
        } else { // Se não enviou senha, atualiza apenas nome e e-mail
            $query = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";
            $params = [
                ':nome'  => $data['nome'],
                ':email' => $data['email'],
                ':id'    => $userId
            ];
        }

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            echo json_encode(['message' => 'Dados do usuário atualizados com sucesso!']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar dados do usuário: ' . $e->getMessage()]);
        }
    }

    public function delete() {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");

        try {
            $stmt->execute([':id' =>

            id]);
            echo json_encode(['message' => 'Usuário excluído com sucesso!']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao excluir usuário: ' . $e->getMessage()]);
        }
    }
}