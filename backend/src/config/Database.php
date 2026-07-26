<?php

namespace Config;

use PDO;
use PDOException;

class Database {
    public static function getConnection() {
        $host   = $_ENV['DB_HOST']   ?? getenv('DB_HOST')   ?? throw new \RuntimeException('DB_HOST não configurado no .env');
        $port   = $_ENV['DB_PORT']   ?? getenv('DB_PORT')   ?? throw new \RuntimeException('DB_PORT não configurado no .env');
        $dbname = $_ENV['DB_NAME']   ?? getenv('DB_NAME')   ?? throw new \RuntimeException('DB_NAME não configurado no .env');
        $user   = $_ENV['DB_USER']   ?? getenv('DB_USER')   ?? throw new \RuntimeException('DB_USER não configurado no .env');
        $pass   = $_ENV['DB_PASS']   ?? getenv('DB_PASS')   ?? throw new \RuntimeException('DB_PASS não configurado no .env');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na conexão com o banco: ' . $e->getMessage()]);
            exit;
        }
    }
}