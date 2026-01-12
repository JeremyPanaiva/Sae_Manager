<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;

/**
 * Deadline Reminder Controller
 *
 * Handles automated deadline reminder emails for students.
 * This controller is called by a cron job via URL to send reminder emails
 * 3 days before SAE submission deadlines.
 *
 * Security: Uses a secret token to prevent unauthorized access.
 *
 * @package Controllers\Sae
 */
class DeadlineReminderController implements ControllerInterface
{
    /**
     * Checks if the current request is supported by this controller
     *
     * @param string $chemin The request path
     * @param string $method The HTTP method
     * @return bool True if path matches /sae/deadline-reminder
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/sae/deadline-reminder';
    }

    /**
     * Main control method - delegates to execute()
     *
     * @return void
     */
    public function control(): void
    {
        $this->execute();
    }

    /**
     * Executes the deadline reminder process
     *
     * Checks authentication token, retrieves SAE attributions with deadlines
     * in 3 days, and sends reminder emails to affected students.
     *
     * URL: /sae/deadline-reminder?token=YOUR_SECRET_TOKEN
     *
     * @return void
     */
    private function execute(): void
    {
        // Set headers for plain text response
        header('Content-Type: text/plain; charset=UTF-8');

        $logPrefix = '[' . date('Y-m-d H:i:s') . ']';

        // Verify secret token for security
        $expectedToken = $this->getSecretToken();
        $providedToken = $_GET['token'] ?? '';

        if (empty($expectedToken)) {
            http_response_code(500);
            echo $logPrefix . " ERREUR: DEADLINE_REMINDER_TOKEN non configuré dans .env\n";
            return;
        }

        if ($providedToken !== $expectedToken) {
            http_response_code(403);
            echo $logPrefix . " ERREUR: Token invalide ou manquant\n";
            return;
        }

        echo $logPrefix . " Script de rappel de deadline démarré\n";

        try {
            // Initialize email service
            $emailService = new EmailService();

            // Get all attributions with deadline in 3 days
            $attributions = SaeAttribution::getAttributionsWithDeadlineIn3Days();

            if (empty($attributions)) {
                echo $logPrefix . " Aucune SAE avec deadline dans 3 jours. Aucun email à envoyer.\n";
                http_response_code(200);
                return;
            }

            echo $logPrefix . " " . count($attributions) . " attribution(s) trouvée(s) avec deadline dans 3 jours\n";

            $successCount = 0;
            $failureCount = 0;

            // Send reminder email to each student
            foreach ($attributions as $attribution) {
                $studentEmail = $attribution['student_email'];
                $studentNom = trim($attribution['student_prenom'] . ' ' . $attribution['student_nom']);
                $saeTitre = $attribution['sae_titre'];
                $dateRendu = $attribution['date_rendu'];
                $responsableNom = trim($attribution['responsable_prenom'] . ' ' . $attribution['responsable_nom']);

                try {
                    $sent = $emailService->sendDeadlineReminderEmail(
                        $studentEmail,
                        $studentNom,
                        $saeTitre,
                        $dateRendu,
                        $responsableNom
                    );

                    if ($sent) {
                        $successCount++;
                        echo $logPrefix . " ✓ Email envoyé à {$studentNom} ({$studentEmail}) pour SAE '{$saeTitre}'\n";
                    } else {
                        $failureCount++;
                        echo $logPrefix . " ✗ Échec d'envoi à {$studentNom} ({$studentEmail})\n";
                    }
                } catch (DataBaseException $e) {
                    $failureCount++;
                    echo $logPrefix . " ✗ Erreur lors de l'envoi à " .
                        "{$studentNom} ({$studentEmail}): " . $e->getMessage() . "\n";
                }

                // Small delay to avoid overwhelming the SMTP server
                usleep(500000); // 0.5 second delay
            }

            echo $logPrefix . " Résumé: {$successCount} email(s) envoyé(s), {$failureCount} échec(s)\n";
            echo $logPrefix . " Script terminé avec succès\n";

            http_response_code(200);
        } catch (DataBaseException $e) {
            http_response_code(500);
            echo $logPrefix . " ERREUR CRITIQUE: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            http_response_code(500);
            echo $logPrefix . " ERREUR INATTENDUE: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Gets the secret token from environment variables
     *
     * @return string The secret token for authentication
     */
    private function getSecretToken(): string
    {
        $token = \Models\Database::parseEnvVar('DEADLINE_REMINDER_TOKEN');
        return $token !== false ? $token : '';
    }
}
