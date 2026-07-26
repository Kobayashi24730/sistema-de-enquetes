<?php
namespace Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthMiddleware {
    public static function authenticate(): array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Token JWT não fornecido']);
            exit;
        }
        $token = $matches[1];

        try {
            $secretKey = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? 'a8Q3HtDTYlnyLXMtWgolaAi3TDPWwnd0km2h4nj80y5';
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            return (array) $decoded->data;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido ou expirado']);
            exit;
        }
    }
}

class RateLimitMiddleware {
    public static function check(int $maxRequests = 10, int $windowSeconds = 60): void {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit:{$ip}:" . date('YmdHi');

        $file = sys_get_temp_dir() . '/' . md5($key);
        $count = file_exists($file) ? (int)file_get_contents($file) : 0;

        if ($count >= $maxRequests) {
            http_response_code(429);
            echo json_encode(['error' => 'Muitas requisicoes. Tente novamente em 1 minuto.']);
            exit;
        }

        file_put_contents($file, $count + 1);
    }
}