<?php
namespace Models\Sae;

use Models\User\EmailService;

class AutoReminder
{
    private const TRACKING_FILE = __DIR__ . '/../../../last_reminder_check.json';

    /**
     * Récupère les délais de rappel configurés
     */
    public static function getReminderDelays(): array
    {
        $data = self::getTrackingData();
        return $data['reminder_delays'] ?? [10, 7, 3, 1];
    }

    /**
     * Configure les délais de rappel personnalisés
     */
    public static function setReminderDelays(array $delays): void
    {
        $data = self::getTrackingData();

        // Valider et trier les délais
        $delays = array_filter($delays, fn($d) => is_numeric($d) && $d > 0 && $d <= 30);
        $delays = array_unique($delays);
        sort($delays, SORT_NUMERIC);
        array_reverse($delays); // Du plus grand au plus petit

        $data['reminder_delays'] = array_values($delays);
        self::saveTrackingData($data);
    }

    /**
     * Vérifie et envoie les rappels automatiques
     */
    public static function checkAndSendReminders(): void
    {
        if (!self::shouldCheck()) {
            return;
        }

        try {
            self::processReminders();
            self::updateLastCheck();
        } catch (\Exception $e) {
            error_log("Erreur AutoReminder: " . $e->getMessage());
        }
    }

    /**
     * Envoie immédiatement des rappels pour un délai spécifique
     */
    public static function sendImmediateReminders(int $days): array
    {
        $stats = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'total' => 0
        ];

        try {
            $saesDue = SaeAttribution::getSaesDueInDays($days);
            $stats['total'] = count($saesDue);

            if ($stats['total'] === 0) {
                return $stats;
            }

            $data = self::getTrackingData();
            $today = date('Y-m-d');

            foreach ($saesDue as $sae) {
                $uniqueKey = self::generateReminderKey($sae, $days);

                // Vérifier si déjà envoyé aujourd'hui
                if (isset($data['reminders_sent'][$uniqueKey]) &&
                    $data['reminders_sent'][$uniqueKey] === $today) {
                    $stats['skipped']++;
                    continue;
                }

                // Envoyer le rappel
                if (self::sendReminder($sae, $days)) {
                    $data['reminders_sent'][$uniqueKey] = $today;
                    $stats['sent']++;
                } else {
                    $stats['failed']++;
                }
            }

            self::saveTrackingData($data);

        } catch (\Exception $e) {
            error_log("Erreur envoi immédiat J-{$days}: " . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Vérifie s'il faut lancer la vérification (une fois par jour)
     */
    private static function shouldCheck(): bool
    {
        if (!file_exists(self::TRACKING_FILE)) {
            self::initTrackingFile();
            return true;
        }

        $data = json_decode(file_get_contents(self::TRACKING_FILE), true);
        $lastCheck = $data['last_check'] ?? '';
        $today = date('Y-m-d');

        return $lastCheck !== $today;
    }

    /**
     * Traite tous les rappels programmés
     */
    private static function processReminders(): void
    {
        $data = self::getTrackingData();
        $today = date('Y-m-d');
        $delays = self::getReminderDelays();

        foreach ($delays as $days) {
            try {
                $saesDue = SaeAttribution::getSaesDueInDays($days);

                foreach ($saesDue as $sae) {
                    $uniqueKey = self::generateReminderKey($sae, $days);

                    // Vérifier si déjà envoyé
                    if (isset($data['reminders_sent'][$uniqueKey]) &&
                        $data['reminders_sent'][$uniqueKey] === $today) {
                        continue;
                    }

                    // Envoyer le rappel
                    if (self::sendReminder($sae, $days)) {
                        $data['reminders_sent'][$uniqueKey] = $today;
                    }
                }

            } catch (\Exception $e) {
                error_log("Erreur rappels J-{$days}: " . $e->getMessage());
            }
        }

        // Nettoyer les anciens rappels (> 30 jours)
        $cutoffDate = date('Y-m-d', strtotime('-30 days'));
        if (isset($data['reminders_sent'])) {
            foreach ($data['reminders_sent'] as $k => $d) {
                if ($d < $cutoffDate) {
                    unset($data['reminders_sent'][$k]);
                }
            }
        }

        self::saveTrackingData($data);
    }

    /**
     * Envoie un rappel individuel
     */
    private static function sendReminder(array $sae, int $days): bool
    {
        $studentEmail = $sae['student_email'] ?? '';

        if (empty($studentEmail)) {
            return false;
        }

        try {
            $studentName = trim(($sae['student_prenom'] ?? '') . ' ' . ($sae['student_nom'] ?? ''));
            $saeTitle = $sae['sae_titre'] ?? 'SAE';
            $dateRendu = $sae['date_rendu'] ?? '';
            $responsableName = trim(($sae['responsable_prenom'] ?? '') . ' ' . ($sae['responsable_nom'] ?? ''));

            $emailService = new EmailService();
            $emailService->sendDeadlineReminderNotification(
                $studentEmail,
                $studentName,
                $saeTitle,
                $dateRendu,
                $responsableName,
                $days
            );

            error_log("✅ Rappel J-{$days} envoyé à {$studentName}");
            return true;

        } catch (\Exception $e) {
            error_log("❌ Erreur envoi rappel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère une clé unique pour un rappel
     */
    private static function generateReminderKey(array $sae, int $days): string
    {
        return md5(
            ($sae['sae_id'] ?? '') . '_' .
            ($sae['student_id'] ?? '') . '_' .
            ($sae['date_rendu'] ?? '') . '_' .
            $days
        );
    }

    /**
     * Met à jour la date de dernière vérification
     */
    private static function updateLastCheck(): void
    {
        $data = self::getTrackingData();
        $data['last_check'] = date('Y-m-d');
        self::saveTrackingData($data);
    }

    /**
     * Récupère les données de tracking
     */
    private static function getTrackingData(): array
    {
        if (!file_exists(self::TRACKING_FILE)) {
            return [
                'last_check' => '',
                'reminders_sent' => [],
                'reminder_delays' => [10, 7, 3, 1]
            ];
        }

        $content = file_get_contents(self::TRACKING_FILE);
        $data = json_decode($content, true) ?? [
            'last_check' => '',
            'reminders_sent' => [],
            'reminder_delays' => [10, 7, 3, 1]
        ];

        // S'assurer que reminder_delays existe
        if (!isset($data['reminder_delays'])) {
            $data['reminder_delays'] = [10, 7, 3, 1];
        }

        return $data;
    }

    /**
     * Sauvegarde les données de tracking
     */
    private static function saveTrackingData(array $data): void
    {
        file_put_contents(
            self::TRACKING_FILE,
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Initialise le fichier de tracking
     */
    private static function initTrackingFile(): void
    {
        $data = [
            'last_check' => '',
            'reminders_sent' => [],
            'reminder_delays' => [10, 7, 3, 1]
        ];
        self::saveTrackingData($data);
    }
}