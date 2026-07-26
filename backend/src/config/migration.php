<?php
namespace Config;

use PDO;
use PDOException;

class Migration {
    public static function run(PDO $pdo): void {
        $queries = [
            // Tabela de Usuários
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // Tabela de Enquetes
            "CREATE TABLE IF NOT EXISTS enquetes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50) DEFAULT 'Geral',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // Tabela de Opções da Enquete
            "CREATE TABLE IF NOT EXISTS enquetes_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                enquete_id INT NOT NULL,
                option_text VARCHAR(255) NOT NULL,
                votes INT DEFAULT 0,
                FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS enquete_votos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                poll_id INT NOT NULL,
                option_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_poll (user_id, poll_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
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