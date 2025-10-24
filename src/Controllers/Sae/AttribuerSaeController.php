<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\Sae;
use Models\User\User;
use Models\User\EmailService;
use Shared\Exceptions\SaeAlreadyAssignedException;
use Shared\Exceptions\StudentAlreadyAssignedException;

class AttribuerSaeController implements ControllerInterface
{
    public const PATH = '/attribuer_sae';

    public function control()
    {
        // Vérifie que la requête est POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        // Vérifie que l'utilisateur est un responsable
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        $saeId = intval($_POST['sae_id'] ?? 0);
        $etudiants = $_POST['etudiants'] ?? [];
        $responsableId = $_SESSION['user']['id'];

        try {
            // Vérifie la connexion à la base
            SaeAttribution::checkDatabaseConnection();

            // Attribuer les étudiants
            SaeAttribution::assignStudentsToSae($saeId, array_map('intval', $etudiants), $responsableId);

            // Récupérer les informations de la SAE
            $saeInfo = Sae::getById($saeId);

            if (!$saeInfo) {
                throw new \Exception("SAE non trouvée");
            }

            $saeTitre = $saeInfo['titre'] ?? 'la SAE';
            $saeDescription = $saeInfo['description'] ?? '';
            $clientId = $saeInfo['client_id'] ?? null;

            // LOG pour debug
            error_log("SAE ID: {$saeId}");
            error_log("SAE Titre: {$saeTitre}");
            error_log("Client ID trouvé: " . ($clientId ?? 'NULL'));

            // Récupérer les informations du client
            $clientNom = 'Client';
            $clientEmail = '';

            if ($clientId) {
                $clientInfo = User::getById($clientId);
                error_log("Client Info: " . json_encode($clientInfo));

                if ($clientInfo) {
                    $clientNom = trim(($clientInfo['prenom'] ?? '') . ' ' . ($clientInfo['nom'] ?? ''));
                    $clientEmail = $clientInfo['mail'] ?? '';
                    error_log("Client Email trouvé: {$clientEmail}");
                } else {
                    error_log("Client non trouvé pour l'ID: {$clientId}");
                }
            } else {
                error_log("Aucun client_id dans la SAE");
            }

            // Récupérer les informations du responsable
            $responsableInfo = User::getById($responsableId);
            $responsableNom = $responsableInfo ? trim(($responsableInfo['prenom'] ?? '') . ' ' . ($responsableInfo['nom'] ?? '')) : 'Responsable';

            // Récupérer la date de rendu
            $dateRendu = '';
            $attributions = SaeAttribution::getAttributionsBySae($saeId);
            if (!empty($attributions)) {
                $dateRendu = $attributions[0]['date_rendu'] ?? '';
            }

            // Récupérer les noms des étudiants pour le message et envoyer les emails
            $studentNames = [];

            foreach ($etudiants as $studentId) {
                $student = User::getById((int)$studentId);
                if ($student) {
                    $studentFullName = trim(($student['prenom'] ?? '') . ' ' . ($student['nom'] ?? ''));
                    $studentNames[] = $studentFullName;
                    $studentEmail = $student['mail'] ?? '';

                    error_log("Étudiant: {$studentFullName} - Email: {$studentEmail}");

                    //Envoyer un email à l'étudiant - NOUVELLE INSTANCE
                    if (!empty($studentEmail)) {
                        try {
                            error_log("Création d'une nouvelle instance EmailService pour l'étudiant");
                            $emailServiceStudent = new EmailService();

                            $emailServiceStudent->sendStudentAssignmentNotification(
                                $studentEmail,
                                $studentFullName,
                                $saeTitre,
                                $saeDescription,
                                $responsableNom,
                                $clientNom,
                                $dateRendu
                            );
                            error_log("Email envoyé à l'étudiant: {$studentEmail}");

                            // Libérer la mémoire
                            unset($emailServiceStudent);

                        } catch (\Exception $e) {
                            error_log("Erreur lors de l'envoi de l'email à l'étudiant {$studentEmail}: " . $e->getMessage());
                            error_log("Stack trace: " . $e->getTraceAsString());
                        }
                    } else {
                        error_log("Pas d'email pour l'étudiant: {$studentFullName}");
                    }

                    //Envoyer un email au client - NOUVELLE INSTANCE
                    if (!empty($clientEmail)) {
                        try {
                            error_log("Création d'une nouvelle instance EmailService pour le client");
                            error_log("Tentative d'envoi email au client: {$clientEmail} pour étudiant: {$studentFullName}");

                            // NOUVELLE INSTANCE pour chaque email
                            $emailServiceClient = new EmailService();

                            $emailServiceClient->sendClientStudentAssignmentNotification(
                                $clientEmail,
                                $clientNom,
                                $saeTitre,
                                $studentFullName,
                                $responsableNom
                            );

                            error_log("✅ Email envoyé au client: {$clientEmail}");

                            // Libérer la mémoire
                            unset($emailServiceClient);

                        } catch (\Exception $e) {
                            error_log("ERREUR lors de l'envoi de l'email au client {$clientEmail}: " . $e->getMessage());
                            error_log("Stack trace: " . $e->getTraceAsString());
                        }
                    } else {
                        error_log("Pas d'email client disponible. Client: {$clientNom}, Email: vide");
                    }

                    //Pour éviter les problèmes SMTP
                    usleep(500000); // 0.5 seconde de pause
                }
            }

            // Construire le message de succès
            $nbEtudiants = count($studentNames);
            if ($nbEtudiants === 1) {
                $_SESSION['success_message'] = "L'étudiant « {$studentNames[0]} » a été attribué avec succès à la SAE « $saeTitre ». Des notifications par email ont été envoyées.";
            } else {
                $listeEtudiants = implode(', ', array_slice($studentNames, 0, -1)) . ' et ' . end($studentNames);
                $_SESSION['success_message'] = "$nbEtudiants étudiants ont été attribués avec succès à la SAE « $saeTitre » : $listeEtudiants. Des notifications par email ont été envoyées.";
            }

            header('Location: /sae');
            exit();

        } catch (\Shared\Exceptions\DataBaseException $e) {
            $_SESSION['error_message'] = $e->getMessage(); // "Unable to connect to the database"
        } catch (SaeAlreadyAssignedException $e) {
            $_SESSION['error_message'] = "Impossible d'attribuer la SAE « {$e->getSae()} » : elle a déjà été attribuée par le responsable « {$e->getResponsable()} ».";
        } catch (StudentAlreadyAssignedException $e) {
            $_SESSION['error_message'] = "L'étudiant « {$e->getStudent()} » est déjà assigné à la SAE « {$e->getSae()} ».";
        } catch (\Exception $e) {
            error_log("❌ Erreur générale dans AttribuerSaeController: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'affectation.";
        }

// Redirection avec message d'erreur
        header('Location: /sae');
        exit();

    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}