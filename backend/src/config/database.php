<?php
namespace Config;

use PDO;
use PDOException;

<?php

class Database {
    public static function getConnection() {
        $host = getenv('DB_HOST') ?: 'mysql-1b29cb70-ajenteduplo000-ad7d.l.aivencloud.com';
        $port = getenv('DB_PORT') ?: '25167';
        $dbname = getenv('DB_NAME') ?: 'padrãodb';
        $user = getenv('DB_USER') ?: 'avnadmin';
        $pass = getenv('DB_PASS') ?: 'SUA_SENHA';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na conexão com o banco: ' . $e->getMessage()]);
            exit;
        }
    }
}