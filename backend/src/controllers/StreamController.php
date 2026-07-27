<?php
namespace Controllers;

use Config\Database;
use PDO;

class StreamController {
    public function streamPollResults($pollId = null) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Importante para NGINX/Vercel não segurar o buffer

        $db = Database::getConnection();
        $startTime = time();
        $maxDuration = 300; // 5 minutos máximo por conexão SSE

        while (true) {
            // Fecha a conexão após o tempo limite para evitar vazamento de memória/processos
            if ((time() - $startTime) > $maxDuration) {
                break;
            }

            if ($pollId) {
                // Consulta para uma enquete específica
                $stmt = $db->prepare("
                    SELECT po.id, po.option_text, COUNT(v.id) as votes
                    FROM enquetes_options po
                    LEFT JOIN enquete_votos v ON v.option_id = po.id
                    WHERE po.poll_id = ?
                    GROUP BY po.id
                ");
                $stmt->execute([$pollId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Consulta para todas as enquetes (usado pelo hook useEnqueteRealtime)
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
                            FROM enquetes_options eo WHERE eo.poll_id = e.id
                        ) AS options
                    FROM enquetes e
                    ORDER BY e.created_at DESC
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Decodifica o JSON retornado do banco para enviar como objeto/array puro
                foreach ($results as &$poll) {
                    $poll['options'] = json_decode($poll['options'] ?? '[]');
                }
            }

            echo "data: " . json_encode($results) . "\n\n";

            if (ob_get_level() > 0) ob_flush();
            flush();

            // Espera 2 segundos antes do próximo envio
            sleep(2);
        }
    }
}