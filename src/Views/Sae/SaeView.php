<?php

namespace Views\Sae;

use Views\Base\BaseView;

class SaeView extends BaseView
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
     * @param array $data - données SAE (etudiants pour responsable)
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
        return __DIR__ . '/sae.html';
    }

    public function templateKeys(): array
    {
        $contentHtml = $this->buildContentHtml();

        // On récupère le header
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

        // Message d'erreur
        if (!empty($this->data['error_message'])) {
            $html .= "<div class='error-message'>";
            $html .= htmlspecialchars($this->data['error_message']);
            $html .= "</div>";
        }

        switch (strtolower($this->role)) {

            case 'etudiant':
                $html .= "<h2>Vos SAE attribuées</h2>";
                foreach ($this->data['saes'] ?? [] as $sae) {
                    $html .= "<div class='sae-card'>";
                    $html .= "<h3>" . htmlspecialchars($sae['sae_titre']) . "</h3>";
                    $html .= "<p><strong>Description :</strong> " . htmlspecialchars($sae['sae_description']) . "</p>";

                    // Responsable
                    $respNom = htmlspecialchars($sae['responsable_nom'] ?? 'N/A');
                    $respPrenom = htmlspecialchars($sae['responsable_prenom'] ?? '');
                    $respMail = htmlspecialchars($sae['responsable_mail'] ?? '');
                    $html .= "<p><strong>Responsable :</strong> {$respNom} {$respPrenom} - {$respMail}</p>";

                    // Client
                    $clientNom = htmlspecialchars($sae['client_nom'] ?? 'N/A');
                    $clientPrenom = htmlspecialchars($sae['client_prenom'] ?? '');
                    $clientMail = htmlspecialchars($sae['client_mail'] ?? '');
                    $html .= "<p><strong>Client :</strong> {$clientNom} {$clientPrenom} - {$clientMail}</p>";

                    $html .= "<p><strong>Date de rendu :</strong> " . htmlspecialchars($sae['date_rendu']) . "</p>";
                    $html .= "</div>";
                }
                break;




            case 'responsable':
                $html .= "<h2>SAE proposées par les clients</h2>";

                // Affichage du message de succès s'il existe
                if (!empty($this->data['success_message'])) {
                    $html .= "<div class='success-message' style='background-color: #efe; border: 1px solid #8f8; color: #070; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
                    $html .= htmlspecialchars($this->data['success_message']);
                    $html .= "</div>";
                }

                foreach ($this->data['saes'] ?? [] as $sae) {

                    // ✅ Déterminer la classe CSS selon l'état
                    if (!empty($sae['etudiants_attribues'])) {
                        $cardClass = "sae-card my-attr"; // SAE attribuée par moi
                    } elseif (empty($sae['responsable_attribution'])) {
                        $cardClass = "sae-card free"; // SAE libre / non attribuée
                    } else {
                        $cardClass = "sae-card other-attr"; // SAE attribuée par un autre responsable
                    }

                    $html .= "<div class='{$cardClass}'>";
                    $html .= "<h3>" . htmlspecialchars($sae['titre']) . "</h3>";
                    $html .= "<p>" . htmlspecialchars($sae['description']) . "</p>";

                    // ✅ AJOUT : Attribué par...
                    if (!empty($sae['responsable_attribution'])) {
                        $html .= "<p><strong>Attribué par :</strong> "
                            . htmlspecialchars($sae['responsable_attribution']['nom'] . ' ' . $sae['responsable_attribution']['prenom'])
                            . "</p>";
                    } else {
                        $html .= "<p><strong>Attribué par :</strong> Pas attribué</p>";
                    }

                    // Formulaire d'attribution
                    $html .= "<form method='POST' action='/attribuer_sae'>";
                    $html .= "<label>Attribuer à :</label>";

                    if (empty($sae['etudiants_disponibles'])) {
                        $html .= "<p class='info-message'>Aucun étudiant disponible pour l'attribution.</p>";
                    } else {
                        $html .= "<select name='etudiants[]' multiple size='5' required>";
                        foreach ($sae['etudiants_disponibles'] as $etu) {
                            $html .= "<option value='{$etu['id']}'>" . htmlspecialchars($etu['nom'] . ' ' . $etu['prenom']) . "</option>";
                        }
                        $html .= "</select>";
                        $html .= "<small>(Maintenez Ctrl ou Cmd pour sélectionner plusieurs étudiants)</small>";
                        $html .= "<input type='hidden' name='sae_id' value='{$sae['id']}'>";
                        $html .= "<button type='submit'>Attribuer</button>";
                    }
                    $html .= "</form>";

                    // Formulaire de suppression
                    $html .= "<form method='POST' action='/unassign_sae' class='remove-form'>";
                    $html .= "<label>Retirer de la SAE (vos attributions uniquement) :</label>";

                    if (empty($sae['etudiants_attribues'])) {
                        $html .= "<p class='info-message'>Vous n'avez attribué aucun étudiant à cette SAE.</p>";
                    } else {
                        $html .= "<select name='etudiants[]' multiple size='5' required>";
                        foreach ($sae['etudiants_attribues'] as $etu) {
                            $html .= "<option value='{$etu['id']}'>" . htmlspecialchars($etu['nom'] . ' ' . $etu['prenom']) . "</option>";
                        }
                        $html .= "</select>";
                        $html .= "<small>(Maintenez Ctrl ou Cmd pour sélectionner plusieurs étudiants)</small>";
                        $html .= "<input type='hidden' name='sae_id' value='{$sae['id']}'>";
                        $html .= "<button type='submit' class='danger-btn'>Retirer</button>";
                    }
                    $html .= "</form>";

                    $html .= "</div>";
                }
                break;




            case 'client':

                // Formulaire de création
                $html .= "<h2>Créer une nouvelle SAE</h2>";
                $html .= "<form method='POST' action='/creer_sae'>";
                $html .= "<label>Titre :</label><input type='text' name='titre' required>";
                $html .= "<label>Description :</label><textarea name='description' required></textarea>";
                $html .= "<button type='submit'>Créer SAE</button>";
                $html .= "</form>";

                // Liste des SAE existantes
                $html .= "<h2>Vos SAE existantes</h2>";

                foreach ($this->data['saes'] ?? [] as $sae) {

                    // --------------------------
                    //   CODE COULEUR
                    // --------------------------
                    if (!empty($sae['responsable_attribution'])) {
                        $cardClass = "sae-card attribuee";
                    } else {
                        $cardClass = "sae-card libre";
                    }

                    $html .= "<div class='{$cardClass}'>";

                    // --------------------------
                    //   AFFICHAGE NORMAL
                    // --------------------------
                    $html .= "<h3>" . htmlspecialchars($sae['titre']) . "</h3>";
                    $html .= "<p>" . htmlspecialchars($sae['description']) . "</p>";
                    $html .= "<p><strong>Date de création :</strong> " . htmlspecialchars($sae['date_creation']) . "</p>";

                    if (!empty($sae['responsable_attribution'])) {
                        $responsable = $sae['responsable_attribution'];
                        $html .= "<p><strong>Attribuée par :</strong> "
                            . htmlspecialchars($responsable['prenom'] . " " . $responsable['nom'])
                            . "</p>";
                    } else {
                        $html .= "<p><strong>Attribuée par :</strong> <em>Non attribuée</em></p>";
                    }

                    // --------------------------
                    //   BOUTON : MODIFIER
                    // --------------------------
                    $html .= "<button class='btn-modifier' onclick=\"document.getElementById('edit-{$sae['id']}').style.display='block';\">Modifier</button>";

                    // --------------------------
                    //   FORMULAIRE DE MODIFICATION (hidden par défaut)
                    // --------------------------
                    $html .= "<div id='edit-{$sae['id']}' class='edit-form' style='display:none; margin-top:10px;'>";
                    $html .= "<form method='POST' action='/update_sae'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$sae['id']}'>";

                    $html .= "<label>Nouveau titre :</label>";
                    $html .= "<input type='text' name='titre' value='" . htmlspecialchars($sae['titre']) . "' required>";

                    $html .= "<label>Nouvelle description :</label>";
                    $html .= "<textarea name='description' required>" . htmlspecialchars($sae['description']) . "</textarea>";

                    $html .= "<button type='submit' class='btn-valider'>Valider</button>";
                    $html .= "</form>";
                    $html .= "</div>";

                    // --------------------------
                    //   BOUTON SUPPRESSION
                    // --------------------------
                    $html .= "<form method='POST' action='/delete_sae' onsubmit='return confirm(\"Supprimer cette SAE ?\");'>";
                    $html .= "<input type='hidden' name='sae_id' value='{$sae['id']}'>";
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
