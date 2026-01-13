<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\Sae;
use Models\User\User;
use Models\User\EmailService;
use Shared\Exceptions\SaeAlreadyAssignedException;
use Shared\Exceptions\StudentAlreadyAssignedException;

/**
 * SAE assignment controller
 *
 * Handles the assignment of students to SAE (Situation d'Apprentissage et d'Évaluation)
 * by supervisors (responsables). Sends email notifications to assigned students and
 * the client who created the SAE.
 *
 * @package Controllers\Sae
 */
class AttribuerSaeController implements ControllerInterface
{
    /**
     * SAE assignment route path
     *
     * @var string
     */
    public const PATH = '/attribuer_sae';

    /**
     * Main controller method
     *
     * Validates supervisor authentication, assigns students to a SAE,
     * and sends email notifications to all involved parties (students and client).
     *
     * @return void
     */
    public function control()
    {
        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        // Verify user is authenticated as a supervisor
        if (
            !isset($_SESSION['user']) ||
            !is_array($_SESSION['user']) ||
            !isset($_SESSION['user']['role']) ||
            !is_string($_SESSION['user']['role']) ||
            strtolower($_SESSION['user']['role']) !== 'responsable'
        ) {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        // Extract form data
        $saeIdRaw = $_POST['sae_id'] ?? 0;
        $saeId = is_numeric($saeIdRaw) ? (int)$saeIdRaw : 0;
        $etudiantsRaw = $_POST['etudiants'] ?? [];
        $etudiants = is_array($etudiantsRaw) ? $etudiantsRaw : [];
        $responsableIdRaw = $_SESSION['user']['id'] ?? 0;
        $responsableId = is_numeric($responsableIdRaw) ? (int)$responsableIdRaw : 0;

        try {
            // Check database connection
            \Models\Database::checkConnection();

            // Assign students to SAE
            $etudiants = is_array($etudiantsRaw) ?
                array_map(fn($id) => is_numeric($id) ? (int)$id : 0, $etudiantsRaw) : [];
            SaeAttribution::assignStudentsToSae($saeId, $etudiants, $responsableId);

            // Retrieve SAE information
            $saeInfo = Sae::getById($saeId);

            if (!$saeInfo) {
                throw new \Exception("SAE non trouvée");
            }

            $saeTitreRaw = $saeInfo['titre'] ?? 'la SAE';
            $saeTitre = is_string($saeTitreRaw) ? $saeTitreRaw : 'la SAE';
            $saeDescriptionRaw = $saeInfo['description'] ?? '';
            $saeDescription = is_string($saeDescriptionRaw) ? $saeDescriptionRaw : '';
            $clientIdRaw = $saeInfo['client_id'] ?? null;
            $clientId = is_numeric($clientIdRaw) ? (int)$clientIdRaw : null;


            // Debug logging
            error_log("SAE ID: {$saeId}");
            error_log("SAE Titre: {$saeTitre}");
            error_log("Client ID trouvé: " . ($clientId !== null ? (string)$clientId : 'NULL'));

            // Retrieve client information
            $clientNom = 'Client';
            $clientEmail = '';

            if ($clientId !== null) {
                $clientInfo = User::getById($clientId);
                error_log("Client Info: " . json_encode($clientInfo));

                if ($clientInfo) {
                    $prenomClientRaw = $clientInfo['prenom'] ?? '';
                    $prenomClient = is_string($prenomClientRaw) ? $prenomClientRaw : '';
                    $nomClientRaw = $clientInfo['nom'] ?? '';
                    $nomClient = is_string($nomClientRaw) ? $nomClientRaw : '';
                    $clientNom = trim($prenomClient . ' ' . $nomClient);
                    $clientEmailRaw = $clientInfo['mail'] ?? '';
                    $clientEmail = is_string($clientEmailRaw) ? $clientEmailRaw : '';
                    error_log("Client Email trouvé:  {$clientEmail}");
                } else {
                    error_log("Client non trouvé pour l'ID: " . (string)$clientId);
                }
            } else {
                error_log("Aucun client_id dans la SAE");
            }

            // Retrieve supervisor information
            $responsableInfo = User::getById($responsableId);
            $prenomResponsableRaw = $responsableInfo['prenom'] ?? '';
            $prenomResponsable = $responsableInfo && is_string($prenomResponsableRaw)
                ? $prenomResponsableRaw : '';
            $nomResponsableRaw = $responsableInfo['nom'] ?? '';
            $nomResponsable = $responsableInfo && is_string($nomResponsableRaw) ? $nomResponsableRaw : '';
            $responsableNom = $responsableInfo ? trim($prenomResponsable . ' ' . $nomResponsable) : 'Responsable';

            // Retrieve submission deadline
            $dateRendu = '';
            $attributions = SaeAttribution::getAttributionsBySae($saeId);
            if (!empty($attributions)) {
                $dateRenduRaw = $attributions[0]['date_rendu'] ?? '';
                $dateRendu = is_string($dateRenduRaw) ? $dateRenduRaw : '';
            }

            // Process each assigned student
            $studentNames = [];

            foreach ($etudiants as $studentId) {
                $student = User::getById((int)$studentId);
                if ($student) {
                    $prenomStudentRaw = $student['prenom'] ?? '';
                    $prenomStudent = is_string($prenomStudentRaw) ? $prenomStudentRaw : '';
                    $nomStudentRaw = $student['nom'] ?? '';
                    $nomStudent = is_string($nomStudentRaw) ? $nomStudentRaw : '';
                    $studentFullName = trim($prenomStudent . ' ' . $nomStudent);
                    $studentNames[] = $studentFullName;
                    $studentEmailRaw = $student['mail'] ?? '';
                    $studentEmail = is_string($studentEmailRaw) ? $studentEmailRaw : '';

                    error_log("Étudiant: {$studentFullName} - Email: {$studentEmail}");

                    // Send email notification to student
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

                            // Free memory
                            unset($emailServiceStudent);
                        } catch (\Exception $e) {
                            error_log("Erreur lors de l'envoi de l'email à l'étudiant 
                            {$studentEmail}: " .  $e->getMessage());
                            error_log("Stack trace: " . $e->getTraceAsString());
                        }
                    } else {
                        error_log("Pas d'email pour l'étudiant: {$studentFullName}");
                    }

                    // Send email notification to client
                    if (!empty($clientEmail)) {
                        try {
                            error_log("Création d'une nouvelle instance EmailService pour le client");
                            error_log("Tentative d'envoi email au client: 
                            {$clientEmail} pour étudiant:  {$studentFullName}");

                            // Create new instance for each email
                            $emailServiceClient = new EmailService();

                            $emailServiceClient->sendClientStudentAssignmentNotification(
                                $clientEmail,
                                $clientNom,
                                $saeTitre,
                                $studentFullName,
                                $responsableNom
                            );

                            error_log("✅ Email envoyé au client: {$clientEmail}");

                            // Free memory
                            unset($emailServiceClient);
                        } catch (\Exception $e) {
                            error_log("ERREUR lors de 
                            l'envoi de l'email au client {$clientEmail}: " . $e->getMessage());
                            error_log("Stack trace: " . $e->getTraceAsString());
                        }
                    } else {
                        error_log("Pas d'email client disponible. Client: {$clientNom}, Email: vide");
                    }

                    // Prevent SMTP issues with rate limiting
                    usleep(500000); // 0.5 second delay between emails
                }
            }

            // Build success message
            $nbEtudiants = count($studentNames);
            if ($nbEtudiants === 1) {
                $_SESSION['success_message'] = "L'étudiant « {$studentNames[0]} » 
                a été attribué avec succès à la SAE « $saeTitre ». Des notifications par email ont été envoyées.";
            } else {
                $listeEtudiants = implode(', ', array_slice($studentNames, 0, -1)) . ' et ' . end($studentNames);
                $_SESSION['success_message'] = "$nbEtudiants étudiants ont été 
                attribués avec succès à la SAE « $saeTitre » : 
                $listeEtudiants. Des notifications par email ont été envoyées.";
            }

            header('Location: /sae');
            exit();
        } catch (\Shared\Exceptions\DataBaseException $e) {
            // Database connection error
            $_SESSION['error_message'] = $e->getMessage();
        } catch (SaeAlreadyAssignedException $e) {
            // SAE already assigned to another supervisor
            $_SESSION['error_message'] = "Impossible d'attribuer la SAE « {$e->getSae()} » :
             elle a déjà été attribuée par le responsable « {$e->getResponsable()} ».";
        } catch (StudentAlreadyAssignedException $e) {
            // Student already assigned to this SAE
            $_SESSION['error_message'] = "L'étudiant 
            « {$e->getStudent()} » est déjà assigné à la SAE « {$e->getSae()} ».";
        } catch (\Exception $e) {
            // Generic error handling
            error_log("❌ Erreur générale dans AttribuerSaeController: " .  $e->getMessage());
            error_log("Stack trace: " .  $e->getTraceAsString());
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'affectation.";
        }

        // Redirect with error message
        header('Location: /sae');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/attribuer_sae' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
