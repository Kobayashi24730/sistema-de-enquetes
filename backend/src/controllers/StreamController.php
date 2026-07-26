<?php
// src/Controllers/StreamController.php
namespace Controllers;

use Config\Database;
use PDO;

class StreamController {
    public function streamPollResults($pollId) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $db = Database::getConnection();
        $startTime = time();
        $maxDuration = 300;
        while (true) {
            if (time() - startTime > maxDuration) {
                break;
            }
            $stmt = $db->prepare("
                SELECT po.id, po.option_text, COUNT(v.id) as votes
                FROM poll_options po
                LEFT JOIN votes v ON v.option_id = po.id
                WHERE po.poll_id = ?
                GROUP BY po.id
            ");
            $stmt->execute([$pollId]);
            $results = $stmt->fetchAll();

            echo "data: " . json_encode($results) . "\n\n";

            if (ob_get_level() > 0) ob_flush();
            flush();
            sleep(2);
        }
    }
}