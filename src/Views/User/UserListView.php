<?php

namespace Views\User;

use Views\Base\BaseView;

class UserListView extends BaseView
{

    private const TEMPLATE_PATH = __DIR__ . '/user.php';

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
        return self::TEMPLATE_PATH;
    }

    public function templateKeys(): array
    {
        return []; // Pas utilisé
    }

    public function renderBody(): string
    {
        ob_start();
        $PAGINATION = $this->paginationHtml;

        // La boucle se fait maintenant directement dans le template PHP, 
        // ou on prépare les variables ici. 
        // Pour rester simple et efficace : on passe juste $users au template si on veut boucler dedans, 
        // Ou on garde la logique de pré-calcul si le template attend une string.
        // Le template user.html attendait {{USERS_ROWS}}, donc $USERS_ROWS_KEY.

        $USERS_ROWS = '';
        foreach ($this->users as $user) {
            $USERS_ROWS .= "<tr><td>{$user['prenom']}</td><td>{$user['nom']}</td></tr>";
        }

        include $this->templatePath();
        return ob_get_clean();
    }
}
