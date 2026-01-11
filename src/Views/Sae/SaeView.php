<?php

namespace Views\Sae;

use Views\Base\BaseView;

/**
 * SAE View
 *
 * Renders role-specific SAE (Situation d'Apprentissage et d'Évaluation) management pages.
 * Displays different interfaces and functionality based on user role.
 *
 * Role-based features:
 * - Étudiant: Views assigned SAE with supervisor and client contact information
 * - Responsable:  Assigns SAE to students, manages student assignments
 * - Client: Creates, modifies, and deletes SAE, views assignment status
 *
 * @package Views\Sae
 */
class SaeView extends BaseView
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
     * SAE data (available SAE, students, assignments, etc.)
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Constructor
     *
     * @param string $title Page title
     * @param array<string, mixed> $data SAE data (available SAE, students for assignment, etc.)
     * @param string $username User's full name
     * @param string $role User's role (etudiant, client, responsable)
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
        $this->data[self::CONTENT_KEY] = $this->buildContentHtml();
    }

    /**
     * Returns the path to the SAE template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return __DIR__ . '/sae.php';
    }

    /**
     * Generates role-specific HTML content for the SAE page
     *
     * Builds different views based on user role:
     * - Étudiant: List of assigned SAE with supervisor and client details
     * - Responsable:  SAE assignment interface with student selection, color-coded by status
     * - Client: SAE creation, modification, and deletion interface
     *
     * Also displays error and success messages when present.
     *
     * @return string Generated HTML content for the SAE page
     */
    private function buildContentHtml(): string
    {
        $html = '';

        if (!empty($this->data['error_message'])) {
            $html .= "<div class='error-message'>";
            $html .= htmlspecialchars($this->safeString($this->data['error_message']));
            $html .= "</div>";
        }

        switch (strtolower($this->role)) {
            case 'etudiant':
                $html .= "<h2>Vos SAE attribuées</h2>";
                /** @var array<int, array<string, mixed>> $saes */
                $saes = $this->data['saes'] ?? [];
                foreach ($saes as $sae) {
                    $html .= "<div class='sae-card'>";
                    $html .= "<h3>" . htmlspecialchars($this->safeString($sae['sae_titre'] ?? '')) . "</h3>";
                    $html .= "<p><strong>Description :</strong> " .
                        htmlspecialchars($this->safeString($sae['sae_description'] ?? '')) . "</p>";

                    $respNom = htmlspecialchars($this->safeString($sae['responsable_nom'] ?? 'N/A'));
                    $respPrenom = htmlspecialchars($this->safeString($sae['responsable_prenom'] ?? ''));
                    $respMail = htmlspecialchars($this->safeString($sae['responsable_mail'] ?? ''));
                    $html .= "<p><strong>Responsable :</strong> {$respNom} {$respPrenom} - {$respMail}</p>";

                    $clientNom = htmlspecialchars($this->safeString($sae['client_nom'] ?? 'N/A'));
                    $clientPrenom = htmlspecialchars($this->safeString($sae['client_prenom'] ?? ''));
                    $clientMail = htmlspecialchars($this->safeString($sae['client_mail'] ?? ''));
                    $html .= "<p><strong>Client : </strong> {$clientNom} {$clientPrenom} - {$clientMail}</p>";

                    $html .= "<p><strong>Date de rendu :</strong> " .
                        htmlspecialchars($this->safeString($sae['date_rendu'] ?? '')) . "</p>";
                    $html .= "</div>";
                }
                break;

            case 'responsable':
                $html .= "<div class='sae-header-flex'>";
                $html .= "<h2>SAE proposées par les clients</h2>";

                $html .= "<div class='color-legend'>";
                $html .= "<div>Légende :</div>";
                $html .= "<div>";
                $html .= "<div><span style='display: inline-block; width: 12px; height: 12px; background: #e3f2fd; 
                    border: 1px solid #2196f3; border-radius: 2px; margin-right: 6px;'></span>Libre</div>";
                $html .= "<div><span style='display: inline-block; width: 12px; height: 12px; background: #e8f5e9; 
                    border: 1px solid #4caf50; border-radius: 2px; margin-right: 6px;'></span>Vous</div>";
                $html .= "<div><span style='display: inline-block; width: 12px; height:  12px; background: #ffebee; 
                    border: 1px solid #f44336; border-radius: 2px; margin-right: 6px;'></span>Autre</div>";
                $html .= "</div>";
                $html .= "</div>";
                $html .= "</div>";


                if (!empty($this->data['success_message'])) {
                    $html .= "<div class='success-message' style='background-color: #efe; border:  1px solid #8f8; 
                        color: #070; padding:  15px; margin-bottom:  20px; border-radius:  5px;'>";
                    $html .= htmlspecialchars($this->safeString($this->data['success_message']));
                    $html .= "</div>";
                }

                /** @var array<int, array<string, mixed>> $saes */
                $saes = $this->data['saes'] ?? [];
                foreach ($saes as $sae) {
                    if (!empty($sae['etudiants_attribues'])) {
                        $cardClass = "sae-card my-attr";
                    } elseif (empty($sae['responsable_attribution'])) {
                        $cardClass = "sae-card free";
                    } else {
                        $cardClass = "sae-card other-attr";
                    }

                    $html .= "<div class='{$cardClass}'>";
                    $html .= "<h3>" . htmlspecialchars($this->safeString($sae['titre'] ?? '')) . "</h3>";
                    $html .= "<p>" . htmlspecialchars($this->safeString($sae['description'] ?? '')) . "</p>";

                    $responsable = $sae['responsable_attribution'] ?? null;
                    if (is_array($responsable)) {
                        $html .= "<p><strong>Attribué par :</strong> "
                            . htmlspecialchars($this->safeString($responsable['nom'] ?? '') . ' ' .
                                $this->safeString($responsable['prenom'] ?? ''))
                            . "</p>";
                    } else {
                        $html .= "<p><strong>Attribué par :</strong> Pas attribué</p>";
                    }

                    $html .= "<form method='POST' action='/attribuer_sae'>";
                    $html .= "<label>Attribuer à :</label>";

                    if (empty($sae['etudiants_disponibles'])) {
                        $html .= "<p class='info-message'>Aucun étudiant disponible pour l'attribution.</p>";
                    } else {
                        $html .= "<select name='etudiants[]' multiple size='5' required>";
                        /** @var array<int, array<string, mixed>> $etudiantsDispo */
                        $etudiantsDispo = $sae['etudiants_disponibles'];
                        foreach ($etudiantsDispo as $etu) {
                            $html .= "<option value='" . $this->safeString($etu['id'] ?? 0) . "'>" .
                                htmlspecialchars(
                                    $this->safeString($etu['nom'] ?? '') . ' ' .
                                    $this->safeString($etu['prenom'] ?? '')
                                ) .
                                "</option>";
                        }
                        $html .= "</select>";
                        $html .= "<small>(Maintenez Ctrl ou Cmd pour sélectionner plusieurs étudiants)</small>";
                        $html .= "<input type='hidden' name='sae_id' value='" .
                            $this->safeString($sae['id'] ?? 0) . "'>";
                        $html .= "<button type='submit'>Attribuer</button>";
                    }
                    $html .= "</form>";

                    $html .= "<form method='POST' action='/unassign_sae' class='remove-form'>";
                    $html .= "<label>Retirer de la SAE (vos attributions uniquement) :</label>";

                    if (empty($sae['etudiants_attribues'])) {
                        $html .= "<p class='info-message'>Vous n'avez attribué aucun étudiant à cette SAE.</p>";
                    } else {
                        $html .= "<select name='etudiants[]' multiple size='5' required>";
                        /** @var array<int, array<string, mixed>> $etudiantsAttribues */
                        $etudiantsAttribues = $sae['etudiants_attribues'];
                        foreach ($etudiantsAttribues as $etu) {
                            $html .= "<option value='" . $this->safeString($etu['id'] ?? 0) . "'>" .
                                htmlspecialchars(
                                    $this->safeString($etu['nom'] ?? '') . ' ' .
                                    $this->safeString($etu['prenom'] ?? '')
                                ) .
                                "</option>";
                        }
                        $html .= "</select>";
                        $html .= "<small>(Maintenez Ctrl ou Cmd pour sélectionner plusieurs étudiants)</small>";
                        $html .= "<input type='hidden' name='sae_id' value='" .
                            $this->safeString($sae['id'] ?? 0) . "'>";
                        $html .= "<button type='submit' class='danger-btn'>Retirer</button>";
                    }
                    $html .= "</form>";

                    $html .= "</div>";
                }
                break;

            case 'client':
                $html .= "<h2>Créer une nouvelle SAE</h2>";
                $html .= "<form method='POST' action='/creer_sae'>";
                $html .= "<label>Titre :</label><input type='text' name='titre' required>";
                $html .= "<label>Description :</label><textarea name='description' required></textarea>";
                $html .= "<button type='submit'>Créer SAE</button>";
                $html .= "</form>";

                $html .= "<h2>Vos SAE existantes</h2>";

                /** @var array<int, array<string, mixed>> $saes */
                $saes = $this->data['saes'] ?? [];
                foreach ($saes as $sae) {
                    if (!empty($sae['responsable_attribution'])) {
                        $cardClass = "sae-card attribuee";
                    } else {
                        $cardClass = "sae-card libre";
                    }

                    $html .= "<div class='{$cardClass}'>";

                    $html .= "<h3>" . htmlspecialchars($this->safeString($sae['titre'] ?? '')) . "</h3>";
                    $html .= "<p>" . htmlspecialchars($this->safeString($sae['description'] ?? '')) . "</p>";
                    $html .= "<p><strong>Date de création :</strong> " .
                        htmlspecialchars($this->safeString($sae['date_creation'] ?? '')) . "</p>";

                    $responsable = $sae['responsable_attribution'] ?? null;
                    if (is_array($responsable)) {
                        $html .= "<p><strong>Attribuée par :</strong> "
                            . htmlspecialchars(
                                $this->safeString($responsable['prenom'] ?? '') . " " .
                                $this->safeString($responsable['nom'] ?? '')
                            )
                            . "</p>";
                    } else {
                        $html .= "<p><strong>Attribuée par :</strong> <em>Non attribuée</em></p>";
                    }

                    $html .= "<button class='btn-modifier' 
                        onclick=\"document.getElementById('edit-" .
                        $this->safeString($sae['id'] ?? 0) .
                        "').style.display='block';\">
                        Modifier</button>";

                    $html .= "<div id='edit-" . $this->safeString($sae['id'] ?? 0) .
                        "' class='edit-form' style='display:none; margin-top:10px;'>";
                    $html .= "<form method='POST' action='/update_sae'>";
                    $html .= "<input type='hidden' name='sae_id' value='" . $this->safeString($sae['id'] ?? 0) . "'>";

                    $html .= "<label>Nouveau titre :</label>";
                    $html .= "<input type='text' name='titre' value='" .
                        htmlspecialchars($this->safeString($sae['titre'] ?? '')) . "' required>";

                    $html .= "<label>Nouvelle description :</label>";
                    $html .= "<textarea name='description' required>" .
                        htmlspecialchars($this->safeString($sae['description'] ?? '')) . "</textarea>";

                    $html .= "<button type='submit' class='btn-valider'>Valider</button>";
                    $html .= "</form>";
                    $html .= "</div>";

                    $html .= "<form method='POST' action='/delete_sae'
                        onsubmit='return confirm(\"Supprimer cette SAE ?\");'>";
                    $html .= "<input type='hidden' name='sae_id' value='" . $this->safeString($sae['id'] ?? 0) . "'>";
                    $html .= "<button type='submit' class='btn-supprimer'>Supprimer</button>";
                    $html .= "</form>";

                    $html .= "</div>";
                }

                break;

            default:
                $html .= "<p>Rôle inconnu ou aucune SAE disponible.</p>";
        }

        return $html;
    }
}
