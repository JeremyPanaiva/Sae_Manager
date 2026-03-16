<?php

namespace Controllers\Logs;

use Controllers\ControllerInterface;
use ZipArchive;

/**
 * Class WeeklyArchiveController
 */
class WeeklyArchiveController implements ControllerInterface
{
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/logs/weekly-archive' && $method === 'GET';
    }

    public function control(): void
    {
        // Token récupéré depuis les variables d'environnement
        $validToken = getenv('TOKEN') ?: ($_ENV['TOKEN'] ?? null);

        if (!$validToken || !isset($_GET['token']) || $_GET['token'] !== $validToken) {
            http_response_code(403);
            die('Access denied.');
        }

        $currentWeekDir = __DIR__ . '/../../../logs/current_week/';
        $archiveDir = __DIR__ . '/../../../logs/archive/';

        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        // 1️⃣ Compression des CSV
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

                // Suppression des CSV après archivage
                foreach ($csvFiles as $file) {
                    unlink($file);
                }

                echo "Archive created successfully: $zipFilename\n";
            } else {
                echo "Failed to create the ZIP archive.\n";
            }

        } else {
            echo "No CSV files to archive this week.\n";
        }

        // 2️⃣ Suppression des archives > 1 an
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
