<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega o .env se existir
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}
// Lógica de CORS Dinâmico para Vercel e Dev
$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$client_url  = $_ENV['CLIENT_URL'] ?? $_SERVER['CLIENT_URL'] ?? getenv('CLIENT_URL') ?: 'http://localhost:5173';
if (
    preg_match('/^https:\/\/.*\.vercel\.app$/', $http_origin) ||
    $http_origin === 'http://localhost:3000' ||
    $http_origin === 'http://localhost:5173'
) {
    header("Access-Control-Allow-Origin: " . $http_origin);
} else {
    header("Access-Control-Allow-Origin: " . $client_url);
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Cache-Control, Accept");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Captura e Normalização da Rota
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// REMOVE O PREFIXO /api SE EXISTIR NA URL
if (str_starts_with($uri, '/api')) {
    $uri = substr($uri, 4);
}
$route = rtrim($uri, '/');
if (empty($route)) {
    $route = '/';
}

// Inicialização de Serviços e Banco de Dados
try {
    $pdo = \Config\Database::getConnection();
    \Config\Migration::run($pdo);

    $userController = new Controllers\UserController($pdo);
    $pollController = new Controllers\PollController();
    $votoController = new Controllers\VotoController();
    $forgotPasswordController = new Controllers\ForgotPasswordController();
    $streamController = new Controllers\StreamController();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Erro de inicialização no servidor',
        'details' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Roteamento da rotas
switch ($route) {
    case '/stream':
        if ($method === 'GET') {
            $pollId = $_GET['poll_id'] ?? $_GET['id'] ?? null;
            $streamController->streamPollResults($pollId);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para /stream. Use GET.']);
        }
        break;

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
    case '/usuarios/item':
        $userData = Middlewares\AuthMiddleware::authenticate();

        if ($method === 'PUT') {
            $userController->update($userIdFromRoute);
        } elseif ($method === 'DELETE') {
            $userController->delete($userIdFromRoute);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para /usuarios/{id}. Use PUT ou DELETE.']);
        }
        break;

    case '/forgot-password':
        if ($method === 'POST') {
            $forgotPasswordController->sendResetLink();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido. Use POST.']);
        }
        break;

    case '/reset-password':
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
            // Exige autenticação para criar enquetes
            $userData = Middlewares\AuthMiddleware::authenticate();
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
            $userData = Middlewares\AuthMiddleware::authenticate();
            $pollController->update();
        }
        break;

    case '/enquetes/vote':
        if ($method === 'POST') {
            if (isset($votoController)) {
                // Adicionado o namespace Middlewares\
                Middlewares\RateLimitMiddleware::check(5, 60);
                $votoController->vote();
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'VotoController não foi instanciado.']);
            }
        }
        break;

    case '/enquetes/delete':
        if ($method === 'DELETE') {
            $userData = Middlewares\AuthMiddleware::authenticate();
            $pollController->delete();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => "Rota '{$route}' não encontrada."]);
        break;
}