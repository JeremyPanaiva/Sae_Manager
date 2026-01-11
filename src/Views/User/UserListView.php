<?php

namespace Views\User;

use Views\Base\BaseView;

/**
 * User List View
 *
 * @package Views\User
 */
class UserListView extends BaseView
{
    private const TEMPLATE_PATH = __DIR__ . '/user. php';

    public const USERS_ROWS_KEY = 'USERS_ROWS';
    public const PAGINATION_KEY = 'PAGINATION';
    public const ERROR_MESSAGE_KEY = 'ERROR_MESSAGE';

    /**
     * Array of user data
     *
     * @var array<int, array<string, mixed>>
     */
    private array $users;

    private string $paginationHtml;
    private string $errorMessage;
    private string $sort;
    private string $order;

    /**
     * Constructor
     *
     * @param array<int, array<string, mixed>> $users
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

    /**
     * Returns template path
     *
     * @return string
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    /**
     * Renders user list body
     *
     * @return string
     */
    public function renderBody(): string
    {
        ob_start();
        $PAGINATION = $this->paginationHtml;
        $ERROR_MESSAGE = $this->errorMessage;
        $SORT = $this->sort;
        $ORDER = $this->order;

        // Generate user rows
        $USERS_ROWS = '';
        foreach ($this->users as $user) {
            // Build rows HTML
        }

        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }
}