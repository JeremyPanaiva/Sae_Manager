<?php

namespace Views\User;

use Views\Base\BaseView;

/**
 * User List View
 *
 * Renders a paginated, sortable table of all users in the system.
 * Displays user information with role badges and supports sorting by various fields.
 *
 * Features:
 * - Paginated user list display
 * - Sortable columns (name, email, role, creation date)
 * - Role-based badge styling (etudiant, client, responsable)
 * - Error message display for operation failures
 * - Configurable sort order (ASC/DESC)
 *
 * Typically accessible only to administrators or supervisors.
 *
 * @package Views\User
 */
class UserListView extends BaseView
{
    /**
     * Path to the user list template file
     */
    private const TEMPLATE_PATH = __DIR__ . '/user.php';

    /**
     * Template data key for user table rows HTML
     */
    public const USERS_ROWS_KEY = 'USERS_ROWS';

    /**
     * Template data key for pagination HTML
     */
    public const PAGINATION_KEY = 'PAGINATION';

    /**
     * Template data key for error message HTML
     */
    public const ERROR_MESSAGE_KEY = 'ERROR_MESSAGE';

    /**
     * Array of user data
     *
     * @var array<int, array<string, mixed>>
     */
    private array $users;

    /**
     * Pagination controls HTML
     *
     * @var string
     */
    private string $paginationHtml;

    /**
     * Error message text
     *
     * @var string
     */
    private string $errorMessage;

    /**
     * Current sort column
     *
     * @var string
     */
    private string $sort;

    /**
     * Current sort order (ASC or DESC)
     *
     * @var string
     */
    private string $order;

    /**
     * Constructor
     *
     * @param array<int, array<string, mixed>> $users Array of user data (each containing prenom, nom, mail, role)
     * @param string $paginationHtml HTML for pagination controls
     * @param string $errorMessage Error message to display if operation failed
     * @param string $sort Current sort column (default: 'date_creation')
     * @param string $order Current sort order (default: 'ASC')
     */
    public function __construct(
        array $users,
        string $paginationHtml = '',
        string $errorMessage = '',
        string $sort = 'date_creation',
        string $order = 'ASC'
    ) {
        $this->users = $users;
        $this->paginationHtml = $paginationHtml;
        $this->errorMessage = $errorMessage;
        $this->sort = $sort;
        $this->order = $order;
    }

    /**
     * Returns the path to the user list template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    /**
     * Renders the user list table body with pagination and sorting
     *
     * Generates HTML for:
     * - Error messages if present
     * - User table rows with role badges
     * - Pagination controls
     * - Sort indicators for current column and order
     *
     * Each user row displays:
     * - First name (prenom)
     * - Last name (nom)
     * - Email address (mail)
     * - Role badge with color-coded styling
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        ob_start();
        $PAGINATION = $this->paginationHtml;
        $SORT = $this->sort;
        $ORDER = $this->order;
        $ERROR_MESSAGE = '';

        if ($this->errorMessage) {
            $ERROR_MESSAGE = "<div class='error-message'>" . htmlspecialchars($this->errorMessage) . "</div>";
        }

        $USERS_ROWS = '';
        foreach ($this->users as $user) {
            $valPrenom = $user['prenom'] ?? '';
            $prenom = htmlspecialchars(is_scalar($valPrenom) ? (string) $valPrenom : '');
            $valNom = $user['nom'] ?? '';
            $nom = htmlspecialchars(is_scalar($valNom) ? (string) $valNom : '');
            $valMail = $user['mail'] ?? '';
            $mail = htmlspecialchars(is_scalar($valMail) ? (string) $valMail : '');
            $valRole = $user['role'] ?? 'inconnu';
            $role = strtolower(is_scalar($valRole) ? (string) $valRole : 'inconnu');
            $roleDisplay = htmlspecialchars(ucfirst($role));

            $roleBadge = "<span class='role-badge role-{$role}'>{$roleDisplay}</span>";

            $USERS_ROWS .= "<tr>";
            $USERS_ROWS .= "<td data-label='Prénom'>{$prenom}</td>";
            $USERS_ROWS .= "<td data-label='Nom'>{$nom}</td>";
            $USERS_ROWS .= "<td data-label='Email'>{$mail}</td>";
            $USERS_ROWS .= "<td data-label='Rôle'>{$roleBadge}</td>";
            $USERS_ROWS .= "</tr>";
        }

        include $this->templatePath();
        return (string) ob_get_clean();
    }
}
