<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\Sae;
use Models\User\User;
use Models\User\EmailService;
use Shared\Exceptions\SaeAlreadyAssignedException;
use Shared\Exceptions\StudentAlreadyAssignedException;
use Shared\SessionGuard;
use Shared\CsrfGuard;
use Shared\RoleGuard;

/**
 * SAE assignment controller
 *
 * Handles the assignment of students to SAE (Situation d'Apprentissage et d'Évaluation)
 * by supervisors (responsables). Sends email notifications to assigned students and
 * the client who created the SAE.
 * Role verification is delegated to RoleGuard.
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
    public function control(): void
    {
        SessionGuard::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Requête invalide (CSRF).');
        }

        // Verify user is authenticated as a supervisor
        RoleGuard::requireRoleOrForbid('responsable');

        // Extract form data
        $saeIdRaw         = $_POST['sae_id']    ?? 0;
        $saeId            = is_numeric($saeIdRaw)         ? (int) $saeIdRaw         : 0;
        $etudiantsRaw     = $_POST['etudiants'] ?? [];
        $responsableIdRaw = $_SESSION['user']['id']       ?? 0;
        $responsableId    = is_numeric($responsableIdRaw) ? (int) $responsableIdRaw : 0;

        try {
            \Models\Database::checkConnection();

            $etudiants = is_array($etudiantsRaw)
                ? array_map(fn($id) => is_numeric($id) ? (int) $id : 0, $etudiantsRaw)
                : [];

            if (empty($etudiants)) {
                $_SESSION['error_message'] = "Veuillez sélectionner au moins un étudiant à attribuer.";
                header('Location: /sae');
                exit();
            }

            SaeAttribution::assignStudentsToSae($saeId, $etudiants, $responsableId);

            // Retrieve SAE information
            $saeInfo = Sae::getById($saeId);
            if (!$saeInfo) {
                throw new \Exception("SAE non trouvée");
            }

            $saeTitre      = is_string($saeInfo['titre']       ?? null) ? $saeInfo['titre']       : 'la SAE';
            $saeDescription = is_string($saeInfo['description'] ?? null) ? $saeInfo['description'] : '';
            $clientIdRaw   = $saeInfo['client_id'] ?? null;
            $clientId      = is_numeric($clientIdRaw) ? (int) $clientIdRaw : null;

            // Retrieve client information
            $clientNom   = 'Client';
            $clientEmail = '';

            if ($clientId !== null) {
                $clientInfo = User::getById($clientId);
                if ($clientInfo) {
                    $prenomClient = is_string($clientInfo['prenom'] ?? null) ? $clientInfo['prenom'] : '';
                    $nomClient    = is_string($clientInfo['nom']    ?? null) ? $clientInfo['nom']    : '';
                    $clientNom    = trim($prenomClient . ' ' . $nomClient);
                    $clientEmail  = is_string($clientInfo['mail']   ?? null) ? $clientInfo['mail']   : '';
                }
            }

            // Retrieve supervisor information
            $responsableInfo = User::getById($responsableId);
            $prenomResponsable = $responsableInfo && is_string($responsableInfo['prenom'] ?? null)
                ? $responsableInfo['prenom'] : '';
            $nomResponsable    = $responsableInfo && is_string($responsableInfo['nom']    ?? null)
                ? $responsableInfo['nom']    : '';
            $responsableNom    = $responsableInfo ? trim($prenomResponsable . ' ' . $nomResponsable) : 'Responsable';

            // Retrieve submission deadline
            $dateRendu   = '';
            $attributions = SaeAttribution::getAttributionsBySae($saeId);
            if (!empty($attributions)) {
                $dateRendu = is_string($attributions[0]['date_rendu'] ?? null) ? $attributions[0]['date_rendu'] : '';
            }

            // Process each assigned student and send notifications
            $studentNames = [];

            foreach ($etudiants as $studentId) {
                $student = User::getById((int) $studentId);
                if ($student) {
                    $prenomStudent   = is_string($student['prenom'] ?? null) ? $student['prenom'] : '';
                    $nomStudent      = is_string($student['nom']    ?? null) ? $student['nom']    : '';
                    $studentFullName = trim($prenomStudent . ' ' . $nomStudent);
                    $studentNames[]  = $studentFullName;
                    $studentEmail    = is_string($student['mail']   ?? null) ? $student['mail']   : '';

                    if (!empty($studentEmail)) {
                        try {
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
                            unset($emailServiceStudent);
                        } catch (\Exception $e) {
                            error_log("Erreur email étudiant {$studentEmail}: " . $e->getMessage());
                        }
                    }

                    usleep(200000); // 0.2s delay between emails
                }
            }

            // Send single summary email to client
            if (!empty($clientEmail) && !empty($studentNames)) {
                try {
                    $studentListString  = implode(', ', $studentNames);
                    $emailServiceClient = new EmailService();
                    $emailServiceClient->sendClientStudentAssignmentNotification(
                        $clientEmail,
                        $clientNom,
                        $saeTitre,
                        $studentListString,
                        $responsableNom
                    );
                    unset($emailServiceClient);
                } catch (\Exception $e) {
                    error_log("Erreur email client {$clientEmail}: " . $e->getMessage());
                }
            }

            // Build success message
            $nbEtudiants = count($studentNames);
            if ($nbEtudiants === 1) {
                $_SESSION['success_message'] = "L'étudiant « {$studentNames[0]} » a été attribué avec succès à la SAE « 
                $saeTitre ». Des notifications par email ont été envoyées.";
            } else {
                $listeEtudiants = implode(', ',
                        array_slice($studentNames, 0, -1)) . ' et ' . end($studentNames);
                $_SESSION['success_message'] = "$nbEtudiants étudiants ont été attribués avec succès à la SAE « 
                $saeTitre » : $listeEtudiants. Des notifications par email ont été envoyées.";
            }

            header('Location: /sae');
            exit();
        } catch (\Shared\Exceptions\DataBaseException $e) {
            $_SESSION['error_message'] = $e->getMessage();
        } catch (SaeAlreadyAssignedException $e) {
            $_SESSION['error_message'] = "Impossible d'attribuer la SAE « 
            {$e->getSae()} » : elle a déjà été attribuée par le responsable « {$e->getResponsable()} ».";
        } catch (StudentAlreadyAssignedException $e) {
            $_SESSION['error_message'] = "L'étudiant « {$e->getStudent()} » est déjà assigné à la SAE « {$e->getSae()} ».";
        } catch (\Exception $e) {
            error_log("Erreur générale AttribuerSaeController: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'affectation.";
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $path   The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/attribuer_sae' and method is POST.
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
