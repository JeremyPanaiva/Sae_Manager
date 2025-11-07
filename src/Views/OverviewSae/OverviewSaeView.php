<?php

namespace Views\OverviewSae;

use Views\Base\BaseView;
use Views\Base\HeaderView;

class OverviewSaeView extends BaseView
{
    private string $title;
    private string $username;
    private string $role;
    protected array $data;

    public function __construct(string $title, array $data, string $username, string $role)
    {
        $this->title = $title;
        $this->data = $data;
        $this->username = $username;
        $this->role = strtolower($role);
    }

    public function templatePath(): string
    {
        return __DIR__ . '/OverviewSae.html';
    }

    public function templateKeys(): array
    {
        $contentHtml = $this->buildContentHtml();

        $headerView = new HeaderView();
        $headerKeys = $headerView->templateKeys();

        return array_merge($headerKeys, [
            'TITLE_KEY' => $this->title,
            'CONTENT_KEY' => $contentHtml,
            'USERNAME_KEY' => $this->username,
            'ROLE_KEY' => ucfirst($this->role),
        ]);
    }

    private function buildContentHtml(): string
    {
        $html = '';

        if (!empty($this->data['error_message'])) {
            $html .= "<div class='error-message'>" . htmlspecialchars($this->data['error_message']) . "</div>";
        }

        $saes = $this->data['saes'] ?? [];

        if ($this->role === 'etudiant') {
            $html .= "<h2>Vos SAE attribuées</h2>";
            $html .= $this->buildTable(
                $saes,
                ['SAE', 'Description', 'Client', 'Responsable', 'Date de rendu'],
                fn($sae) => [
                    $sae['sae_titre'] ?? '-',
                    $sae['sae_description'] ?? '-',
                    ($sae['client_nom'] ?? 'N/A') . ' ' . ($sae['client_prenom'] ?? ''),
                    ($sae['responsable_nom'] ?? 'N/A') . ' ' . ($sae['responsable_prenom'] ?? ''),
                    $sae['date_rendu'] ?? '-'
                ]
            );
        } elseif (in_array($this->role, ['responsable', 'client'])) {
            $html .= "<h2>Récapitulatif des SAE attribuées aux étudiants</h2>";
            $html .= $this->buildTable(
                $saes,
                ['Nom', 'Prénom', 'Email', 'SAE', 'Client', 'Responsable', 'Date de rendu'],
                fn($sae) => [
                    $sae['etudiant_nom'] ?? '-',
                    $sae['etudiant_prenom'] ?? '-',
                    $sae['etudiant_email'] ?? '-',
                    $sae['sae_titre'] ?? '-',
                    ($sae['client_nom'] ?? 'N/A') . ' ' . ($sae['client_prenom'] ?? ''),
                    ($sae['responsable_nom'] ?? 'N/A') . ' ' . ($sae['responsable_prenom'] ?? ''),
                    $sae['date_rendu'] ?? '-'
                ]
            );
        } else {
            $html .= "<p>Rôle inconnu ou aucune SAE disponible.</p>";
        }

        return $html;
    }

    /**
     * Génère une table HTML prête pour DataTables avec couleurs par SAE
     */
    private function buildTable(array $rows, array $headers, callable $rowDataCallback): string
    {
        // 1. Lister tous les ids SAE distincts afin de garantir une palette stable
        $saeKeys = [];
        foreach ($rows as $sae) {
            $key = $sae['sae_id'] ?? $sae['id'] ?? $sae['sae_titre'] ?? 'unknown';
            if (!in_array($key, $saeKeys, true)) {
                $saeKeys[] = $key;
            }
        }

        // 2. Générer la table et passer les clés uniques à getColorForSae
        $html = "<table id='myTable' class='display'>";
        $html .= "<thead><tr>";
        foreach ($headers as $header) {
            $html .= "<th>" . htmlspecialchars($header) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($rows as $sae) {
            $color = $this->getColorForSae($sae, $saeKeys);
            $html .= "<tr style='background-color: $color'>";
            foreach ($rowDataCallback($sae) as $cell) {
                $html .= "<td>" . htmlspecialchars($cell) . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
        return $html;
    }

    /**
     * Retourne une couleur pastel unique pour chaque SAE.
     * $allKeys = tableau des ids SAE distincts dans le tableau affiché
     */
    private function getColorForSae(array $sae, array $allKeys): string
    {
        $key = $sae['sae_id'] ?? $sae['id'] ?? $sae['sae_titre'] ?? 'unknown';

        // Trouve la position de la SAE dans la liste des différentes SAE
        $idx = array_search($key, $allKeys, true);
        $colorCount = count($allKeys);

        // Répartition régulière sur le cercle HSL
        $hue = ($colorCount > 0 && $idx !== false) ? intval(($idx / $colorCount) * 360) : 0;
        return "hsl($hue, 65%, 85%)";
    }
}