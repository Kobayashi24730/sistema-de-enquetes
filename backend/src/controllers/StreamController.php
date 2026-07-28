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
                    // Mesma lógica de PollController::show(), incluindo o criador
                    $stmt = $db->prepare("
                        SELECT e.*, u.name AS criador
                        FROM enquetes e
                        JOIN users u ON e.user_id = u.id
                        WHERE e.id = ?
                    ");
                    $stmt->execute([$pollId]);
                    $enquete = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($enquete) {
                        // Lê 'votes' direto da tabela de opções (fonte real do contador),
                        // igual ao PollController::index()/show() — nada de COUNT em enquete_votos.
                        $stmtOpt = $db->prepare("SELECT id, option_text, votes FROM enquetes_options WHERE enquete_id = ?");
                        $stmtOpt->execute([$pollId]);
                        $options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

                        $enquete['options'] = $options;
                        $enquete['total_votes'] = array_sum(array_column($options, 'votes'));

                        $results = [$enquete];
                    } else {
                        $results = [];
                    }
                } else {
                    $stmt = $db->prepare("
                        SELECT e.id, e.title, e.description, e.created_at, e.user_id,
                               COALESCE(e.category, 'Geral') AS category, u.name AS criador
                        FROM enquetes e
                        JOIN users u ON e.user_id = u.id
                        ORDER BY e.created_at DESC
                    ");
                    $stmt->execute();
                    $enquetes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $stmtOpt = $db->prepare("SELECT id, option_text, votes FROM enquetes_options WHERE enquete_id = ?");

                    foreach ($enquetes as &$poll) {
                        $stmtOpt->execute([$poll['id']]);
                        $options = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

                        $poll['options'] = $options;
                        $poll['total_votes'] = array_sum(array_column($options, 'votes'));
                    }
                    unset($poll);

                    $results = $enquetes;
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