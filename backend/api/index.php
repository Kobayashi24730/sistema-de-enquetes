<?php

require_once __DIR__ . '/../vendor/autoload.php';

// 1. Carrega o .env se existir (safeLoad não lança exceção se não achar)
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

// 2. Lógica de CORS Dinâmico (Aceita domínios .vercel.app, localhost e a CLIENT_URL)
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$client_url  = $_ENV['CLIENT_URL'] ?? $_SERVER['CLIENT_URL'] ?? getenv('CLIENT_URL') ?: 'http://localhost:3000';

if (
    $http_origin === $client_url ||
    str_ends_with($http_origin, '.vercel.app') ||
    $http_origin === 'http://localhost:3000' ||
    $http_origin === 'http://localhost:5173'
) {
    header("Access-Control-Allow-Origin: " . $http_origin);
} else {
    header("Access-Control-Allow-Origin: " . $client_url);
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Intercepta a requisição OPTIONS (Preflight de CORS) imediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. Captura Método HTTP e Rota
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$route = rtrim($uri, '/');
if (empty($route)) {
    $route = '/';
}

// 4. Inicialização de Serviços e Banco de Dados
try {
    $pdo = \Config\Database::getConnection();
    \Config\Migration::run($pdo);

    $userController = new Controllers\UserController($pdo);
    $pollController = new Controllers\PollController();
    $votoController = new Controllers\VotoController(); // Descomente se usar esta classe
    $forgotPasswordController = new Controllers\ForgotPasswordController();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Erro de inicialização no servidor',
        'details' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// 5. Roteamento da Aplicação
switch ($route) {
    case '/login':
        if ($method === 'POST') {
            $userController->login();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para /login. Use POST.']);
        }
        break;

    case '/register':
    case '/usuarios':
        if ($method === 'POST') {
            $userController->create();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para /register. Use POST.']);
        }
        break;

    case '/reset-password':
    case '/forgot-password':
        if ($method === 'POST') {
            $forgotPasswordController->resetPassword();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido. Use POST.']);
        }
        break;

    case '/profile':
    case '/perfil':
        if ($method === 'GET') {
            $userData = Middlewares\AuthMiddleware::authenticate();
            $userController->show($userData['id']);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para /profile. Use GET.']);
        }
        break;

    case '/enquetes':
        if ($method === 'GET') {
            $pollController->index();
        } elseif ($method === 'POST') {
            $pollController->create();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para /enquetes.']);
        }
        break;

    case '/enquetes/show':
        if ($method === 'GET') {
            $pollController->show();
        }
        break;

    case '/enquetes/update':
        if ($method === 'PUT') {
            $pollController->update();
        }
        break;

    case '/enquetes/vote':
        if ($method === 'POST') {
            if (isset($votoController)) {
                $votoController->vote();
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'VotoController não foi instanciado.']);
            }
        }
        break;

    case '/enquetes/delete':
        if ($method === 'DELETE') {
            $pollController->delete();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => "Rota '{$route}' não encontrada."]);
        break;
}