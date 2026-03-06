<?php

namespace Controllers\Logs;

use Controllers\ControllerInterface;
use Models\Database;

/**
 * Class DailyExportController
 *
 * Handles the daily extraction of database logs into CSV files.
 * Extracts logs from the previous day, writes them to a CSV file in the current_week directory,
 * and deletes the extracted records from the database to keep it lightweight.
 *
 * @package Controllers\Logs
 */
class DailyExportController implements ControllerInterface
{
    /**
     * Determines if this controller supports the given route and HTTP method.
     *
     * @param string $chemin The requested route path.
     * @param string $method The HTTP method.
     * @return bool True if the route is '/logs/daily-export' and method is 'GET', false otherwise.
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/logs/daily-export' && $method === 'GET';
    }

    /**
     * Main controller execution method.
     *
     * Validates the security token, queries the previous day's logs,
     * writes them into a CSV file, and purges the exported rows from the database.
     *
     * @return void
     */
    public function control(): void
    {
        $validToken = 'eff686a19e04dd1ac6ebabedb5bd65fbb63c5d5d19980b5f7c261d2527c35a55';

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

            // PHPStan Fix: Verify that $result is strictly an instance of mysqli_result
            if ($result instanceof \mysqli_result && $result->num_rows > 0) {
                $filename = $currentWeekDir . 'logs_' . $yesterday . '.csv';
                $file = fopen($filename, 'w');

                if ($file !== false) {
                    fputcsv($file, ['id', 'user_id', 'action', 'table_concernee', 'element_id', 'details', 'ip_address', 'system_info', 'date_action']);

                    $idsToDelete = [];
                    while ($row = $result->fetch_assoc()) {
                        fputcsv($file, $row);
                        // Ensure ID is cast to int for strict typing
                        if (isset($row['id'])) {
                            $idsToDelete[] = (int) $row['id'];
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
