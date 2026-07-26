<?php

namespace Config;

use PDO;
use PDOException;

class Database {
    public static function getConnection() {
        $host   = $_ENV['DB_HOST']   ?? getenv('DB_HOST')   ?: 'mysql-1b29cb70-ajenteduplo000-ad7d.l.aivencloud.com';
        $port   = $_ENV['DB_PORT']   ?? getenv('DB_PORT')   ?: '25167';
        $dbname = $_ENV['DB_NAME']   ?? getenv('DB_NAME')   ?: 'defaultdb';
        $user   = $_ENV['DB_USER']   ?? getenv('DB_USER')   ?: 'avnadmin';
        $pass   = $_ENV['DB_PASS']   ?? getenv('DB_PASS')   ?: 'SUA_SENHA';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na conexão com o banco: ' . $e->getMessage()]);
            exit;
        }
    }
}