<?php
namespace Controllers;

use Config\Database;
use PDO;

class StreamController {
    public function streamPollResults($pollId = null) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $db = Database::getConnection();
        $startTime = time();
        $maxDuration = 300;

        while (true) {
            if ((time() - $startTime) > $maxDuration) {
                break;
            }

            if ($pollId) {
                // ✅ Ajustado: po.enquete_id e ev.enquete_id
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
                // ✅ Ajustado: eo.enquete_id
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

            if (ob_get_level() > 0) ob_flush();
            flush();

            sleep(2);
        }
    }
}