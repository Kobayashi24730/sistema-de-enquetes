<?php

// 1. IMPORTANTE: Carrega todas as dependências do Composer (Dotenv, Controllers, etc)
require_once __DIR__ . '/../vendor/autoload.php';

$allowed_origin = getenv('CLIENT_URL') ?: 'http://localhost:3000';

header("Access-Control-Allow-Origin: " . $allowed_origin);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Carrega as variáveis de ambiente (.env) se o arquivo existir
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

// ... restante do seu código igual ...