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

        // Limpa e desativa todos os buffers de saída
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        echo "retry: 1000\n\n";
        flush();

        $db = Database::getConnection();
        $startTime = time();
        $maxDuration = 8; // Duração

        while (true) {
            if ((time() - $startTime) >= $maxDuration) {
                echo ": keep-alive\n\n";
                flush();
                break;
            }

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

                    foreach ($results as &$opt) {
                        $opt['votes'] = (int)$opt['votes'];
                    }

                } else {
                    $stmt = $db->prepare("SELECT * FROM enquetes ORDER BY created_at DESC");
                    $stmt->execute();
                    $enquetes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $results = [];

                    foreach ($enquetes as $poll) {
                        $stmtOpt = $db->prepare("
                            SELECT
                                eo.id,
                                eo.option_text,
                                COUNT(ev.id) AS votes
                            FROM enquetes_options eo
                            LEFT JOIN enquete_votos ev ON ev.option_id = eo.id
                            WHERE eo.enquete_id = ?
                            GROUP BY eo.id
                            ORDER BY eo.id ASC
                        ");
                        $stmtOpt->execute([$poll['id']]);
                        $options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

                        $totalVotes = 0;
                        foreach ($options as &$opt) {
                            $opt['votes'] = (int)$opt['votes'];
                            $totalVotes += $opt['votes'];
                        }
                        unset($opt);

                        $poll['options'] = $options;
                        $poll['total_votes'] = $totalVotes;

                        $results[] = $poll;
                    }
                }

                // Envia o JSON limpo
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