<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;

/**
 * Inactive User Cleanup Controller
 *
 * Handles the GDPR/CNIL compliance process for inactive accounts.
 * This script is designed to be executed via a monthly cron job.
 * * Step 1: Sends a warning email to users inactive for 35 months.
 * Step 2: Sends a deletion confirmation email, then permanently deletes accounts
 * inactive for 36 months or more.
 *
 * @package Controllers\User
 */
class InactiveUserCleanupController implements ControllerInterface
{
    /** @var int Inactivity threshold for the warning email (in months) */
    private const WARNING_MONTHS = 35;

    /** @var int Inactivity threshold for account deletion (in months) */
    private const DELETION_MONTHS = 36;

    /**
     * Checks if this controller supports the current request route.
     *
     * @param string $chemin The requested path
     * @param string $method The HTTP method
     * @return bool True if the path is supported
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/user/cleanup-inactive';
    }

    /**
     * Main control method that delegates to execute().
     *
     * @return void
     */
    public function control(): void
    {
        $this->execute();
    }

    /**
     * Executes the cleanup and warning process.
     *
     * Validates the security token, sends warning emails to accounts
     * inactive for 35 months, notifies users slated for deletion,
     * and permanently removes accounts inactive for 36 months.
     *
     * @return void
     */
    private function execute(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        $logPrefix = '[' . date('Y-m-d H:i:s') . ']';

        $expectedToken = $this->getSecretToken();
        $providedToken = $_GET['token'] ?? '';

        if (empty($expectedToken)) {
            http_response_code(500);
            echo $logPrefix . " ERREUR: TOKEN non configuré dans .env\n";
            return;
        }

        if ($providedToken !== $expectedToken) {
            http_response_code(403);
            echo $logPrefix . " ERREUR: Token invalide ou manquant\n";
            return;
        }

        echo $logPrefix . " Démarrage du script de conformité RGPD (Inactivité)\n";

        try {
            $emailService = new EmailService();


            // STEP 1: WARNINGS (35 MONTHS INACTIVE)
            echo $logPrefix . " --- Vérification des comptes inactifs depuis " . self::WARNING_MONTHS . " mois ---\n";
            $usersToWarn = User::getUsersForInactivityWarning(self::WARNING_MONTHS);

            $warningSuccess = 0;
            $warningFailure = 0;

            if (!empty($usersToWarn)) {
                echo $logPrefix . " " . count($usersToWarn) . " utilisateur(s) à avertir trouvé(s).\n";

                foreach ($usersToWarn as $user) {
                    $email  = isset($user['mail']) && is_string($user['mail']) ? $user['mail'] : '';
                    $prenom = isset($user['prenom']) && is_string($user['prenom']) ? $user['prenom'] : '';
                    $nom    = isset($user['nom']) && is_string($user['nom']) ? $user['nom'] : '';

                    $fullName = trim($prenom . ' ' . $nom);

                    try {
                        $sent = $emailService->sendInactiveAccountWarningEmail($email, $fullName);
                        if ($sent) {
                            $warningSuccess++;
                            echo $logPrefix . " ✓ [AVERTISSEMENT] Email envoyé à {$fullName} ({$email})\n";
                        } else {
                            $warningFailure++;
                            echo $logPrefix . " ✗ [AVERTISSEMENT] Échec d'envoi à {$fullName} ({$email})\n";
                        }
                    } catch (\Exception $e) {
                        $warningFailure++;
                        echo $logPrefix . " ✗ [AVERTISSEMENT] Erreur pour {$email}: " . $e->getMessage() . "\n";
                    }


                    usleep(500000);
                }
            } else {
                echo $logPrefix . " Aucun utilisateur à avertir ce mois-ci.\n";
            }

            // ==========================================================
            // STEP 2: DELETIONS & NOTIFICATIONS (36 MONTHS INACTIVE)
            // ==========================================================
            echo $logPrefix . " --- Suppression des comptes inactifs depuis " . self::DELETION_MONTHS . " mois ---\n";
            $usersToDelete = User::getUsersForDeletion(self::DELETION_MONTHS);

            $deletionMailSuccess = 0;
            $deletionMailFailure = 0;
            $deletedCount = 0;

            if (!empty($usersToDelete)) {
                echo $logPrefix . " " . count($usersToDelete) . " utilisateur(s) à supprimer trouvé(s).\n";


                foreach ($usersToDelete as $user) {
                    $email  = isset($user['mail']) && is_string($user['mail']) ? $user['mail'] : '';
                    $prenom = isset($user['prenom']) && is_string($user['prenom']) ? $user['prenom'] : '';
                    $nom    = isset($user['nom']) && is_string($user['nom']) ? $user['nom'] : '';

                    $fullName = trim($prenom . ' ' . $nom);

                    try {
                        $sent = $emailService->sendAccountDeletedNotificationEmail($email, $fullName);
                        if ($sent) {
                            $deletionMailSuccess++;
                            echo $logPrefix . " ✓ [SUPPRESSION] Email d'adieu envoyé à {$fullName} ({$email})\n";
                        } else {
                            $deletionMailFailure++;
                            echo $logPrefix . " ✗ [SUPPRESSION] Échec d'envoi à {$fullName} ({$email})\n";
                        }
                    } catch (\Exception $e) {
                        $deletionMailFailure++;
                        echo $logPrefix . " ✗ [SUPPRESSION] Erreur mail pour {$email}: " . $e->getMessage() . "\n";
                    }


                    usleep(500000);
                }

                // 2.B: Actual database deletion process
                $deletedCount = User::deleteInactiveAccounts(self::DELETION_MONTHS);
                echo $logPrefix . " ✓ SUCCÈS : {$deletedCount} compte(s) inactif(s) supprimé(s)
                 définitivement de la base.\n";
            } else {
                echo $logPrefix . " ℹ INFORMATION : Aucun compte à supprimer ce mois-ci.\n";
            }


            echo $logPrefix . " Résumé Avertissements : {$warningSuccess} envoyé(s), {$warningFailure} échec(s)\n";
            echo $logPrefix . " Résumé Emails Adieu   : {$deletionMailSuccess} envoyé(s),
             {$deletionMailFailure} échec(s)\n";
            echo $logPrefix . " Résumé Suppressions   : {$deletedCount} compte(s) supprimé(s) en BDD\n";
            echo $logPrefix . " Script terminé avec succès\n";

            http_response_code(200);
        } catch (DataBaseException $e) {
            http_response_code(500);
            echo $logPrefix . " ERREUR CRITIQUE BDD: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            http_response_code(500);
            echo $logPrefix . " ERREUR INATTENDUE: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Retrieves the secret token from environment variables.
     *
     * @return string The configured secret token
     */
    private function getSecretToken(): string
    {
        $token = \Models\Database::parseEnvVar('TOKEN');
        return $token !== false ? $token : '';
    }
}
