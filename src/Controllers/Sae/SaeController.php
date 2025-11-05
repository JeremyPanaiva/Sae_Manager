<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Views\Sae\SaeView;
use Models\User\User;
use Models\Sae\Sae;
use Models\Sae\SaeAttribution;

class SaeController implements ControllerInterface
{
    public const PATH = '/sae';

    public function control()
    {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $currentUser = $_SESSION['user'];
        $role = strtolower($currentUser['role']);
        $username = $currentUser['nom'] . ' ' . $currentUser['prenom'];
        $userId = $currentUser['id'];

        $contentData = [
            'saes' => [],
            'error_message' => '',
            'success_message' => ''
        ];

        try {
            // Récupération des données selon le rôle
            $contentData = array_merge($contentData, $this->prepareSaeContent($userId, $role));
        } catch (\Shared\Exceptions\DataBaseException $e) {
            // On met le message dans la view SAE
            $contentData['error_message'] =  $e->getMessage();
        } catch (\Exception $e) {
            $contentData['error_message'] = "Erreur inattendue : " . $e->getMessage();
        }

        $view = new SaeView(
            'Gestion des SAE',
            $contentData,
            $username,
            ucfirst($role)
        );

        echo $view->render();
    }


    private function prepareSaeContent(int $userId, string $role): array
    {
        switch ($role) {
            case 'etudiant':
                $saes = SaeAttribution::getSaeForStudent($userId);
                foreach ($saes as &$sae) {
                    $sae['date_rendu_formatee'] = !empty($sae['date_rendu'])
                        ? date('d/m/Y', strtotime($sae['date_rendu']))
                        : 'Non définie';
                }
                return ['saes' => $saes];

            case 'responsable':
                $saes = Sae::getAllProposed();
                $etudiants = User::getAllByRole('etudiant');
                $responsableId = $userId;

                foreach ($saes as &$sae) {
                    // Tous les étudiants attribués à cette SAE
                    $assignedStudents = SaeAttribution::getStudentsForSae($sae['id']);

                    // ✅ Étudiants attribués par CE responsable
                    $etudiantsAttribuesParMoi = [];
                    foreach ($assignedStudents as $assignedStudent) {
                        if (SaeAttribution::isStudentAssignedByResponsable($sae['id'], $assignedStudent['id'], $responsableId)) {
                            $etudiantsAttribuesParMoi[] = $assignedStudent;
                        }
                    }

                    // ✅ Étudiants disponibles (non attribués du tout)
                    $etudiantsDisponibles = array_filter($etudiants, function ($etudiant) use ($assignedStudents) {
                        foreach ($assignedStudents as $assignedStudent) {
                            if ($assignedStudent['id'] == $etudiant['id']) {
                                return false;
                            }
                        }
                        return true;
                    });

                    // Injection dans la SAE
                    $sae['etudiants_disponibles'] = $etudiantsDisponibles;
                    $sae['etudiants_attribues']   = $etudiantsAttribuesParMoi;

                    // ✅ Ajout : responsable ayant attribué la SAE (ou null si pas attribué)
                    $sae['responsable_attribution'] = SaeAttribution::getResponsableForSae($sae['id']);
                }

                // 🔹 TRI : mes attributions → libres → attribuées par d'autres
                usort($saes, function ($a, $b) use ($responsableId) {
                    $aIsMine  = !empty($a['etudiants_attribues']);
                    $bIsMine  = !empty($b['etudiants_attribues']);

                    $aIsFree  = empty($a['responsable_attribution']);
                    $bIsFree  = empty($b['responsable_attribution']);

                    $priorityA = $aIsMine ? 0 : ($aIsFree ? 1 : 2);
                    $priorityB = $bIsMine ? 0 : ($bIsFree ? 1 : 2);

                    return $priorityA - $priorityB;
                });

                $errorMessage = $_SESSION['error_message'] ?? '';
                $successMessage = $_SESSION['success_message'] ?? '';
                unset($_SESSION['error_message'], $_SESSION['success_message']);

                return [
                    'saes' => $saes,
                    'error_message' => $errorMessage,
                    'success_message' => $successMessage
                ];


            case 'client':
                $saes = Sae::getByClient($userId);

                foreach ($saes as &$sae) {
                    // Ajout : responsable ayant attribué la SAE (ou null si pas attribué)
                    $sae['responsable_attribution'] = SaeAttribution::getResponsableForSae($sae['id']);
                }

                return ['saes' => $saes];


            default:
                return [];
        }
    }

    public function handleCreateSae(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $clientId = $_SESSION['user']['id'];

            if ($titre !== '' && $description !== '') {
                try {
                    SaeAttribution::checkDatabaseConnection();
                    Sae::create($clientId, $titre, $description);
                } catch (\Shared\Exceptions\DataBaseException $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                } catch (\Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    public function handleAssignSae(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $dateRendu = $_POST['date_rendu'] ?? '';
            $etudiants = $_POST['etudiants'] ?? [];

            if ($saeId > 0 && !empty($etudiants)) {
                try {
                    SaeAttribution::checkDatabaseConnection();
                    foreach ($etudiants as $studentId) {
                        SaeAttribution::assignToStudent($saeId, (int)$studentId, $dateRendu);
                    }
                } catch (\Shared\Exceptions\DataBaseException $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                } catch (\Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    public function handleUnassignSae(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $etudiants = $_POST['etudiants'] ?? [];

            if ($saeId > 0 && !empty($etudiants)) {
                try {
                    SaeAttribution::checkDatabaseConnection();
                    foreach ($etudiants as $studentId) {
                        SaeAttribution::removeFromStudent($saeId, (int)$studentId);
                    }
                } catch (\Shared\Exceptions\DataBaseException $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                } catch (\Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}
