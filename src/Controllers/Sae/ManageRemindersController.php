<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\AutoReminder;

class ManageRemindersController implements ControllerInterface
{
    public const PATH = '/sae/manage-reminders';

    public static function support(string $uri, string $method): bool
    {
        return $uri === self::PATH && $method === 'POST';
    }

    public function control(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'send_now':
                    $this->handleSendNow();
                    break;

                case 'configure_auto':
                    $this->handleConfigureAuto();
                    break;

                default:
                    $_SESSION['error_message'] = "Action non reconnue";
            }

        } catch (\Exception $e) {
            error_log("Erreur ManageRemindersController: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Envoie immédiat de rappels
     */
    private function handleSendNow(): void
    {
        $days = isset($_POST['days']) ? (int)$_POST['days'] : 3;

        if ($days < 1 || $days > 30) {
            $_SESSION['error_message'] = "Le délai doit être entre 1 et 30 jours";
            return;
        }

        $stats = AutoReminder::sendImmediateReminders($days);

        $dayWord = $days == 1 ? 'jour' : 'jours';

        if ($stats['total'] === 0) {
            $_SESSION['info_message'] = "Aucune SAE à échéance dans {$days} {$dayWord}";
        } elseif ($stats['failed'] > 0) {
            $_SESSION['warning_message'] = "Rappels J-{$days} : {$stats['sent']} envoyés, {$stats['failed']} échoués, {$stats['skipped']} ignorés";
        } else {
            $_SESSION['success_message'] = "✅ {$stats['sent']} rappel(s) envoyé(s) avec succès pour J-{$days}";
        }
    }

    /**
     * Configure les rappels automatiques
     */
    private function handleConfigureAuto(): void
    {
        $delays = $_POST['delays'] ?? [];

        if (!is_array($delays) || empty($delays)) {
            $_SESSION['error_message'] = "Veuillez sélectionner au moins un délai";
            return;
        }

        // Convertir en entiers
        $delays = array_map('intval', $delays);

        // Valider
        foreach ($delays as $delay) {
            if ($delay < 1 || $delay > 30) {
                $_SESSION['error_message'] = "Les délais doivent être entre 1 et 30 jours";
                return;
            }
        }

        AutoReminder::setReminderDelays($delays);

        $_SESSION['success_message'] = "Configuration des rappels automatiques mise à jour !";
    }
}