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

        // Define o tempo padrão de reconexão nativa do EventSource para 1 segundo
        echo "retry: 1000\n\n";
        flush();

        $db = Database::getConnection();
        $startTime = time();
        $maxDuration = 8; // Duração ideal para Vercel / Serverless

        while (true) {
            // Encerra suavemente antes do timeout do Serverless para o browser reconectar sem erro
            if ((time() - $startTime) >= $maxDuration) {
                echo ": keep-alive\n\n";
                flush();
                break;
            }

            // Cancela se o cliente fechou a conexão no navegador
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
                                        'votes', CAST((SELECT COUNT(*) FROM enquete_votos ev WHERE ev.option_id = eo.id) AS UNSIGNED)
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
                        // Decodifica as opções em um array PHP limpo
                        $options = json_decode($poll['options'] ?? '[]', true) ?? [];

                        $totalVotes = 0;
                        if (is_array($options)) {
                            foreach ($options as &$opt) {
                                $opt['votes'] = (int)($opt['votes'] ?? 0);
                                $totalVotes += $opt['votes'];
                            }
                        }

                        $poll['options'] = $options;
                        // Injeta o total de votos e garante tipos corretos para o frontend
                        $poll['total_votes'] = $totalVotes;
                    }
                } // <--- Fechamento do ELSE adicionado aqui!

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