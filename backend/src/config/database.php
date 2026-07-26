<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db   = $_ENV['DB_NAME'] ?? 'enquete_db';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            try {
                // 1. Conecta apenas no Servidor MySQL (sem especificar o banco)
                $pdoServer = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                // 2. Garante que o banco de dados exista
                $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                // 3. Conecta no banco de dados específico
                self::$instance = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

            } catch (PDOException $e) {
                throw new PDOException("Erro na conexão com o banco de dados: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }
}