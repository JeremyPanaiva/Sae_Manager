<?php

namespace Views\User;

use Views\Base\BaseView;

class UserListView extends BaseView
{

    private const TEMPLATE_PATH = __DIR__ . '/user.php';  // ✅ Sans espace

    public const USERS_ROWS_KEY = 'USERS_ROWS';
    public const PAGINATION_KEY = 'PAGINATION';
    public const ERROR_MESSAGE_KEY = 'ERROR_MESSAGE';

    private array $users;
    private string $paginationHtml;
    private string $errorMessage;

    public function __construct(array $users, string $paginationHtml = '', string $errorMessage = '')
    {
        $this->users = $users;
        $this->paginationHtml = $paginationHtml;
        $this->errorMessage = $errorMessage;
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }


    public function renderBody(): string
    {
        ob_start();
        $PAGINATION = $this->paginationHtml;
        $ERROR_MESSAGE = '';

        if ($this->errorMessage) {
            $ERROR_MESSAGE = "<div class='error-message'>" . htmlspecialchars($this->errorMessage) . "</div>";
        }

        $USERS_ROWS = '';
        foreach ($this->users as $user) {
            $prenom = htmlspecialchars($user['prenom'] ?? '');
            $nom = htmlspecialchars($user['nom'] ?? '');
            $mail = htmlspecialchars($user['mail'] ?? '');
            $role = strtolower($user['role'] ??  'inconnu');
            $roleDisplay = htmlspecialchars(ucfirst($role));

            // Créer la pastille de rôle
            $roleBadge = "<span class='role-badge role-{$role}'>{$roleDisplay}</span>";

            $USERS_ROWS .= "<tr>";
            $USERS_ROWS .= "<td>{$prenom}</td>";
            $USERS_ROWS .= "<td>{$nom}</td>";
            $USERS_ROWS .= "<td>{$mail}</td>";
            $USERS_ROWS .= "<td>{$roleBadge}</td>";
            $USERS_ROWS .= "</tr>";
        }

        include $this->templatePath();
        return ob_get_clean();
    }
}