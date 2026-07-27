<?php
namespace Models;

use PDO;

class User {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Buscar usuário por e-mail
    public function findByEmail(string $email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    // Criar novo usuário
    public function create(array $data) {
        $stmt = $this->db->prepare( "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)" );
        return $stmt->execute([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
    }
}