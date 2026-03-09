<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User; // Assure-toi que ce modèle existe
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;

/**
 * Inactive User Cleanup Controller
 *
 * Supprime ou anonymise les comptes inactifs depuis un certain temps
 * (ex: 3 ans / 36 mois) pour être en conformité avec le RGPD et la CNIL.
 *
 * @package Controllers\User
 */
class InactiveUserCleanupController implements ControllerInterface
{
    // Durée d'inactivité avant suppression (en mois)
    private const INACTIVITY_MONTHS = 36;

    public static function support(string $chemin, string $method): bool
    {
        return $chemin === '/user/cleanup-inactive';
    }

    public function control(): void
    {
        // Si ton architecture l'exige
        SessionGuard::check();
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

        echo $logPrefix . " Script de nettoyage RGPD (Comptes inactifs) démarré\n";
        echo $logPrefix . " --- Seuil d'inactivité : " . self::INACTIVITY_MONTHS . " mois ---\n";

        try {
            // Appel à une méthode du modèle User qui gère la suppression
            // Cette méthode devrait renvoyer le nombre de comptes supprimés
            $deletedCount = User::deleteInactiveAccounts(self::INACTIVITY_MONTHS);

            if ($deletedCount > 0) {
                echo $logPrefix . " ✓ SUCCÈS : {$deletedCount} compte(s) inactif(s) supprimé(s).\n";
            } else {
                echo $logPrefix . " ℹ INFORMATION : Aucun compte inactif trouvé.\n";
            }

            echo $logPrefix . " ==========================================\n";
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
