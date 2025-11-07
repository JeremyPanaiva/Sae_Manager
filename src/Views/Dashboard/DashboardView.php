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
    }

    public function templatePath(): string
    {
        return __DIR__ . '/dashboard.html';
    }

    public function templateKeys(): array
    {
        $contentHtml = $this->buildContentHtml();

        // Récupère le header
        $headerView = new \Views\Base\HeaderView();
        $headerKeys = $headerView->templateKeys();

        return array_merge($headerKeys, [
            self::TITLE_KEY => $this->title,
            self::CONTENT_KEY => $contentHtml,
            self::USERNAME_KEY => $this->username,
            self::ROLE_KEY => $this->role,
        ]);
    }

    /**
     * Génère le HTML du contenu selon le rôle
     */
    private function buildContentHtml(): string
    {
        $html = '';

        // Messages de succès
        $successMessage = $this->data['success_message'] ?? $_SESSION['success_message'] ?? null;
        if ($successMessage) {
            $html .= "<div class='success-message' style='background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
            $html .= htmlspecialchars($successMessage);
            $html .= "</div>";
            unset($_SESSION['success_message']);
        }

        // Messages d'avertissement
        $warningMessage = $this->data['warning_message'] ?? $_SESSION['warning_message'] ?? null;
        if ($warningMessage) {
            $html .= "<div class='warning-message' style='background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
            $html .= htmlspecialchars($warningMessage);
            $html .= "</div>";
            unset($_SESSION['warning_message']);
        }

        // Messages d'information
        $infoMessage = $this->data['info_message'] ?? $_SESSION['info_message'] ?? null;
        if ($infoMessage) {
            $html .= "<div class='info-message' style='background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
            $html .= htmlspecialchars($infoMessage);
            $html .= "</div>";
            unset($_SESSION['info_message']);
        }

        // Messages d'erreur
        $errorMessage = $this->data['error_message'] ?? $_SESSION['error_message'] ?? null;
        if ($errorMessage) {
            $html .= "<div class='error-message' style='background-color: #fee; border: 1px solid #f88; color: #c00; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
            $html .= htmlspecialchars($errorMessage);
            $html .= "</div>";
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
                    $saeAttributionId = $sae['sae_attribution_id'] ?? 0;
                    $html .= "<form method='POST' action='/todo/add' class='todo-add'>";
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$saeAttributionId}'>";
                    $html .= "<input type='text' name='titre' placeholder='Nouvelle tâche...' required>";
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
                            $html .= "<form method='POST' action='/todo/toggle' class='todo-toggle'>";
                            $html .= "<input type='hidden' name='task_id' value='{$taskId}'>";
                            $html .= "<input type='hidden' name='fait' value='" . ($fait ? 0 : 1) . "'>";
                            $html .= "<label>";
                            $html .= "<input type='checkbox' class='todo-checkbox' onclick='this.form.submit();' {$checked}> ";
                            $html .= $taskTitre;
                            $html .= "</label>";
                            $html .= "</form>";
                            $html .= "</li>";
                        }
                        $html .= "</ul>";
                    } else {
                        $html .= "<p>Aucune tâche pour cette SAE.</p>";
                    }

                    // --- Étudiants associés ---
                    $etudiants = $sae['etudiants'] ?? [];
                    if (!empty($etudiants)) {
                        $html .= "<h4>Autres étudiants associés :</h4>";
                        $html .= "<ul class='student-list'>";
                        foreach ($etudiants as $etudiant) {
                            $nomComplet = htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']);
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
                            $roleAuteur = htmlspecialchars(ucfirst($avis['role'] ?? ''));
                            $message = htmlspecialchars($avis['message'] ?? '');
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
                    $titreSae = htmlspecialchars($sae['titre'] ?? 'Titre inconnu');
                    $description = htmlspecialchars($sae['description'] ?? '');
                    $html .= "<h3>{$titreSae}</h3>";
                    $html .= "<p><strong>Description :</strong> {$description}</p>";

                    // --- Regrouper les informations par SAE ---
                    $allEtudiants = [];
                    $allTodos = [];
                    $allAvis = [];

                    foreach ($sae['attributions'] ?? [] as $attrib) {
                        // Étudiants
                        foreach ($attrib['etudiants'] ?? [] as $etu) {
                            $allEtudiants[$etu['id']] = htmlspecialchars(trim(($etu['nom'] ?? '') . ' ' . ($etu['prenom'] ?? '')));
                        }

                        // Date de rendu : on prend la date de la première attribution
                        if (!isset($dateRendu)) {
                            $dateRendu = htmlspecialchars($attrib['date_rendu'] ?? '');
                        }

                        // To-Do
                        foreach ($attrib['todos'] ?? [] as $todo) {
                            $allTodos[] = $todo;
                        }

                        // Avis
                        foreach ($attrib['avis'] ?? [] as $avis) {
                            $allAvis[] = $avis;
                        }
                    }

                    // --- Étudiants ---
                    $html .= "<p><strong>Étudiants :</strong> ";
                    if (!empty($allEtudiants)) {
                        $html .= implode(', ', $allEtudiants);
                    } else {
                        $html .= "Aucun";
                    }
                    $html .= "</p>";

                    // --- Date de rendu ---
                    $html .= "<p><strong>Date de rendu :</strong> " . ($dateRendu ?? '') . "</p>";

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
                        $html .= "<p>Aucune tâche pour cette SAE.</p>";
                    }

                    // --- Avis / Remarques ---
                    if (!empty($allAvis)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($allAvis as $avis) {
                            $nomAuteur = htmlspecialchars($avis['nom'] ?? 'Inconnu');
                            $prenomAuteur = htmlspecialchars($avis['prenom'] ?? '');
                            $roleAuteur = htmlspecialchars(ucfirst($avis['role'] ?? ''));
                            $message = htmlspecialchars($avis['message'] ?? '');
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
                        $html .= "<p>Aucun avis pour cette SAE.</p>";
                    }

                    // --- Formulaire Ajouter un avis ---
                    $html .= "<h4>Ajouter un avis</h4>";
                    $html .= "<form method='POST' action='/sae/avis/add' class='avis-add'>";
                    // On prend la première attribution pour lier le formulaire
                    $firstAttribId = $sae['attributions'][0]['sae_attribution_id'] ?? 0;
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$firstAttribId}'>";
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
                    $titreSae = htmlspecialchars($sae['sae_titre'] ?? 'Titre inconnu');
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
                    $saeAttributionId = $sae['sae_attribution_id'] ?? 0;
                    $dateRendu = htmlspecialchars($sae['date_rendu'] ?? '');

                    $html .= "<div class='date-rendu-wrapper'>";
                    $html .= "<form method='POST' action='/sae/update_date' style='display:flex; gap:5px; align-items:center; margin:0;'>";
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$saeAttributionId}'>";
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
                    if (!empty($avisList)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($avisList as $avis) {
                            $userIdAuteur = $avis['user_id'] ?? 0;
                            $message = htmlspecialchars($avis['message'] ?? '');
                            $dateAvis = htmlspecialchars($avis['date_envoi'] ?? '');
                            $avisId = $avis['id'] ?? 0;
                            $currentUserId = $_SESSION['user']['id'] ?? 0;

                            $nomAuteur = htmlspecialchars($avis['nom'] ?? 'Inconnu');
                            $prenomAuteur = htmlspecialchars($avis['prenom'] ?? '');
                            $roleAuteur = htmlspecialchars(ucfirst($avis['role'] ?? ''));

                            $html .= "<div class='avis-card'>";
                            $html .= "<p><strong>{$nomAuteur} {$prenomAuteur} ({$roleAuteur}) :</strong> {$message}</p>";
                            $html .= "<small>{$dateAvis}</small>";

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

                    // Formulaire ajouter un avis
                    $html .= "<h4>Ajouter un avis</h4>";
                    $html .= "<form method='POST' action='/sae/avis/add' class='avis-add'>";
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$saeAttributionId}'>";
                    $html .= "<textarea name='message' placeholder='Votre remarque...' required></textarea>";
                    $html .= "<button type='submit'>Envoyer</button>";
                    $html .= "</form>";

                    $html .= "</div>"; // dashboard-card
                }


                $currentDelays = \Models\Sae\AutoReminder::getReminderDelays();

                // Séparateur visuel
                $html .= "<hr style='margin: 40px 0; border: none; border-top: 2px solid #ddd;'>";

                // Nouveau titre de section
                $html .= "<h2>Gestion des rappels par email</h2>";

                $html .= "<div class='dashboard-card' style='background-color: #f8f9fa;'>";

                // Envoi immédiat
                $html .= "<h4>Envoyer un rappel immédiatement</h4>";
                $html .= "<p style='color: #666; font-size: 0.95em; margin-bottom: 12px;'>Envoyez un email aux étudiants ayant une échéance dans le délai sélectionné.</p>";
                $html .= "<form method='POST' action='/sae/manage-reminders' class='todo-add'>";
                $html .= "<input type='hidden' name='action' value='send_now'>";
                $html .= "<select name='days'>";
                foreach ([1, 2, 3, 5, 7, 10, 14] as $d) {
                    $selected = $d == 3 ? 'selected' : '';
                    $label = $d == 1 ? '1 jour avant' : "{$d} jours avant";
                    $html .= "<option value='{$d}' {$selected}>{$label}</option>";
                }
                $html .= "</select>";
                $html .= "<button type='submit'>Envoyer maintenant</button>";
                $html .= "</form>";

                // Ligne de séparation
                $html .= "<hr style='margin: 25px 0; border: none; border-top: 1px solid #ddd;'>";

                // Configuration auto
                $html .= "<h4>Configuration des rappels automatiques</h4>";
                $html .= "<p style='color: #666; font-size: 0.95em; margin-bottom: 12px;'>Les rappels seront envoyés automatiquement chaque jour aux délais cochés ci-dessous.</p>";
                $html .= "<form method='POST' action='/sae/manage-reminders'>";
                $html .= "<input type='hidden' name='action' value='configure_auto'>";

                foreach ([1, 3, 7, 10, 14] as $d) {
                    $checked = in_array($d, $currentDelays) ? 'checked' : '';
                    $label = $d == 1 ? '1 jour avant' : "{$d} jours avant";
                    $html .= "<label style='display:block; margin:10px 0; cursor:pointer;'>";
                    $html .= "<input type='checkbox' name='delays[]' value='{$d}' {$checked} style='margin-right:8px;'> {$label}";
                    $html .= "</label>";
                }

                $html .= "<button type='submit' style='margin-top:15px;'>Enregistrer la configuration</button>";
                $html .= "</form>";

                if (!empty($currentDelays)) {
                    $html .= "<p style='margin-top:20px; padding:10px; background-color:#fff; border-left:3px solid #28a745; color:#666; font-size:0.9em;'>";
                    $html .= "<strong>Actuellement actif :</strong> J-" . implode(', J-', $currentDelays);
                    $html .= "</p>";
                }

                $html .= "</div>"; // dashboard-card

                break;


            default:
                $html .= "<p>Rôle inconnu ou aucune donnée disponible.</p>";
        }

        return $html;
    }
}