<?php
namespace Middlewares;

use Exception;

class RateLimitMiddleware {
    public static function check(int $maxRequests = 10, int $windowSeconds = 60): void {
        $redisUrl = $_ENV['REDIS_URL'] ?? getenv('REDIS_URL') ?? null;
        if (!$redisUrl) {
            return;
        }

        try {
            $redis = new \Predis\Client($redisUrl);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $key = "rate_limit:{$ip}";

            $count = $redis->incr($key);
            if ($count === 1) {
                $redis->expire($key, $windowSeconds);
            }
            if ($count > $maxRequests) {
                http_response_code(429);
                echo json_encode(['error' => 'Muitas requisições. Tente novamente em alguns momentos.']);
                exit;
            }
        } catch (Exception $e) {
            error_log("Erro no RateLimitMiddleware (Redis): " . $e->getMessage());
        }
    }
}