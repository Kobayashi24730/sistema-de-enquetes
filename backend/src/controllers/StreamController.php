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

        $db = Database::getConnection();
        $startTime = time();

        // Em Serverless, mantemos transmissões mais curtas (ex: 25 segundos)
        // O cliente EventSource no frontend reconecta automaticamente assim que fechar.
        $maxDuration = 25;

        while (true) {
            if ((time() - $startTime) >= $maxDuration) {
                // Envia um ping final para instruir a reconexão suave
                echo "event: ping\ndata: {\"retry\": 1000}\n\n";
                flush();
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
                        $poll['options'] = json_decode($poll['options'] ?? '[]');
                    }
                }

                echo "data: " . json_encode($results) . "\n\n";
                flush();

            } catch (\Throwable $e) {
                echo "event: error\ndata: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                flush();
                break;
            }

            sleep(2);
        }
    }
}