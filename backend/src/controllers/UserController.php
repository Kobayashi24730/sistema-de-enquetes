<?php
namespace Controllers;

use PDO;
use PDOException;
use Throwable;

/**
 * Controller responsável por gerenciar operações relacionadas aos usuários.
 */
class UserController {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);

        // Aceita campos tanto em português (nome/senha) quanto em inglês (name/password) do front-end
        $nome = $data['nome'] ?? $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $senha = $data['senha'] ?? $data['password'] ?? null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email inválido']);
            return;
        }

        if (strlen($senha) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Senha deve ter no mínimo 6 caracteres']);
            return;
        }

        if (empty($nome) || empty($email) || empty($senha)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome, e-mail e senha são obrigatórios.']);
            return;
        }

        try {
            // 1. Verifica se e-mail já existe na tabela 'users'
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'E-mail já cadastrado.']);
                return;
            }

            // 2. Hash da senha
            $passwordHash = password_hash($senha, PASSWORD_BCRYPT);

            // 3. Insere na tabela 'users' com os nomes de colunas corretos (name, email, password)
            $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
            $stmt = $this->db->prepare($query);

            $stmt->execute([
                ':name'     => $nome,
                ':email'    => $email,
                ':password' => $passwordHash
            ]);

            http_response_code(201);
            echo json_encode(['message' => 'Usuário criado com sucesso!']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao criar usuário: ' . $e->getMessage()]);
        }
    }

    // BUSCAR PERFIL POR ID
    public function show($id = null) {
        $userId = $id ?? $_GET['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário não informado.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, name AS nome, email, created_at FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado.']);
                return;
            }

            echo json_encode($user);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao buscar usuário: ' . $e->getMessage()]);
        }
    }

    // UPDATE (ATUALIZAR DADOS DO USUÁRIO)
    public function update($id = null) {
        $data = json_decode(file_get_contents("php://input"), true);

        $userId = $id ?? $data['id'] ?? null;
        $nome = $data['nome'] ?? $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $senha = $data['senha'] ?? $data['password'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário não informado.']);
            return;
        }

        if (empty($nome) || empty($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome e e-mail são obrigatórios.']);
            return;
        }

        try {
            if (!empty($senha)) {
                $query = "UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id";
                $params = [
                    ':name'     => $nome,
                    ':email'    => $email,
                    ':password' => password_hash($senha, PASSWORD_BCRYPT),
                    ':id'       => $userId
                ];
            } else {
                $query = "UPDATE users SET name = :name, email = :email WHERE id = :id";
                $params = [
                    ':name'  => $nome,
                    ':email' => $email,
                    ':id'    => $userId
                ];
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            echo json_encode(['message' => 'Dados do usuário atualizados com sucesso!']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar dados do usuário: ' . $e->getMessage()]);
        }
    }

    // DELETE (EXCLUIR USUÁRIO)
    public function delete($id = null) {
        $userId = $id ?? $_GET['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário não informado.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            echo json_encode(['message' => 'Usuário excluído com sucesso!']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao excluir usuário: ' . $e->getMessage()]);
        }
    }


    // LOGIN (AUTENTICAÇÃO)
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? null;
        $senha = $data['senha'] ?? $data['password'] ?? null;

        if (empty($email) || empty($senha)) {
            http_response_code(400);
            echo json_encode(['error' => 'E-mail e senha são obrigatórios.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, name, email, password FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($senha, $user['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'E-mail ou senha inválidos.']);
                return;
            }
            $secretKey = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? throw new Exception('JWT_SECRET não configurado');

            $payload = [
                'iss'  => 'localhost',
                'aud'  => 'localhost',
                'iat'  => time(),
                'exp'  => time() + (60 * 60 * 24),
                'data' => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email']
                ]
            ];

            $jwt = \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');

            http_response_code(200);
            echo json_encode([
                'message' => 'Login realizado com sucesso!',
                'token'   => $jwt,
                'user'    => [
                    'id'    => $user['id'],
                    'nome'  => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao realizar login: ' . $e->getMessage()]);
        }
    }
}