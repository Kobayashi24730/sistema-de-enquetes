<?php
namespace Config;

use PDO;
use PDOException;

class Migration {
    public static function run(PDO $pdo): void {
        $queries = [
            // 1. Tabela de Usuários
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 2. Tabela de Tokens de Recuperação de Senha
            "CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_token (email, token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 3. Tabela de Enquetes
            "CREATE TABLE IF NOT EXISTS enquetes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                category VARCHAR(50) DEFAULT 'Geral',
                expires_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 4. Tabela de Opções da Enquete
            "CREATE TABLE IF NOT EXISTS enquetes_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                enquete_id INT NOT NULL,
                option_text VARCHAR(255) NOT NULL,
                votes INT DEFAULT 0,
                FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 5. Tabela de Votos da Enquete
            "CREATE TABLE IF NOT EXISTS enquete_votos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                enquete_id INT NOT NULL,
                option_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_enquete (user_id, enquete_id),
                FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE,
                FOREIGN KEY (option_id) REFERENCES enquetes_options(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        ];

        try {
            foreach ($queries as $sql) {
                $pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Erro na Migration: " . $e->getMessage());
            throw $e;
        }
    }
}