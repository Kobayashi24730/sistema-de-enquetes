<?php

require_once __DIR__ . '/vendor/autoload.php';

// 1. Configurações de CORS
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Responde imediatamente às requisições preflight (OPTIONS) do navegador
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Carrega as variáveis de ambiente (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 3. Captura o Método HTTP e a Rota chamada
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ajusta a rota removendo barras extras do final
$route = rtrim($uri, '/');
if (empty($route)) {
    $route = '/';
}

// 4. Conexão com o Banco de Dados via Singleton e Instância do Controller
try {
    // Chama o método estático da sua classe Config\Database
    $pdo = \Config\Database::getConnection();

    // Instancia o Controller passando a conexão PDO
    $userController = new Controllers\UserController($pdo);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno de inicialização no servidor.']);
    exit;
}

// 5. Mapeamento de Rotas
switch ($route) {

    // === ROTA DE LOGIN ===
    case '/login':
        if ($method === 'POST') {
            $userController->login();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para esta rota. Use POST.']);
        }
        break;

    // === ROTA DE CADASTRO (Mapeia tanto /register quanto /usuarios) ===
    case '/register':
    case '/usuarios':
        if ($method === 'POST') {
            $userController->create();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido para esta rota. Use POST.']);
        }
        break;

    // === ROTA DE PERFIL PROTEGIDA (Mapeia tanto /profile quanto /perfil) ===
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

    // === ROTA PADRÃO / 404 ===
    default:
        http_response_code(404);
        echo json_encode(['error' => "Rota '{$route}' não encontrada."]);
        break;
}