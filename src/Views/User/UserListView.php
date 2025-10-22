<?php

namespace Views\User;

use Views\Base\BaseView;

class UserListView extends BaseView {

    private const TEMPLATE_HTML = __DIR__ . '/user.html';

    public const USERS_ROWS_KEY = 'USERS_ROWS';
    public const PAGINATION_KEY = 'PAGINATION';

    private array $users;
    private string $paginationHtml;

    public function __construct(array $users, string $paginationHtml = '')
    {
        $this->users = $users;
        $this->paginationHtml = $paginationHtml;
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }

    public function templateKeys(): array
    {
        // Génération des lignes du tableau
        $rowsHtml = '';
        foreach ($this->users as $user) {
            $rowsHtml .= "<tr><td>{$user['prenom']}</td><td>{$user['nom']}</td></tr>";
        }

        return [
            self::USERS_ROWS_KEY => $rowsHtml,
            self::PAGINATION_KEY => $this->paginationHtml,
        ];
    }
}
