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
            'TITLE_KEY'    => $this->title,
            'CONTENT_KEY'  => $contentHtml,
            'USERNAME_KEY' => $this->username,
            'ROLE_KEY'     => ucfirst($this->role),
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
        $html = "<table id='myTable' class='display'>";
        $html .= "<thead><tr>";
        foreach ($headers as $header) {
            $html .= "<th>" . htmlspecialchars($header) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($rows as $sae) {
            $color = $this->getColorForSae($sae);
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
     * Retourne une couleur pastel unique pour chaque SAE
     */
    private function getColorForSae(array $sae): string
    {
        if (($sae['sae_id'] ?? 0) === 0) {
            return "hsl(0, 0%, 95%)"; // neutre pour les étudiants sans SAE
        }
        $key = $sae['sae_id'] ?? $sae['id'] ?? $sae['sae_titre'] ?? 'unknown';
        $hash = crc32($key);
        $hue = $hash % 360;
        return "hsl($hue, 50%, 90%)";
    }
}
