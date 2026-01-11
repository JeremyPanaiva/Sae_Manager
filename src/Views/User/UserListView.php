<?php

namespace Views\User;

use Views\Base\BaseView;

class UserListView extends BaseView
{
    private const TEMPLATE_PATH = __DIR__ . '/user.php';

    public const USERS_ROWS_KEY = 'USERS_ROWS';
    public const PAGINATION_KEY = 'PAGINATION';
    public const ERROR_MESSAGE_KEY = 'ERROR_MESSAGE';

    /**
     * @var array<int, array<string, string|null>>
     */
    private array $users;
    private string $paginationHtml;
    private string $errorMessage;
    private string $sort;
    private string $order;

    /**
     * @param array<int, array<string, string|null>> $users
     * @param string $paginationHtml
     * @param string $errorMessage
     * @param string $sort
     * @param string $order
     */
    public function __construct(
        array $users,
        string $paginationHtml = '',
        string $errorMessage = '',
        string $sort = 'date_creation',
        string $order = 'ASC'
    ) {
        parent::__construct();
        $this->users = $users;
        $this->paginationHtml = $paginationHtml;
        $this->errorMessage = $errorMessage;
        $this->sort = $sort;
        $this->order = $order;
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    public function renderBody(): string
    {
        ob_start();
        $PAGINATION = $this->paginationHtml;
        $ERROR_MESSAGE = $this->errorMessage;
        $SORT = $this->sort;
        $ORDER = $this->order;

        $USERS_ROWS = '';
        foreach ($this->users as $user) {
            $prenom = htmlspecialchars($user['prenom'] ?? '');
            $nom = htmlspecialchars($user['nom'] ?? '');
            $mail = htmlspecialchars($user['mail'] ?? '');
            $role = htmlspecialchars($user['role'] ?? '');

            $USERS_ROWS .= "<tr>
                <td>{$prenom}</td>
                <td>{$nom}</td>
                <td>{$mail}</td>
                <td><span class='role-badge role-{$role}'>{$role}</span></td>
            </tr>";
        }

        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }
}