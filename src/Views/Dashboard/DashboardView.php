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
 * - Étudiant: Shows assigned SAE with to-do list management, progress bar, deadlines
 * - Client: Shows created SAE with student assignments, progress, and feedback
 * - Responsable: Shows assigned SAE with deadline management, student progress, feedback
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
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Constructor
     *
     * @param string $title Page title
     * @param array<string, mixed> $data Dashboard data (SAE assignments, to-do lists, students, feedback)
     * @param string $username User's full name
     * @param string $role User's role (etudiant, client, responsable)
     */
    public function __construct(string $title, array $data, string $username, string $role)
    {
        parent::__construct();

        $this->title    = $title;
        $this->data     = $data;
        $this->username = $username;
        $this->role     = $role;

        $this->data[self::TITLE_KEY]    = $this->title;
        $this->data[self::USERNAME_KEY] = $this->username;
        $this->data[self::ROLE_KEY]     = $this->role;
        $this->data[self::CONTENT_KEY]  = $this->buildContentHtml();
    }

    /**
     * Converts URLs in text to clickable links
     *
     * @param string $texte Text potentially containing URLs
     * @return string Text with URLs converted to HTML links
     */
    public function rendreLiensCliquables(string $texte): string
    {
        $pattern = '/(https?:\/\/[^\s]+)/i';

        return (string) preg_replace_callback($pattern, function ($matches) {
            $url = htmlspecialchars($matches[1]);
            return "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\">{$url}</a>";
        }, $texte);
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
     * @return string Generated HTML content for the dashboard
     */
    private function buildContentHtml(): string
    {
        $html        = '';
        $currentUser = (array) ($_SESSION['user'] ?? []);

        $errorMessage = $this->data['error_message'] ?? $_SESSION['error_message'] ?? null;
        if ($errorMessage) {
            $html .= "<div class='error-message' style='background-color: #fee; border: 1px solid #f88;
                color: #c00; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
            $html .= htmlspecialchars($this->safeString($errorMessage));
            $html .= "</div>";
            unset($_SESSION['error_message']);
        }

        $successMessage = $this->data['success_message'] ?? $_SESSION['success_message'] ?? null;
        if ($successMessage) {
            $html .= "<div class='success-message' style='background-color: #e8f5e9;
                border: 1px solid #4caf50; color: #2e7d32; padding: 15px; margin-bottom: 20px;
                margin-top: 15px; border-radius: 5px;'>";
            $html .= htmlspecialchars($this->safeString($successMessage));
            $html .= "</div>";
            unset($_SESSION['success_message']);
        }

        switch (strtolower($this->role)) {
            /* ====================================================
               👨‍🎓 ÉTUDIANT
            ==================================================== */
            case 'etudiant':
                $html .= "<h2>Vos SAE attribuées</h2>";

                /** @var array<int, array<string, mixed>> $saes */
                $saes = $this->data['saes'] ?? [];
                foreach ($saes as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    $saeId    = $this->safeString($sae['sae_id'] ?? 0);
                    $titreSae = htmlspecialchars($this->safeString($sae['sae_titre'] ?? 'Titre inconnu'));
                    $html .= "<h3>{$titreSae}</h3>";

                    // Section livrable
                    $githubLink = $this->safeString($sae['github_link'] ?? '');
                    $html .= "<div class='deliverable-container'>";
                    $html .= "<div class='deliverable-header'>";
                    $html .= "<span><i class='fas fa-code-branch'></i> Dépôt du projet</span>";
                    if (!empty($githubLink)) {
                        $html .= "<span class='badge badge-success'>Déposé</span>";
                    }
                    $html .= "</div>";
                    $html .= "<div class='deliverable-body'>";
                    if (!empty($githubLink)) {
                        $html .= "<div class='link-display'>" . $this->rendreLiensCliquables($githubLink) . "</div>";
                    } else {
                        $html .= "<p class='no-link-text'>Aucun lien GitHub ou Drive configuré.</p>";
                    }
                    $html .= "<form method='POST' action='/sae/update_link' class='link-update-form'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<input type='url' name='github_link' value='" . htmlspecialchars($githubLink) . "'
                        placeholder='https://github.com/votre-projet' class='input-url'>";
                    $html .= "<button type='submit' class='btn-primary'>Enregistrer</button>";
                    $html .= "</form>";
                    $html .= "</div></div>";

                    // Countdown
                    if (isset($sae['countdown']) && is_array($sae['countdown'])) {
                        /** @var array{expired: bool, jours?: int, heures?: int, minutes?: int, timestamp?: int, urgent?: bool} $countdown */
                        $countdown = $sae['countdown'];
                        $html .= $this->generateCountdownHTML(
                            $countdown,
                            "etudiant-" . $this->safeString($sae['sae_id'] ?? 0)
                        );
                    }

                    // Avancement
                    /** @var array<int, array<string, mixed>> $todos */
                    $todos      = $sae['todos'] ?? [];
                    $totalTasks = count($todos);
                    $doneTasks  = count(array_filter($todos, fn($task) => !empty($task['fait'])));
                    $percent    = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

                    $html .= "<p><strong>Avancement :</strong> {$percent}%</p>";
                    $html .= "<div class='progress-bar'>"
                        . "<div class='progress-fill' style='width: {$percent}%;'></div>"
                        . "</div>";

                    // Ajouter tâche
                    $html .= "<form method='POST' action='/todo/add' class='todo-add'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<input type='text' name='titre' placeholder='Nouvelle tâche...' required>";
                    $html .= "<button type='submit'>Ajouter</button>";
                    $html .= "</form>";

                    // Liste tâches
                    if ($totalTasks > 0) {
                        $html .= "<ul class='todo-list'>";
                        foreach ($todos as $task) {
                            $taskId          = $this->safeString($task['id'] ?? 0);
                            $taskTitre       = htmlspecialchars($this->safeString($task['titre'] ?? 'Tâche'));
                            $dateCreationRaw = $this->safeString($task['date_creation'] ?? '');
                            $timestampC      = !empty($dateCreationRaw) ? strtotime($dateCreationRaw) : false;
                            $dateCreation    = ($timestampC !== false) ? date('d/m/Y à H:i', $timestampC) : '';
                            $fait            = !empty($task['fait']);
                            $checked         = $fait ? 'checked' : '';

                            $html .= "<li><div class='todo-card" . ($fait ? " done" : "") . "'>";
                            $html .= "<div class='todo-left-section'>";
                            $html .= "<form method='POST' action='/todo/toggle' class='todo-toggle'
                                style='margin:0; flex:0;'>";
                            $html .= "<input type='hidden' name='task_id' value='{$taskId}'>";
                            $html .= "<input type='hidden' name='fait' value='" . ($fait ? 0 : 1) . "'>";
                            $html .= "<input type='checkbox' class='todo-checkbox'
                                onclick='this.form.submit();' {$checked}>";
                            $html .= "</form>";
                            $html .= "<div class='todo-info'>";
                            $html .= "<span class='todo-title'>{$taskTitre}</span>";

                            $metaInfo     = [];
                            $nomAuteur    = $this->safeString($task['nom'] ?? '');
                            $prenomAuteur = $this->safeString($task['prenom'] ?? '');
                            $roleAuteur   = ucfirst($this->safeString($task['role'] ?? ''));
                            if ($dateCreation) {
                                $metaInfo[] = $dateCreation;
                            }
                            if ($nomAuteur && $prenomAuteur) {
                                $metaInfo[] = "{$prenomAuteur} {$nomAuteur}"
                                    . ($roleAuteur ? " ({$roleAuteur})" : "");
                            }
                            if (!empty($metaInfo)) {
                                $html .= "<span class='todo-date'>" . implode(' ', $metaInfo) . "</span>";
                            }

                            $html .= "</div></div>";
                            $html .= "<div class='todo-actions'>";
                            $html .= "<form method='POST' action='/todo/delete' class='todo-delete' style='margin:0;'>";
                            $html .= "<input type='hidden' name='task_id' value='{$taskId}'>";
                            $html .= "<button type='submit' class='btn-delete-task'
                                onclick='return confirm(\"Supprimer cette tâche ?\");'
                                title='Supprimer'></button>";
                            $html .= "</form></div>";
                            $html .= "</div></li>";
                        }
                        $html .= "</ul>";
                    } else {
                        $html .= "<p>Aucune tâche pour cette SAE.</p>";
                    }

                    // Étudiants associés
                    /** @var array<int, array<string, mixed>> $etudiants */
                    $etudiants = $sae['etudiants'] ?? [];
                    if (!empty($etudiants)) {
                        $html .= "<h4>Autres étudiants associés :</h4><ul class='student-list'>";
                        foreach ($etudiants as $etudiant) {
                            $nomComplet = htmlspecialchars(
                                $this->safeString($etudiant['nom'] ?? '') . ' ' .
                                $this->safeString($etudiant['prenom'] ?? '')
                            );
                            $html .= "<li>{$nomComplet}</li>";
                        }
                        $html .= "</ul>";
                    }

                    // Remarques
                    /** @var array<int, array<string, mixed>> $avisList */
                    $avisList = $sae['avis'] ?? [];
                    if (!empty($avisList)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($avisList as $avis) {
                            $nomAuteur    = htmlspecialchars($this->safeString($avis['nom'] ?? 'Inconnu'));
                            $prenomAuteur = htmlspecialchars($this->safeString($avis['prenom'] ?? ''));
                            $roleAuteur   = htmlspecialchars(ucfirst($this->safeString($avis['role'] ?? '')));
                            $message      = htmlspecialchars($this->safeString($avis['message'] ?? ''));
                            $message      = $this->rendreLiensCliquables($message);
                            $dateAvisRaw  = $this->safeString($avis['date_envoi'] ?? '');
                            $timestampA   = !empty($dateAvisRaw) ? strtotime($dateAvisRaw) : false;
                            $dateAvis     = ($timestampA !== false) ? date('d/m/Y à H:i', $timestampA) : '';

                            $html .= "<div class='avis-card'>";
                            $html .= "<p><strong>{$nomAuteur} {$prenomAuteur} ({$roleAuteur}) :</strong> 
                            {$message}</p>";
                            $html .= "<small>{$dateAvis}</small>";
                            $html .= "</div>";
                        }
                    } else {
                        $html .= "<p>Aucun avis pour cette SAE.</p>";
                    }

                    $html .= "</div>"; // end dashboard-card
                }
                break;

            /* ====================================================
               💼 CLIENT
            ==================================================== */
            case 'client':
                $html .= "<h2>Vos SAE créées et leurs attributions</h2>";

                /** @var array<int, array<string, mixed>> $saes */
                $saes = $this->data['saes'] ?? [];
                foreach ($saes as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    $saeId    = $this->safeString($sae['id'] ?? 0);
                    $titreSae = htmlspecialchars($this->safeString($sae['titre'] ?? 'Titre inconnu'));
                    $html .= "<h3>{$titreSae}</h3>";

                    $allEtudiants = [];
                    $dateRendu    = null;

                    /** @var array<int, array<string, mixed>> $allTodos */
                    $allTodos = $sae['todos'] ?? [];
                    /** @var array<int, array<string, mixed>> $allAvis */
                    $allAvis = $sae['avis'] ?? [];

                    /** @var array<int, array<string, mixed>> $attributions */
                    $attributions = $sae['attributions'] ?? [];
                    foreach ($attributions as $attrib) {
                        $student = $attrib['student'] ?? null;
                        if (is_array($student)) {
                            $allEtudiants[$this->safeString($student['id'] ?? 0)] = htmlspecialchars(
                                trim(
                                    $this->safeString($student['nom'] ?? '') . ' ' .
                                    $this->safeString($student['prenom'] ?? '')
                                )
                            );
                        }
                        if (!isset($dateRendu)) {
                            $dateRendu = htmlspecialchars($this->safeString($attrib['date_rendu'] ?? ''));
                        }
                    }

                    $html .= "<p><strong>Étudiants :</strong> ";
                    $html .= !empty($allEtudiants) ? implode(', ', $allEtudiants) : "Aucun";
                    $html .= "</p>";

                    $githubLink = $this->safeString($sae['github_link'] ?? '');
                    if (!empty($githubLink)) {
                        $html .= "<p class='github-link-item'><strong>Lien déposé par les étudiants :</strong> "
                            . $this->rendreLiensCliquables($githubLink) . "</p>";
                    }

                    $html .= "<p><strong>Date de rendu :</strong> " . ($dateRendu ?? 'Non définie') . "</p>";

                    if (isset($sae['countdown']) && is_array($sae['countdown'])) {
                        /** @var array{expired: bool, jours?: int, heures?: int, minutes?: int, timestamp?: int, urgent?: bool} $countdown */
                        $countdown = $sae['countdown'];
                        $html .= $this->generateCountdownHTML($countdown, "client-{$saeId}");
                    }

                    if (!empty($allTodos)) {
                        $totalTasks = count($allTodos);
                        $doneTasks  = count(array_filter($allTodos, fn($task) => !empty($task['fait'])));
                        $percent    = (int) round(($doneTasks / $totalTasks) * 100);

                        $html .= "<p><strong>Avancement :</strong> {$percent}%</p>";
                        $html .= "<div class='progress-bar'>"
                            . "<div class='progress-fill' style='width: {$percent}%;'></div>"
                            . "</div>";

                        $html .= "<ul class='todo-list'>";
                        foreach ($allTodos as $task) {
                            $taskTitre       = htmlspecialchars($this->safeString($task['titre'] ?? 'Tâche'));
                            $dateCreationRaw = $this->safeString($task['date_creation'] ?? '');
                            $timestampC      = !empty($dateCreationRaw) ? strtotime($dateCreationRaw) : false;
                            $dateCreation    = ($timestampC !== false) ? date('d/m/Y à H:i', $timestampC) : '';
                            $fait            = !empty($task['fait']);

                            $html .= "<li><div class='todo-card" . ($fait ? " done" : "") . "'>";
                            $html .= "<div class='todo-info'>";
                            $html .= "<span class='todo-title'>{$taskTitre}</span>";

                            $metaInfo     = [];
                            $nomAuteur    = $this->safeString($task['nom'] ?? '');
                            $prenomAuteur = $this->safeString($task['prenom'] ?? '');
                            $roleAuteur   = ucfirst($this->safeString($task['role'] ?? ''));
                            if ($dateCreation) {
                                $metaInfo[] = $dateCreation;
                            }
                            if ($nomAuteur && $prenomAuteur) {
                                $metaInfo[] = "{$prenomAuteur} {$nomAuteur}"
                                    . ($roleAuteur ? " ({$roleAuteur})" : "");
                            }
                            if (!empty($metaInfo)) {
                                $html .= "<span class='todo-date'>" . implode(' ', $metaInfo) . "</span>";
                            }

                            $html .= "</div>";
                            if ($fait) {
                                $html .= "<div class='todo-actions'>✅</div>";
                            }
                            $html .= "</div></li>";
                        }
                        $html .= "</ul>";
                    } else {
                        $html .= "<p>Aucune tâche pour cette SAE.</p>";
                    }

                    if (!empty($allAvis)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($allAvis as $avis) {
                            $avisData      = (array) $avis;
                            $nomAuteur     = htmlspecialchars($this->safeString($avisData['nom'] ?? 'Inconnu'));
                            $prenomAuteur  = htmlspecialchars($this->safeString($avisData['prenom'] ?? ''));
                            $roleAuteur    = htmlspecialchars(ucfirst($this->safeString($avisData['role'] ?? '')));
                            $message       = htmlspecialchars($this->safeString($avisData['message'] ?? ''));
                            $messageRendu  = $this->rendreLiensCliquables($message);
                            $dateAvisRaw   = $this->safeString($avisData['date_envoi'] ?? '');
                            $timestampA    = !empty($dateAvisRaw) ? strtotime($dateAvisRaw) : false;
                            $dateAvis      = ($timestampA !== false) ? date('d/m/Y à H:i', $timestampA) : '';
                            $avisId        = $this->safeString($avisData['id'] ?? 0);
                            $currentUserId = (int) $this->safeString($currentUser['id'] ?? 0);

                            $html .= "<div class='avis-card'>";
                            $html .= "<p><strong>{$nomAuteur} {$prenomAuteur} ({$roleAuteur}) :</strong> 
                            {$messageRendu}</p>";
                            $html .= "<small>{$dateAvis}</small>";

                            if ((int) $this->safeString($avisData['user_id'] ?? 0) === $currentUserId) {
                                $html .= "<form method='POST' action='/sae/avis/delete' style='display:inline;'>";
                                $html .= "<input type='hidden' name='avis_id' value='{$avisId}'>";
                                $html .= "<button type='submit' class='avis-btn-supprimer'
                                    style='color:red; background:none; border:none; cursor:pointer;'
                                    onclick='return confirm(\"Voulez-vous vraiment supprimer cette remarque ?\");'>
                                    Supprimer</button>";
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

                    $html .= "</div>"; // end dashboard-card
                }
                break;

            /* ====================================================
               👤 RESPONSABLE
            ==================================================== */
            case 'responsable':
                $html .= "<h2>Vos SAE attribuées</h2>";

                // ✅ Bouton envoyer un message — dans le thème du site
                $html .= "<div class='send-message-section'>";
                $html .= "<button
                    type='button'
                    class='btn-open-message-modal'
                    onclick='
                        document.getElementById(\"messageModal\").style.display=\"block\";
                        document.body.classList.add(\"modal-open\");
                    '>
                    ✉️&nbsp; Envoyer un message à un étudiant
                </button>";
                $html .= "</div>";

                /** @var array<int, array<string, mixed>> $saes */
                $saes = $this->data['saes'] ?? [];
                foreach ($saes as $sae) {
                    $html .= "<div class='dashboard-card'>";

                    $saeId    = $this->safeString($sae['sae_id'] ?? 0);
                    $titreSae = htmlspecialchars($this->safeString($sae['sae_titre'] ?? 'Titre inconnu'));
                    $html .= "<h3>{$titreSae}</h3>";

                    // Étudiants
                    /** @var array<int, array<string, mixed>> $etudiants */
                    $etudiants = $sae['etudiants'] ?? [];
                    if (!empty($etudiants)) {
                        $etudiantsList = array_map(fn($etu) => htmlspecialchars(
                            $this->safeString($etu['nom'] ?? '') . ' ' .
                            $this->safeString($etu['prenom'] ?? '')
                        ), $etudiants);
                        $html .= "<p><strong>Étudiants :</strong> " . implode(', ', $etudiantsList) . "</p>";
                    } else {
                        $html .= "<p><strong>Étudiants :</strong> Aucun</p>";
                    }

                    // Lien GitHub
                    $githubLink = $this->safeString($sae['github_link'] ?? '');
                    if (!empty($githubLink)) {
                        $html .= "<p class='github-link-item'><strong>Lien déposé par les étudiants :</strong> "
                            . $this->rendreLiensCliquables($githubLink) . "</p>";
                    }

                    // Date rendu
                    $dateRendu = htmlspecialchars($this->safeString($sae['date_rendu'] ?? ''));
                    $dateOnly  = '';
                    $timeOnly  = '20:00';
                    if (!empty($dateRendu)) {
                        $timestamp = strtotime($dateRendu);
                        if ($timestamp !== false) {
                            $dateOnly = date('Y-m-d', $timestamp);
                            $timeOnly = date('H:i', $timestamp);
                        }
                    }
                    $dateRenduFormatted = '';
                    if (!empty($dateRendu)) {
                        $timestamp = strtotime($dateRendu);
                        if ($timestamp !== false) {
                            $dateRenduFormatted = date('d/m/Y', $timestamp) . ' à ' . date('H:i', $timestamp);
                        }
                    }

                    // Countdown
                    if (isset($sae['countdown']) && is_array($sae['countdown'])) {
                        /** @var array{expired: bool, jours?: int, heures?: int, minutes?: int, timestamp?: int, urgent?: bool} $countdown */
                        $countdown = $sae['countdown'];
                        $html .= $this->generateCountdownHTML($countdown, "responsable-{$saeId}");
                    }

                    // Modifier date de rendu
                    $currentDateTime = !empty($dateOnly) ? "{$dateOnly} {$timeOnly}" : '';
                    $html .= "<div class='date-rendu-wrapper'>";
                    $html .= "<p><strong>Date de rendu actuelle :</strong> <span class='date-value'>"
                        . ($dateRenduFormatted ?: 'Non définie') . "</span></p>";
                    $html .= "<button type='button' class='btn-open-date-modal'
                        onclick='openDateModal({$saeId}, \"{$currentDateTime}\")'>
                        <span class='btn-text-full'>Modifier la date de rendu</span>
                        <span class='btn-text-short'>Modifier</span>
                    </button>";
                    $html .= "</div>";

                    // Modale date
                    $html .= "<div id='modal-date-{$saeId}' class='date-modal'>";
                    $html .= "<div class='date-modal-content'>";
                    $html .= "<span class='date-modal-close'>&times;</span>";
                    $html .= "<h3>Modifier la date et l'heure de rendu</h3>";
                    $html .= "<form method='POST' action='/sae/update_date'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$saeId}'>";
                    $html .= "<div class='date-time-inputs'>";
                    $html .= "<div class='input-wrapper'><div class='form-group'>";
                    $html .= "<label for='date-input-{$saeId}'>Date de rendu :</label>";
                    $html .= "<input type='date' id='date-input-{$saeId}' name='date_rendu'
                        value='{$dateOnly}' required>";
                    $html .= "</div></div>";
                    $html .= "<div class='input-wrapper'><div class='form-group'>";
                    $html .= "<label for='time-input-{$saeId}'>Heure de rendu :</label>";
                    $html .= "<input type='time' id='time-input-{$saeId}' name='heure_rendu'
                        value='{$timeOnly}' required onclick='this.showPicker()'>";
                    $html .= "</div></div>";
                    $html .= "</div>";
                    $html .= "<div class='modal-buttons'>";
                    $html .= "<button type='submit' class='btn-validate-date'>✓ Valider</button>";
                    $html .= "<button type='button' class='btn-cancel-modal'>✗ Annuler</button>";
                    $html .= "</div></form></div></div>";

                    // Tâches
                    /** @var array<int, array<string, mixed>> $todos */
                    $todos = $sae['todos'] ?? [];
                    if (!empty($todos)) {
                        $totalTasks = count($todos);
                        $doneTasks  = count(array_filter($todos, fn($task) => !empty($task['fait'])));
                        $percent    = round(($doneTasks / $totalTasks) * 100);

                        $html .= "<p><strong>Avancement :</strong> {$percent}%</p>";
                        $html .= "<div class='progress-bar'>"
                            . "<div class='progress-fill' style='width: {$percent}%;'></div>"
                            . "</div>";

                        $html .= "<ul class='todo-list'>";
                        foreach ($todos as $task) {
                            $taskTitre       = htmlspecialchars($this->safeString($task['titre'] ?? 'Tâche'));
                            $dateCreationRaw = $this->safeString($task['date_creation'] ?? '');
                            $timestampC      = !empty($dateCreationRaw) ? strtotime($dateCreationRaw) : false;
                            $dateCreation    = ($timestampC !== false) ? date('d/m/Y à H:i', $timestampC) : '';
                            $fait            = !empty($task['fait']);

                            $html .= "<li><div class='todo-card" . ($fait ? " done" : "") . "'>";
                            $html .= "<div class='todo-info'>";
                            $html .= "<span class='todo-title'>{$taskTitre}</span>";

                            $metaInfo     = [];
                            $nomAuteur    = $this->safeString($task['nom'] ?? '');
                            $prenomAuteur = $this->safeString($task['prenom'] ?? '');
                            $roleAuteur   = ucfirst($this->safeString($task['role'] ?? ''));
                            if ($dateCreation) {
                                $metaInfo[] = $dateCreation;
                            }
                            if ($nomAuteur && $prenomAuteur) {
                                $metaInfo[] = "{$prenomAuteur} {$nomAuteur}"
                                    . ($roleAuteur ? " ({$roleAuteur})" : "");
                            }
                            if (!empty($metaInfo)) {
                                $html .= "<span class='todo-date'>" . implode(' ', $metaInfo) . "</span>";
                            }

                            $html .= "</div>";
                            if ($fait) {
                                $html .= "<div class='todo-actions'>✅</div>";
                            }
                            $html .= "</div></li>";
                        }
                        $html .= "</ul>";
                    } else {
                        $html .= "<p>Aucune tâche pour cette SAE.</p>";
                    }

                    // Remarques
                    /** @var array<int, array<string, mixed>> $avisList */
                    $avisList = $sae['avis'] ?? [];
                    if (!empty($avisList)) {
                        $html .= "<h4>Remarques</h4>";
                        foreach ($avisList as $avis) {
                            $avisData      = (array) $avis;
                            $nomAuteur     = htmlspecialchars($this->safeString($avisData['nom'] ?? 'Inconnu'));
                            $prenomAuteur  = htmlspecialchars($this->safeString($avisData['prenom'] ?? ''));
                            $roleAuteur    = htmlspecialchars(ucfirst($this->safeString($avisData['role'] ?? '')));
                            $message       = htmlspecialchars($this->safeString($avisData['message'] ?? ''));
                            $messageRendu  = $this->rendreLiensCliquables($message);
                            $dateAvisRaw   = $this->safeString($avisData['date_envoi'] ?? '');
                            $timestampA    = !empty($dateAvisRaw) ? strtotime($dateAvisRaw) : false;
                            $dateAvis      = ($timestampA !== false) ? date('d/m/Y à H:i', $timestampA) : '';
                            $avisId        = $this->safeString($avisData['id'] ?? 0);
                            $currentUserId = (int) $this->safeString($currentUser['id'] ?? 0);

                            $html .= "<div class='avis-card'>";
                            $html .= "<p><strong>{$nomAuteur} {$prenomAuteur} ({$roleAuteur}) :</strong> 
                            {$messageRendu}</p>";
                            $html .= "<small>{$dateAvis}</small>";

                            if ((int) $this->safeString($avisData['user_id'] ?? 0) === $currentUserId) {
                                $html .= "<form method='POST' action='/sae/avis/delete' style='display:inline;'>";
                                $html .= "<input type='hidden' name='avis_id' value='{$avisId}'>";
                                $html .= "<button type='submit' class='avis-btn-supprimer'
                                    style='color:red; background:none; border:none; cursor:pointer;'
                                    onclick='return confirm(\"Voulez-vous vraiment supprimer cette remarque ?\");'>
                                    Supprimer</button>";
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

                    $html .= "</div>"; // end dashboard-card
                }

                $html .= "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.body.addEventListener('click', function(e) {
                            if (e.target && e.target.classList.contains('btn-open-date-modal')) {
                                const targetId = e.target.getAttribute('data-target');
                                const modal = document.getElementById(targetId);
                                if (modal) {
                                    modal.style.display = 'flex';
                                    const input = modal.querySelector('input[type=\"date\"]');
                                    if (input) { setTimeout(function(){ input.focus(); }, 100); }
                                }
                            }
                            if (e.target && (e.target.classList.contains('date-modal-close') ||
                                e.target.classList.contains('btn-cancel-modal'))) {
                                const modal = e.target.closest('.date-modal');
                                if (modal) { modal.style.display = 'none'; }
                            }
                            if (e.target && e.target.classList.contains('date-modal')) {
                                e.target.style.display = 'none';
                            }
                        });
                    });
                </script>";
                break;

            default:
                $html .= "<p>Rôle inconnu ou aucune donnée disponible.</p>";
        }

        return $html;
    }

    /**
     * Generates the HTML for the project deadline countdown.
     *
     * @param array{
     *     expired: bool,
     *     jours?: int,
     *     heures?: int,
     *     minutes?: int,
     *     timestamp?: int,
     *     urgent?: bool
     * }|null $countdown Countdown data calculated by the controller.
     * @param string $uniqueId Unique identifier for the HTML element.
     * @return string Generated HTML content.
     */
    public function generateCountdownHTML(?array $countdown, string $uniqueId): string
    {
        if ($countdown === null) {
            return "<span class='countdown-error'>Date invalide</span>";
        }

        if ($countdown['expired']) {
            return "<span class='countdown-expired'>Délai expiré</span>";
        }

        $urgentClass = !empty($countdown['urgent']) ? ' urgent' : '';
        $timestamp   = $countdown['timestamp'] ?? 0;
        $jours       = $countdown['jours'] ?? 0;
        $heures      = $countdown['heures'] ?? 0;
        $minutes     = $countdown['minutes'] ?? 0;

        return
            "<div class='countdown-container{$urgentClass}' data-deadline='{$timestamp}' id='countdown-{$uniqueId}'>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='jours'>{$jours}</span>" .
            "<span class='countdown-label'>jours</span>" .
            "</div>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='heures'>{$heures}</span>" .
            "<span class='countdown-label'>heures</span>" .
            "</div>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='minutes'>{$minutes}</span>" .
            "<span class='countdown-label'>minutes</span>" .
            "</div>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='secondes'>0</span>" .
            "<span class='countdown-label'>secondes</span>" .
            "</div>" .

            "</div>";
    }
}
