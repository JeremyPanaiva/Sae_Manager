<?php

namespace Controllers\Logs;

use Controllers\ControllerInterface;
use ZipArchive;

/**
 * Class WeeklyArchiveController
 *
 * Handles the weekly archiving of daily CSV log files.
 * Compresses all CSV files from the current_week directory into a single ZIP file,
 * stores it in the archive directory, and cleans up old archives exceeding the retention policy (1 year).
 *
 * @package Controllers\Logs
 */
class WeeklyArchiveController implements ControllerInterface
{
    /**
     * Determines if this controller supports the given route and HTTP method.
     *
     * @param string $chemin The requested route path.
     * @param string $method The HTTP method.
     * @return bool True if the route is '/cron/weekly-archive' and the method is 'GET', false otherwise.
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/cron/weekly-archive' && $method === 'GET';
    }

    /**
     * Main controller execution method.
     *
     * Validates the security token via environment variables, compresses existing CSV files
     * into a ZIP archive, deletes the original CSV files, and removes ZIP files older than 365 days.
     *
     * @return void
     */
    public function control(): void
    {
        $validToken = $_ENV['TOKEN'] ?? getenv('TOKEN');

        if (empty($validToken) || !isset($_GET['token']) || $_GET['token'] !== $validToken) {
            http_response_code(403);
            die('Access denied.');
        }

        $currentWeekDir = __DIR__ . '/../../../logs/current_week/';
        $archiveDir = __DIR__ . '/../../../logs/archive/';

        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        $csvFiles = glob($currentWeekDir . '*.csv');

        if ($csvFiles !== false && !empty($csvFiles)) {
            $zip = new ZipArchive();
            $weekNumber = date('W', strtotime('-1 week'));
            $year = date('Y', strtotime('-1 week'));
            $zipFilename = $archiveDir . "semaine_{$weekNumber}_{$year}.zip";

            if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($csvFiles as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                foreach ($csvFiles as $file) {
                    unlink($file);
                }
                echo "Archive $zipFilename created successfully.\n";
            } else {
                echo "Failed to create the ZIP archive.\n";
            }
        } else {
            echo "No CSV files to archive this week.\n";
        }

        // 2. Clean up archives older than 1 year (365 days)
        $zipArchives = glob($archiveDir . '*.zip');
        $oneYearAgo = time() - (365 * 24 * 60 * 60);
        $deletedFiles = 0;

        if ($zipArchives !== false) {
            foreach ($zipArchives as $file) {
                if (is_file($file) && filemtime($file) !== false && filemtime($file) < $oneYearAgo) {
                    unlink($file);
                    $deletedFiles++;
                }
            }
        }

        if ($deletedFiles > 0) {
            echo "$deletedFiles archive(s) older than one year have been deleted.";
        }
    }
}
