<?php

namespace Views\Dashboard;

use Views\Base\BaseView;

/**
 * Dashboard View
 *
 * Renders role-specific dashboard content for students, clients, and supervisors.
 * Displays SAE assignments, to-do lists, progress tracking, student groups, and
 * comments/feedback for each SAE.
 *
 * Role-based content:
 * - Étudiant:  Shows assigned SAE with to-do list management, progress bar, deadlines
 * - Client: Shows created SAE with student assignments, progress, and feedback
 * - Responsable:  Shows assigned SAE with deadline management, student progress, feedback
 *
 * @package Views\Dashboard
 */
class DashboardView extends BaseView
{
    /**
     * Template data key for page title
     */
    public const TITLE_KEY = 'TITLE_KEY';

    /**
     * Template data key for generated HTML content
     */
    public const CONTENT_KEY = 'CONTENT_KEY';

    /**
     * Template data key for username display
     */
    public const USERNAME_KEY = 'USERNAME_KEY';

    /**
     * Template data key for user role
     */
    public const ROLE_KEY = 'ROLE_KEY';

    /**
     * Page title
     *
     * @var string
     */
    private string $title;

    /**
     * Username (full name)
     *
     * @var string
     */
    private string $username;

    /**
     * User role (etudiant, client, responsable)
     *
     * @var string
     */
    private string $role;

    /**
     * Dashboard data (SAE, to-do, students, etc.)
     *
     * @var array
     */
    protected array $data;

    /**
     * Constructor
     *
     * @param string $title Page title
     * @param array $data Dashboard data (SAE assignments, to-do lists, students, feedback)
     * @param string $username User's full name
     * @param string $role User's role (etudiant, client, responsable)
     */
    public function __construct(string $title, array $data, string $username, string $role)
    {
        parent::__construct();

        $this->title = $title;
        $this->data = $data;
        $this->username = $username;
        $this->role = $role;

        $this->data[self::TITLE_KEY] = $this->title;
        $this->data[self::USERNAME_KEY] = $this->username;
        $this->data[self::ROLE_KEY] = $this->role;
        $this->data[self:: CONTENT_KEY] = $this->buildContentHtml();
    }

    /**
     * Converts URLs in text to clickable links
     *
     * Detects URLs starting with http(s) and wraps them in anchor tags
     * with target="_blank" and rel="noopener noreferrer" for security.
     *
     * @param string $texte Text potentially containing URLs
     * @return string Text with URLs converted to HTML links
     */
    function rendreLiensCliquables($texte)
    {
        $pattern = '/(https?:\/\/[^\s]+)/i';
        $remplacement = '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>';
        return preg_replace($pattern, $remplacement, $texte);
    }

    /**
     * Returns the path to the dashboard template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return __DIR__ . '/dashboard.php';
    }

    /**
     * Generates role-specific HTML content for the dashboard
     *
     * Builds different dashboard views based on user role:
     * - Étudiant: SAE with to-do management, progress tracking, team members, feedback
     * - Client: Created SAE with student assignments, progress overview, feedback management
     * - Responsable:  Assigned SAE with deadline editing, student progress, feedback management
     *
     * Also displays error messages from session if present.
     *
     * @return string Generated HTML content for the dashboard
     */
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

                    $dateRendu = $sae['date_rendu'] ?? '';
                    $html .= "<p><strong>Date de rendu :</strong> {$dateRendu} ";
                    $html .= "<span class='countdown' data-date='{$dateRendu}'></span></p>";

                    $todos = $sae['todos'] ??  [];
                    $totalTasks = count($todos);
                    $doneTasks = count(array_filter($todos, fn($task) => !empty($task['fait'])));
                    $percent = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

                    $html .= "<p><strong>Avancement : </strong> {$percent}%</p>";

                    $html .= "<div class='progress-bar'>";
                    $html .= "<div class='progress-fill' style='width: {$percent}%;'></div>";
                    $html .= "</div>";

                    $saeId = $sae['sae_id'] ?? 0;
                    $html .= "<form method='POST' action='/todo/add' class='todo-add'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
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
                            $message = $this->rendreLiensCliquables($message);
                            $dateAvis = htmlspecialchars($avis['date_envoi'] ??  '');

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

                    $saeId = $sae['id'] ?? 0;
                    $titreSae = htmlspecialchars($sae['titre'] ?? 'Titre inconnu');
                    $description = htmlspecialchars($sae['description'] ?? '');
                    $html .= "<h3>{$titreSae}</h3>";
                    $html .= "<p><strong>Description :</strong> {$description}</p>";

                    $allEtudiants = [];
                    $dateRendu = null;

                    foreach ($sae['attributions'] ??  [] as $attrib) {
                        $student = $attrib['student'] ??  null;
                        if ($student) {
                            $allEtudiants[$student['id']] = htmlspecialchars(trim(($student['nom'] ?? '') . ' ' . ($student['prenom'] ?? '')));
                        }

                        if (! isset($dateRendu)) {
                            $dateRendu = htmlspecialchars($attrib['date_rendu'] ?? '');
                        }
                    }

                    $allTodos = $sae['todos'] ?? [];
                    $allAvis = $sae['avis'] ?? [];

                    $html .= "<p><strong>Étudiants :</strong> ";
                    if (! empty($allEtudiants)) {
                        $html .= implode(', ', $allEtudiants);
                    } else {
                        $html .= "Aucun";
                    }
                    $html .= "</p>";

                    $html .= "<p><strong>Date de rendu :</strong> " . ($dateRendu ??  'Non définie') . "</p>";

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

                    if (!empty($allAvis)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($allAvis as $avis) {
                            $nomAuteur = htmlspecialchars($avis['nom'] ?? 'Inconnu');
                            $prenomAuteur = htmlspecialchars($avis['prenom'] ?? '');
                            $roleAuteur = htmlspecialchars(ucfirst($avis['role'] ?? ''));
                            $message = htmlspecialchars($avis['message'] ??  '');
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

                    $html .= "<h4>Ajouter un avis</h4>";
                    $html .= "<form method='POST' action='/sae/avis/add' class='avis-add'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
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

                    $saeId = $sae['sae_id'] ?? 0;
                    $titreSae = htmlspecialchars($sae['sae_titre'] ??  'Titre inconnu');
                    $html .= "<h3>{$titreSae}</h3>";

                    $etudiants = $sae['etudiants'] ?? [];
                    if (!empty($etudiants)) {
                        $etudiantsList = array_map(fn($etu) => htmlspecialchars(($etu['nom'] ?? '') . ' ' . ($etu['prenom'] ?? '')), $etudiants);
                        $html .= "<p><strong>Étudiants :</strong> " . implode(', ', $etudiantsList) . "</p>";
                    } else {
                        $html .= "<p><strong>Étudiants :</strong> Aucun</p>";
                    }

                    $dateRendu = htmlspecialchars($sae['date_rendu'] ?? '');

                    $html .= "<div class='date-rendu-wrapper'>";
                    $html .= "<form method='POST' action='/sae/update_date' style='display:flex; gap:5px; align-items:center; margin: 0;'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<input type='date' name='date_rendu' value='{$dateRendu}'>";
                    $html .= "<button type='submit' class='btn-update-date'>Modifier</button>";
                    $html .= "</form>";
                    $html .= "</div>";

                    $todos = $sae['todos'] ??  [];
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
                    if (! empty($avisList)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($avisList as $avis) {
                            $userIdAuteur = $avis['user_id'] ?? 0;
                            $message = htmlspecialchars($avis['message'] ?? '');
                            $message = $this->rendreLiensCliquables($message);
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
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
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