<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\User\EmailService;

class SendRemindersController implements ControllerInterface
{
    public const PATH = '/sae/send-reminders';


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

        try {
            // Récupérer le nombre de jours depuis le formulaire (par défaut 3)
            $daysBefore = isset($_POST['days_before']) ? (int)$_POST['days_before'] : 3;

            // Validation : entre 1 et 30 jours
            if ($daysBefore < 1 || $daysBefore > 30) {
                $daysBefore = 3;
            }

            $saesDue = SaeAttribution::getSaesDueInDays($daysBefore);

            $totalSaes = count($saesDue);
            $emailsSent = 0;
            $emailsFailed = 0;
            $emailsSkipped = 0;

            if ($totalSaes === 0) {
                $dayWord = $daysBefore == 1 ? 'jour' : 'jours';
                $_SESSION['info_message'] = "Aucun rappel à envoyer pour les échéances dans {$daysBefore} {$dayWord}.";
                header('Location: /dashboard');
                exit();
            }

            foreach ($saesDue as $sae) {
                $studentName = trim(($sae['student_prenom'] ?? '') . ' ' . ($sae['student_nom'] ?? ''));
                $studentEmail = $sae['student_email'] ?? '';
                $saeTitle = $sae['sae_titre'] ?? 'SAE';
                $dateRendu = $sae['date_rendu'] ?? '';
                $responsableName = trim(($sae['responsable_prenom'] ?? '') . ' ' . ($sae['responsable_nom'] ?? ''));

                if (empty($studentEmail)) {
                    error_log("Aucun email pour l'étudiant: {$studentName}");
                    $emailsSkipped++;
                    continue;
                }

                try {
                    $emailService = new EmailService();

                    // Envoyer avec le nombre de jours spécifique
                    $emailService->sendDeadlineReminderNotification(
                        $studentEmail,
                        $studentName,
                        $saeTitle,
                        $dateRendu,
                        $responsableName,
                        $daysBefore
                    );

                    $emailsSent++;
                    unset($emailService);
                    usleep(500000);

                } catch (\Exception $e) {
                    error_log("Erreur envoi rappel SAE à {$studentEmail}: " . $e->getMessage());
                    $emailsFailed++;
                }
            }

            $dayWord = $daysBefore == 1 ? 'jour' : 'jours';
            if ($emailsFailed > 0) {
                $_SESSION['warning_message'] = "Rappels ({$daysBefore} {$dayWord}) : Envoyés={$emailsSent} / Échoués={$emailsFailed} / Ignorés={$emailsSkipped}";
            } else {
                $_SESSION['success_message'] = "Rappels envoyés avec succès : {$emailsSent} email(s) pour les échéances dans {$daysBefore} {$dayWord}";
            }

            header('Location: /dashboard');
            exit();

        } catch (\Exception $e) {
            error_log("Erreur fatale dans SendRemindersController: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors de l'envoi des rappels : " . $e->getMessage();
            header('Location: /dashboard');
            exit();
        }
    }
}