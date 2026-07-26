<?php
$allowed_origin = getenv('CLIENT_URL') ?: 'http://localhost:3000';

header("Access-Control-Allow-Origin: " . $allowed_origin);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Carrega as variáveis de ambiente (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 3. Captura o Método HTTP e a Rota chamada
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$route = rtrim($uri, '/');
if (empty($route)) {
    $route = '/';
}

try {
    $pdo = \Config\Database::getConnection();
    \Config\Migration::run($pdo);
    $userController = new Controllers\UserController($pdo);
    $pollController = new Controllers\PollController();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro de inicialização',
        'details' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}


switch ($route) {
    case '/login':
        if ($method === 'POST') {
            $userController->login();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para esta rota. Use POST.']);
        }
        break;

    case '/register':
    case '/usuarios':
        if ($method === 'POST') {
            $userController->create();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para esta rota. Use POST.']);
        }
        break;

    case '/profile':
    case '/perfil':
        if ($method === 'GET') {
            $userData = Middlewares\AuthMiddleware::authenticate();
            $userController->show($userData['id']);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para esta rota. Use GET.']);
        }
        break;

    // ==========================================
    // ROTA PÚBLICA
    // ==========================================
    case '/enquetes':
        if ($method === 'GET') {
            $pollController->index();
        } elseif ($method === 'POST') {
            // Rota protegida: Criar enquete
            $pollController->create();
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

    // ==========================================
    // ROTA PROTEGIDA
    // ==========================================
    case '/enquetes/vote':
        if ($method === 'POST') {
            $votoController->vote();
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