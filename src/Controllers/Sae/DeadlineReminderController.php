<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;

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
        SessionGuard::check();
        $this->execute();
    }

    /**
     * Executes the deadline reminder process
     *
     * Checks authentication token, retrieves SAE attributions with deadlines
     * in 3 days AND 1 day, and sends appropriate reminder emails to affected students.
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

            $totalSuccessCount = 0;
            $totalFailureCount = 0;

            // ========== RAPPEL J-3 ==========
            echo $logPrefix . " --- Vérification des deadlines à J-3 ---\n";
            $attributionsJ3 =
                SaeAttribution::getAttributionsWithDeadlineIn3Days();

            if (!empty($attributionsJ3)) {
                $msg = " attribution(s) trouvée(s) avec deadline dans 3 jours\n";
                echo $logPrefix . " " . count($attributionsJ3) . $msg;

                foreach ($attributionsJ3 as $attribution) {
                    /** @var array<string, string|null> $attribution */
                    $studentEmail = (string) ($attribution['student_email'] ?? '');

                    $prenom = (string) ($attribution['student_prenom'] ?? '');
                    $nom = (string) ($attribution['student_nom'] ?? '');
                    $studentNom = trim($prenom . ' ' . $nom);

                    $saeTitre = (string) ($attribution['sae_titre'] ?? '');
                    $dateRendu = (string) ($attribution['date_rendu'] ?? '');

                    $respPrenom = (string) ($attribution['responsable_prenom'] ?? '');
                    $respNom = (string) ($attribution['responsable_nom'] ?? '');
                    $responsableNom = trim($respPrenom . ' ' . $respNom);

                    try {
                        $sent = $emailService->sendDeadlineReminderEmail(
                            $studentEmail,
                            $studentNom,
                            $saeTitre,
                            $dateRendu,
                            $responsableNom
                        );

                        if ($sent) {
                            $totalSuccessCount++;
                            echo $logPrefix . " ✓ [J-3] Email envoyé à {$studentNom} " .
                                "({$studentEmail}) pour SAE '{$saeTitre}'\n";
                        } else {
                            $totalFailureCount++;
                            echo $logPrefix . " ✗ [J-3] Échec d'envoi à {$studentNom} ({$studentEmail})\n";
                        }
                    } catch (DataBaseException $e) {
                        $totalFailureCount++;
                        echo $logPrefix . " ✗ [J-3] Erreur lors de l'envoi à {$studentNom} " .
                            "({$studentEmail}): " . $e->getMessage() . "\n";
                    }

                    usleep(500000); // 0.5 second delay
                }
            } else {
                echo $logPrefix . " Aucune SAE avec deadline dans 3 jours\n";
            }

            // ========== RAPPEL J-1 (URGENT) ==========
            echo $logPrefix . " --- Vérification des deadlines à J-1 (URGENT) ---\n";
            $attributionsJ1 =
                SaeAttribution::getAttributionsWithDeadlineIn1Day();

            if (!empty($attributionsJ1)) {
                $msg = " attribution(s) trouvée(s) avec deadline DEMAIN (23h59)\n";
                echo $logPrefix . " " . count($attributionsJ1) . $msg;

                foreach ($attributionsJ1 as $attribution) {
                    /** @var array<string, string|null> $attribution */
                    $studentEmail = (string) ($attribution['student_email'] ?? '');

                    $prenom = (string) ($attribution['student_prenom'] ?? '');
                    $nom = (string) ($attribution['student_nom'] ?? '');
                    $studentNom = trim($prenom . ' ' . $nom);

                    $saeTitre = (string) ($attribution['sae_titre'] ?? '');
                    $dateRendu = (string) ($attribution['date_rendu'] ?? '');

                    $respPrenom = (string) ($attribution['responsable_prenom'] ?? '');
                    $respNom = (string) ($attribution['responsable_nom'] ?? '');
                    $responsableNom = trim($respPrenom . ' ' . $respNom);

                    try {
                        $sent = $emailService->sendUrgentDeadlineReminderEmail(
                            $studentEmail,
                            $studentNom,
                            $saeTitre,
                            $dateRendu,
                            $responsableNom
                        );

                        if ($sent) {
                            $totalSuccessCount++;
                            echo $logPrefix . " ✓ [J-1 URGENT] Email envoyé à {$studentNom} " .
                                "({$studentEmail}) pour SAE '{$saeTitre}'\n";
                        } else {
                            $totalFailureCount++;
                            echo $logPrefix . " ✗ [J-1 URGENT] Échec d'envoi à {$studentNom} ({$studentEmail})\n";
                        }
                    } catch (DataBaseException $e) {
                        $totalFailureCount++;
                        echo $logPrefix . " ✗ [J-1 URGENT] Erreur lors de l'envoi à {$studentNom} " .
                            "({$studentEmail}): " . $e->getMessage() . "\n";
                    }

                    usleep(500000); // 0.5 second delay
                }
            } else {
                echo $logPrefix . " Aucune SAE avec deadline demain\n";
            }

            echo $logPrefix . " ==========================================\n";
            echo $logPrefix .
                " Résumé TOTAL: {$totalSuccessCount} email(s) envoyé(s), {$totalFailureCount} échec(s)\n";
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
