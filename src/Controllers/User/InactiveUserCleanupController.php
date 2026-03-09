<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;

/**
 * Inactive User Cleanup Controller
 *
 * Handles the GDPR/CNIL compliance process for inactive accounts.
 * This script is designed to be executed via a monthly cron job.
 * * Step 1: Sends a warning email to users inactive for 35 months.
 * Step 2: Permanently deletes accounts inactive for 36 months or more.
 *
 * @package Controllers\User
 */
class InactiveUserCleanupController implements ControllerInterface
{
    /** * @var int Inactivity threshold for warning email (in months) */
    private const WARNING_MONTHS = 35;

    /** * @var int Inactivity threshold for account deletion (in months) */
    private const DELETION_MONTHS = 36;

    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/user/cleanup-inactive';
    }

    public function control(): void
    {
        $this->execute();
    }

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

            // ========== ETAPE 1 : AVERTISSEMENTS (35 MOIS) ==========
            echo $logPrefix . " --- Vérification des comptes inactifs depuis " . self::WARNING_MONTHS . " mois ---\n";
            $usersToWarn = User::getUsersForInactivityWarning(self::WARNING_MONTHS);

            $warningSuccess = 0;
            $warningFailure = 0;

            if (!empty($usersToWarn)) {
                echo $logPrefix . " " . count($usersToWarn) . " utilisateur(s) à avertir trouvé(s).\n";

                foreach ($usersToWarn as $user) {
                    $email = (string)($user['mail'] ?? '');
                    $prenom = (string)($user['prenom'] ?? '');
                    $nom = (string)($user['nom'] ?? '');
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

                    usleep(500000); // 0.5 second delay to prevent SMTP throttling
                }
            } else {
                echo $logPrefix . " Aucun utilisateur à avertir ce mois-ci.\n";
            }

            // ========== ETAPE 2 : SUPPRESSIONS (36 MOIS) ==========
            echo $logPrefix . " --- Suppression des comptes inactifs depuis " . self::DELETION_MONTHS . " mois ---\n";
            $deletedCount = User::deleteInactiveAccounts(self::DELETION_MONTHS);

            if ($deletedCount > 0) {
                echo $logPrefix . " ✓ SUCCÈS : {$deletedCount} compte(s) inactif(s) supprimé(s) définitivement.\n";
            } else {
                echo $logPrefix . " ℹ INFORMATION : Aucun compte à supprimer ce mois-ci.\n";
            }

            // ========== RESUME ==========
            echo $logPrefix . " ==========================================\n";
            echo $logPrefix . " Résumé Avertissements : {$warningSuccess} envoyé(s), {$warningFailure} échec(s)\n";
            echo $logPrefix . " Résumé Suppressions   : {$deletedCount} compte(s) supprimé(s)\n";
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

    private function getSecretToken(): string
    {
        $token = \Models\Database::parseEnvVar('TOKEN');
        return $token !== false ? $token : '';
    }
}
