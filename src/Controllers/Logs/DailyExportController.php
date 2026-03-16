<?php

namespace Controllers\Logs;

use Controllers\ControllerInterface;
use Models\Database;

/**
 * Class DailyExportController
 */
class DailyExportController implements ControllerInterface
{
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/logs/daily-export' && $method === 'GET';
    }

    public function control(): void
    {
        // Récupération du token depuis les variables d'environnement
        $validToken = getenv('TOKEN');

        if (!isset($_GET['token']) || $_GET['token'] !== $validToken) {
            http_response_code(403);
            die('Access denied.');
        }

        $currentWeekDir = __DIR__ . '/../../../logs/current_week/';

        if (!is_dir($currentWeekDir)) {
            mkdir($currentWeekDir, 0755, true);
        }

        try {
            $db = Database::getConnection();
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $query = "SELECT id, user_id, action, table_concernee, element_id,
                    details, ip_address, system_info, date_action 
                    FROM logs 
                    WHERE DATE(date_action) = '$yesterday'";

            $result = $db->query($query);

            if ($result instanceof \mysqli_result && $result->num_rows > 0) {

                $filename = $currentWeekDir . 'logs_' . $yesterday . '.csv';
                $file = fopen($filename, 'w');

                if ($file !== false) {

                    fputcsv($file, [
                        'id',
                        'user_id',
                        'action',
                        'table_concernee',
                        'element_id',
                        'details',
                        'ip_address',
                        'system_info',
                        'date_action'
                    ]);

                    $idsToDelete = [];

                    while ($row = $result->fetch_assoc()) {
                        fputcsv($file, $row);

                        if (isset($row['id'])) {
                            $idsToDelete[] = (int)$row['id'];
                        }
                    }

                    fclose($file);

                    if (!empty($idsToDelete)) {
                        $chunks = array_chunk($idsToDelete, 1000);

                        foreach ($chunks as $chunk) {
                            $ids = implode(',', $chunk);
                            $db->query("DELETE FROM logs WHERE id IN ($ids)");
                        }
                    }

                    echo "Daily export successful: " . count($idsToDelete) . " rows exported.";
                } else {
                    echo "Failed to open file for writing.";
                }
            } else {
                echo "No logs to export for $yesterday.";
            }
        } catch (\Throwable $e) {
            error_log("Daily log export error: " . $e->getMessage());
            http_response_code(500);
            echo "Internal server error.";
        }
    }
}

