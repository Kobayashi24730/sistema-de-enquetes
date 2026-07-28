<?php
namespace Controllers;

use Config\Database;
use PDO;

class StreamController {
    public function streamPollResults($pollId = null) {
        // Desativa limites de tempo do script PHP
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        // Headers essenciais para o SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Limpa e desativa todos os buffers de saída anteriores
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Define o tempo padrão de reconexão do EventSource para 1 segundo em caso de queda
        echo "retry: 1000\n\n";
        flush();

        $db = Database::getConnection();
        $startTime = time();
        $maxDuration = 8;

        while (true) {
            // Se atingir o tempo máximo do loop, encerra suavemente para o frontend reconectar sem erro
            if ((time() - $startTime) >= $maxDuration) {
                // Comentário SSE
                echo ": keep-alive\n\n";
                flush();
                break;
            }

            // Verifica se a conexão com o cliente/navegador foi abortada
            if (connection_aborted()) {
                break;
            }

            try {
                if ($pollId) {
                    $stmt = $db->prepare("
                        SELECT po.id, po.option_text, COUNT(v.id) as votes
                        FROM enquetes_options po
                        LEFT JOIN enquete_votos v ON v.option_id = po.id
                        WHERE po.enquete_id = ?
                        GROUP BY po.id
                    ");
                    $stmt->execute([$pollId]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $stmt = $db->prepare("
                        SELECT e.*,
                            (
                                SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        'id', eo.id,
                                        'option_text', eo.option_text,
                                        'votes', (SELECT COUNT(*) FROM enquete_votos ev WHERE ev.option_id = eo.id)
                                    )
                                )
                                FROM enquetes_options eo WHERE eo.enquete_id = e.id
                            ) AS options
                        FROM enquetes e
                        ORDER BY e.created_at DESC
                    ");
                    $stmt->execute();
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($results as &$poll) {
                        $poll['options'] = json_decode($poll['options'] ?? '[]', true);
                    }
                }
                echo "data: " . json_encode($results) . "\n\n";
                flush();

            } catch (\Throwable $e) {
                // Em caso de erro, loga ou envia evento de erro estruturado
                echo "event: error\ndata: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                flush();
                break;
            }

            sleep(2);
        }
    }
}