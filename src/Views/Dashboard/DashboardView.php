<?php

namespace Views\Dashboard;

use Views\Base\BaseView;

class DashboardView extends BaseView
{
    public const TITLE_KEY = 'TITLE_KEY';
    public const CONTENT_KEY = 'CONTENT_KEY';
    public const USERNAME_KEY = 'USERNAME_KEY';
    public const ROLE_KEY = 'ROLE_KEY';

    private string $title;
    private string $username;
    private string $role;
    protected array $data;

    /**
     * @param string $title - titre de la page
     * @param array $data - données pour le dashboard
     * @param string $username - nom complet utilisateur
     * @param string $role - rôle de l'utilisateur
     */
    public function __construct(string $title, array $data, string $username, string $role)
    {
        $this->title = $title;
        $this->data = $data;
        $this->username = $username;
        $this->role = $role;

        $this->data[self::TITLE_KEY] = $this->title;
        $this->data[self::USERNAME_KEY] = $this->username;
        $this->data[self::ROLE_KEY] = $this->role;
        $this->data[self:: CONTENT_KEY] = $this->buildContentHtml();
    }

    function rendreLiensCliquables($texte)
    {
        $pattern = '/(https?:\/\/[^\s]+)/i'; // détecte les URLs commençant par http(s)
        $remplacement = '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>';
        return preg_replace($pattern, $remplacement, $texte);
    }
    public function templatePath(): string
    {
        return __DIR__ . '/dashboard.php';
    }


    /**
     * Génère le HTML du contenu selon le rôle
     */
    private function buildContentHtml(): string
    {
        $html = '';

        // 1️⃣ On récupère l'erreur, soit depuis data, soit depuis la session
        $errorMessage = $this->data['error_message'] ?? $_SESSION['error_message'] ?? null;
        if ($errorMessage) {
            $html .= "<div class='error-message' style='background-color: #fee; border: 1px solid #f88; color: #c00; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
            $html .= htmlspecialchars($errorMessage);
            $html .= "</div>";

            // On supprime la session pour que ça ne réapparaisse pas au prochain refresh
            unset($_SESSION['error_message']);
        }


        switch (strtolower($this->role)) {


            case 'etudiant':
                $html .= "<h2>Vos SAE attribuées</h2>";

                foreach ($this->data['saes'] ?? [] as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    // --- Titre de la SAE ---
                    $titreSae = htmlspecialchars($sae['sae_titre'] ?? 'Titre inconnu');
                    $html .= "<h3>{$titreSae}</h3>";

                    // --- Date de rendu avec compte à rebours ---
                    $dateRendu = $sae['date_rendu'] ?? '';
                    $html .= "<p><strong>Date de rendu :</strong> {$dateRendu} ";
                    $html .= "<span class='countdown' data-date='{$dateRendu}'></span></p>";

                    // --- Avancement To-Do List ---
                    $todos = $sae['todos'] ?? [];
                    $totalTasks = count($todos);
                    $doneTasks = count(array_filter($todos, fn($task) => !empty($task['fait'])));
                    $percent = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

                    $html .= "<p><strong>Avancement :</strong> {$percent}%</p>";

                    // --- Barre de progression ---
                    $html .= "<div class='progress-bar'>";
                    $html .= "<div class='progress-fill' style='width: {$percent}%;'></div>";
                    $html .= "</div>";

                    // --- Formulaire pour ajouter une tâche ---
                    $saeId = $sae['sae_id'] ?? 0;
                    $html .= "<form method='POST' action='/todo/add' class='todo-add'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<input type='text' name='titre' placeholder='Nouvelle tâche.. .' required>";
                    $html .= "<button type='submit'>Ajouter</button>";
                    $html .= "</form>";

                    // --- Liste des tâches ---
                    if ($totalTasks > 0) {
                        $html .= "<ul class='todo-list'>";
                        foreach ($todos as $task) {
                            $taskId = $task['id'] ?? 0;
                            $taskTitre = htmlspecialchars($task['titre'] ?? 'Tâche');
                            $fait = !empty($task['fait']);
                            $checked = $fait ? 'checked' : '';

                            $html .= "<li>";

                            // Formulaire toggle (checkbox)
                            $html .= "<form method='POST' action='/todo/toggle' class='todo-toggle'>";
                            $html .= "<input type='hidden' name='task_id' value='{$taskId}'>";
                            $html .= "<input type='hidden' name='fait' value='" . ($fait ? 0 : 1) . "'>";
                            $html .= "<label>";
                            $html .= "<input type='checkbox' class='todo-checkbox' onclick='this.form.submit();' {$checked}> ";
                            $html .= $taskTitre;
                            $html .= "</label>";
                            $html .= "</form>";

                            // ✅ Bouton de suppression avec croix
                            $html .= "<form method='POST' action='/todo/delete' class='todo-delete'>";
                            $html .= "<input type='hidden' name='task_id' value='{$taskId}'>";
                            $html .= "<button type='submit' class='btn-delete-task' onclick='return confirm(\"Supprimer cette tâche ?\");' title='Supprimer'></button>";
                            $html .= "</form>";

                            $html .= "</li>";
                        }
                        $html .= "</ul>";
                    } else {
                        $html .= "<p>Aucune tâche pour cette SAE. </p>";
                    }

                    // --- Étudiants associés ---
                    $etudiants = $sae['etudiants'] ?? [];
                    if (!empty($etudiants)) {
                        $html .= "<h4>Autres étudiants associés : </h4>";
                        $html .= "<ul class='student-list'>";
                        foreach ($etudiants as $etudiant) {
                            $nomComplet = htmlspecialchars($etudiant['nom'] .  ' ' . $etudiant['prenom']);
                            $html .= "<li>{$nomComplet}</li>";
                        }
                        $html .= "</ul>";
                    }

                    // --- Remarques / avis ---
                    if (!empty($sae['avis'])) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($sae['avis'] as $avis) {
                            $nomAuteur = htmlspecialchars($avis['nom'] ?? 'Inconnu');
                            $prenomAuteur = htmlspecialchars($avis['prenom'] ?? '');
                            $roleAuteur = htmlspecialchars(ucfirst($avis['role'] ??  ''));
                            $message = htmlspecialchars($avis['message'] ?? '');
                            $message = $this->rendreLiensCliquables($message);
                            $dateAvis = htmlspecialchars($avis['date_envoi'] ?? '');

                            $html .= "<div class='avis-card'>";
                            $html .= "<p><strong>{$nomAuteur} {$prenomAuteur} ({$roleAuteur}) :</strong> {$message}</p>";
                            $html .= "<small>{$dateAvis}</small>";
                            $html .= "</div>";
                        }
                    } else {
                        $html .= "<p>Aucun avis pour cette SAE.</p>";
                    }

                    $html .= "</div>"; // dashboard-card
                }
                break;


            case 'client':
                $html .= "<h2>Vos SAE créées et leurs attributions</h2>";

                foreach ($this->data['saes'] ?? [] as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    // --- Titre et description de la SAE ---
                    $saeId = $sae['id'] ?? 0;
                    $titreSae = htmlspecialchars($sae['titre'] ?? 'Titre inconnu');
                    $description = htmlspecialchars($sae['description'] ?? '');
                    $html .= "<h3>{$titreSae}</h3>";
                    $html .= "<p><strong>Description :</strong> {$description}</p>";

                    // --- Regrouper les informations par SAE ---
                    $allEtudiants = [];
                    $dateRendu = null;

                    foreach ($sae['attributions'] ?? [] as $attrib) {
                        // Étudiants
                        $student = $attrib['student'] ?? null;
                        if ($student) {
                            $allEtudiants[$student['id']] = htmlspecialchars(trim(($student['nom'] ?? '') . ' ' . ($student['prenom'] ?? '')));
                        }

                        // Date de rendu :  on prend la date de la première attribution
                        if (!isset($dateRendu)) {
                            $dateRendu = htmlspecialchars($attrib['date_rendu'] ?? '');
                        }
                    }

                    // Récupération des todos et avis (maintenant liés à sae_id)
                    $allTodos = $sae['todos'] ?? [];
                    $allAvis = $sae['avis'] ?? [];

                    // --- Étudiants ---
                    $html .= "<p><strong>Étudiants :</strong> ";
                    if (! empty($allEtudiants)) {
                        $html .= implode(', ', $allEtudiants);
                    } else {
                        $html .= "Aucun";
                    }
                    $html .= "</p>";

                    // --- Date de rendu ---
                    $html .= "<p><strong>Date de rendu :</strong> " . ($dateRendu ?? 'Non définie') . "</p>";

                    // --- To-Do et progression ---
                    if (!empty($allTodos)) {
                        $totalTasks = count($allTodos);
                        $doneTasks = count(array_filter($allTodos, fn($task) => !empty($task['fait'])));
                        $percent = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

                        $html .= "<p><strong>Avancement :</strong> {$percent}%</p>";
                        $html .= "<div class='progress-bar'><div class='progress-fill' style='width: {$percent}%;'></div></div>";

                        $html .= "<ul class='todo-list'>";
                        foreach ($allTodos as $task) {
                            $taskTitre = htmlspecialchars($task['titre'] ?? 'Tâche');
                            $fait = !empty($task['fait']);
                            $html .= "<li>{$taskTitre}" . ($fait ? " ✅" : "") . "</li>";
                        }
                        $html .= "</ul>";
                    } else {
                        $html .= "<p>Aucune tâche pour cette SAE. </p>";
                    }

                    // --- Avis / Remarques ---
                    if (!empty($allAvis)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($allAvis as $avis) {
                            $nomAuteur = htmlspecialchars($avis['nom'] ?? 'Inconnu');
                            $prenomAuteur = htmlspecialchars($avis['prenom'] ?? '');
                            $roleAuteur = htmlspecialchars(ucfirst($avis['role'] ?? ''));
                            $message = htmlspecialchars($avis['message'] ?? '');
                            $message = $this->rendreLiensCliquables($message);
                            $dateAvis = htmlspecialchars($avis['date_envoi'] ?? '');
                            $avisId = $avis['id'] ?? 0;
                            $currentUserId = $_SESSION['user']['id'] ?? 0;

                            $html .= "<div class='avis-card'>";
                            $html .= "<p><strong>{$nomAuteur} {$prenomAuteur} ({$roleAuteur}) :</strong> {$message}</p>";
                            $html .= "<small>{$dateAvis}</small>";

                            if (($avis['user_id'] ?? 0) === $currentUserId) {
                                $html .= "<form method='POST' action='/sae/avis/delete' style='display:inline; margin-left:10px;'>";
                                $html .= "<input type='hidden' name='avis_id' value='{$avisId}'>";
                                $html .= "<button type='submit' style='color:red; background:none; border:none; cursor:pointer;'>Supprimer</button>";
                                $html .= "</form>";
                            }

                            $html .= "</div>";
                        }
                    } else {
                        $html .= "<p>Aucun avis pour cette SAE. </p>";
                    }

                    // --- Formulaire Ajouter un avis (utilise sae_id maintenant) ---
                    $html .= "<h4>Ajouter un avis</h4>";
                    $html .= "<form method='POST' action='/sae/avis/add' class='avis-add'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<textarea name='message' placeholder='Votre remarque...' required></textarea>";
                    $html .= "<button type='submit'>Envoyer</button>";
                    $html .= "</form>";

                    $html .= "</div>"; // fin dashboard-card
                }
                break;




            case 'responsable':
                $html .= "<h2>Vos SAE attribuées</h2>";

                foreach ($this->data['saes'] ?? [] as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    // Titre SAE
                    $saeId = $sae['sae_id'] ?? 0;
                    $titreSae = htmlspecialchars($sae['sae_titre'] ??  'Titre inconnu');
                    $html .= "<h3>{$titreSae}</h3>";

                    // Étudiants associés
                    $etudiants = $sae['etudiants'] ?? [];
                    if (!empty($etudiants)) {
                        $etudiantsList = array_map(fn($etu) => htmlspecialchars(($etu['nom'] ?? '') . ' ' . ($etu['prenom'] ?? '')), $etudiants);
                        $html .= "<p><strong>Étudiants :</strong> " . implode(', ', $etudiantsList) . "</p>";
                    } else {
                        $html .= "<p><strong>Étudiants :</strong> Aucun</p>";
                    }

                    // Date de rendu modifiable pour responsable
                    $dateRendu = htmlspecialchars($sae['date_rendu'] ?? '');

                    $html .= "<div class='date-rendu-wrapper'>";
                    $html .= "<form method='POST' action='/sae/update_date' style='display: flex; gap:5px; align-items:center; margin: 0;'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<input type='date' name='date_rendu' value='{$dateRendu}'>";
                    $html .= "<button type='submit' class='btn-update-date'>Modifier</button>";
                    $html .= "</form>";
                    $html .= "</div>";


                    // To-Do list et progression
                    $todos = $sae['todos'] ?? [];
                    if (!empty($todos)) {
                        $totalTasks = count($todos);
                        $doneTasks = count(array_filter($todos, fn($task) => !empty($task['fait'])));
                        $percent = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

                        $html .= "<p><strong>Avancement :</strong> {$percent}%</p>";
                        $html .= "<div class='progress-bar'><div class='progress-fill' style='width: {$percent}%;'></div></div>";

                        $html .= "<ul class='todo-list'>";
                        foreach ($todos as $task) {
                            $taskTitre = htmlspecialchars($task['titre'] ?? 'Tâche');
                            $fait = !empty($task['fait']);
                            $html .= "<li>{$taskTitre}" . ($fait ? " ✅" : "") . "</li>";
                        }
                        $html .= "</ul>";
                    } else {
                        $html .= "<p>Aucune tâche pour cette SAE.</p>";
                    }

                    // Avis / remarques
                    $avisList = $sae['avis'] ?? [];
                    if (! empty($avisList)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($avisList as $avis) {
                            $userIdAuteur = $avis['user_id'] ?? 0;
                            $message = htmlspecialchars($avis['message'] ?? '');
                            $message = $this->rendreLiensCliquables($message);
                            $dateAvis = htmlspecialchars($avis['date_envoi'] ?? '');
                            $avisId = $avis['id'] ?? 0;
                            $currentUserId = $_SESSION['user']['id'] ?? 0;

                            // On prend le nom, prénom et rôle directement depuis l'avis
                            $nomAuteur = htmlspecialchars($avis['nom'] ?? 'Inconnu');
                            $prenomAuteur = htmlspecialchars($avis['prenom'] ?? '');
                            $roleAuteur = htmlspecialchars(ucfirst($avis['role'] ?? ''));

                            $html .= "<div class='avis-card'>";
                            $html .= "<p><strong>{$nomAuteur} {$prenomAuteur} ({$roleAuteur}) :</strong> {$message}</p>";
                            $html .= "<small>{$dateAvis}</small>";

                            // Bouton supprimer si même utilisateur
                            if ($userIdAuteur === $currentUserId) {
                                $html .= "<form method='POST' action='/sae/avis/delete' style='display:inline; margin-left:10px;'>";
                                $html .= "<input type='hidden' name='avis_id' value='{$avisId}'>";
                                $html .= "<button type='submit' style='color:red; background:none; border:none; cursor:pointer;'>Supprimer</button>";
                                $html .= "</form>";
                            }

                            $html .= "</div>";
                        }
                    } else {
                        $html .= "<p>Aucun avis pour cette SAE.</p>";
                    }


                    // Formulaire ajouter un avis (utilise sae_id maintenant)
                    $html .= "<h4>Ajouter un avis</h4>";
                    $html .= "<form method='POST' action='/sae/avis/add' class='avis-add'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<textarea name='message' placeholder='Votre remarque.. .' required></textarea>";
                    $html .= "<button type='submit'>Envoyer</button>";
                    $html .= "</form>";

                    $html .= "</div>"; // dashboard-card
                }
                break;



            default:
                $html .= "<p>Rôle inconnu ou aucune donnée disponible.</p>";
        }


        return $html;
    }
}