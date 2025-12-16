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

        $headerView = new \Views\Base\HeaderView();
        $headerKeys = $headerView->templateKeys();

        return array_merge($headerKeys, [
            self::TITLE_KEY => $this->title,
            self::CONTENT_KEY => $contentHtml,
            self::USERNAME_KEY => $this->username,
            self::ROLE_KEY => $this->role,
        ]);
    }

    private function buildContentHtml(): string
    {
        $html = '';

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

                    $titreSae = htmlspecialchars($sae['sae_titre'] ??  'Titre inconnu');
                    $html .= "<h3>{$titreSae}</h3>";

                    // Date de rendu avec countdown
                    $dateRendu = $sae['date_rendu'] ?? '';
                    if (!empty($dateRendu)) {
                        $html .= "<p><strong>Date de rendu :</strong> " . htmlspecialchars($dateRendu);
                        $html .= " <span class='countdown' data-date='" . htmlspecialchars($dateRendu) . "'></span></p>";
                    } else {
                        $html .= "<p><strong>Date de rendu : </strong> Non définie</p>";
                    }

                    // Avancement To-Do List
                    $todos = $sae['todos'] ?? [];
                    $totalTasks = count($todos);
                    $doneTasks = count(array_filter($todos, fn($task) => !empty($task['fait'])));
                    $percent = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

                    $html .= "<p><strong>Avancement :</strong> {$percent}%</p>";
                    $html .= "<div class='progress-bar'>";
                    $html .= "<div class='progress-fill' style='width: {$percent}%;'></div>";
                    $html .= "</div>";

                    $saeAttributionId = $sae['sae_attribution_id'] ?? 0;
                    $html .= "<form method='POST' action='/todo/add' class='todo-add'>";
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$saeAttributionId}'>";
                    $html .= "<input type='text' name='titre' placeholder='Nouvelle tâche.. .' required>";
                    $html .= "<button type='submit'>Ajouter</button>";
                    $html .= "</form>";

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

                    if (! empty($sae['avis'])) {
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

                    $html .= "</div>";
                }
                break;

            case 'client':
                $html .= "<h2>Vos SAE créées et leurs attributions</h2>";

                foreach ($this->data['saes'] ?? [] as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    $titreSae = htmlspecialchars($sae['titre'] ?? 'Titre inconnu');
                    $description = htmlspecialchars($sae['description'] ?? '');
                    $html .= "<h3>{$titreSae}</h3>";
                    $html .= "<p><strong>Description :</strong> {$description}</p>";

                    $allEtudiants = [];
                    $allTodos = [];
                    $allAvis = [];
                    $dateRendu = null;

                    foreach ($sae['attributions'] ?? [] as $attrib) {
                        foreach ($attrib['etudiants'] ?? [] as $etu) {
                            $allEtudiants[$etu['id']] = htmlspecialchars(trim(($etu['nom'] ?? '') . ' ' . ($etu['prenom'] ?? '')));
                        }

                        if (! isset($dateRendu)) {
                            $dateRendu = $attrib['date_rendu'] ??  '';
                        }

                        foreach ($attrib['todos'] ??  [] as $todo) {
                            $allTodos[] = $todo;
                        }

                        foreach ($attrib['avis'] ?? [] as $avis) {
                            $allAvis[] = $avis;
                        }
                    }

                    $html .= "<p><strong>Étudiants :</strong> ";
                    if (! empty($allEtudiants)) {
                        $html .= implode(', ', $allEtudiants);
                    } else {
                        $html .= "Aucun";
                    }
                    $html .= "</p>";

                    // Date de rendu avec countdown
                    if (!empty($dateRendu)) {
                        $html .= "<p><strong>Date de rendu :</strong> " . htmlspecialchars($dateRendu);
                        $html .= " <span class='countdown' data-date='" . htmlspecialchars($dateRendu) . "'></span></p>";
                    } else {
                        $html .= "<p><strong>Date de rendu : </strong> Non définie</p>";
                    }

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
                                $html .= "<button type='submit' style='color:red; background: none; border:none; cursor: pointer;'>Supprimer</button>";
                                $html .= "</form>";
                            }

                            $html .= "</div>";
                        }
                    } else {
                        $html .= "<p>Aucun avis pour cette SAE.</p>";
                    }

                    $html .= "<h4>Ajouter un avis</h4>";
                    $html .= "<form method='POST' action='/sae/avis/add' class='avis-add'>";
                    $firstAttribId = $sae['attributions'][0]['sae_attribution_id'] ?? 0;
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$firstAttribId}'>";
                    $html .= "<textarea name='message' placeholder='Votre remarque...' required></textarea>";
                    $html .= "<button type='submit'>Envoyer</button>";
                    $html .= "</form>";

                    $html .= "</div>";
                }
                break;

            case 'responsable':
                $html .= "<h2>Vos SAE attribuées</h2>";

                foreach ($this->data['saes'] ?? [] as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    $titreSae = htmlspecialchars($sae['sae_titre'] ?? 'Titre inconnu');
                    $html .= "<h3>{$titreSae}</h3>";

                    $etudiants = $sae['etudiants'] ?? [];
                    if (! empty($etudiants)) {
                        $etudiantsList = array_map(fn($etu) => htmlspecialchars(($etu['nom'] ?? '') . ' ' . ($etu['prenom'] ?? '')), $etudiants);
                        $html .= "<p><strong>Étudiants :</strong> " . implode(', ', $etudiantsList) . "</p>";
                    } else {
                        $html .= "<p><strong>Étudiants :</strong> Aucun</p>";
                    }

                    $saeAttributionId = $sae['sae_attribution_id'] ?? 0;
                    $dateRendu = htmlspecialchars($sae['date_rendu'] ?? '');

                    $html .= "<div class='date-rendu-wrapper'>";
                    $html .= "<form method='POST' action='/sae/update_date' style='display:flex; gap:5px; align-items:center; margin: 0;'>";
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$saeAttributionId}'>";
                    $html .= "<input type='date' name='date_rendu' value='{$dateRendu}'>";
                    $html .= "<button type='submit' class='btn-update-date'>Modifier</button>";
                    $html .= "</form>";
                    $html .= "</div>";

                    // Countdown pour responsable
                    if (!empty($dateRendu)) {
                        $html .= "<p><strong>Temps restant :</strong>";
                        $html .= " <span class='countdown' data-date='" . $dateRendu . "'></span></p>";
                    }

                    $todos = $sae['todos'] ?? [];
                    if (!empty($todos)) {
                        $totalTasks = count($todos);
                        $doneTasks = count(array_filter($todos, fn($task) => !empty($task['fait'])));
                        $percent = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

                        $html .= "<p><strong>Avancement : </strong> {$percent}%</p>";
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

                    $html .= "<h4>Ajouter un avis</h4>";
                    $html .= "<form method='POST' action='/sae/avis/add' class='avis-add'>";
                    $html .= "<input type='hidden' name='sae_attribution_id' value='{$saeAttributionId}'>";
                    $html .= "<textarea name='message' placeholder='Votre remarque...' required></textarea>";
                    $html .= "<button type='submit'>Envoyer</button>";
                    $html .= "</form>";

                    $html .= "</div>";
                }
                break;

            default:
                $html .= "<p>Rôle inconnu ou aucune donnée disponible.</p>";
        }

        return $html;
    }
}