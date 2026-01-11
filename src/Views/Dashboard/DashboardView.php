<?php

namespace Views\Dashboard;

use Views\Base\BaseView;

/**
 * Dashboard View
 *
 * @package Views\Dashboard
 */
class DashboardView extends BaseView
{
    /**
     * Template data
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct();
        $this->data = $data;
    }

    /**
     * Returns template path
     *
     * @return string
     */
    public function templatePath(): string
    {
        return __DIR__ . '/dashboard.php';
    }

    /**
     * Renders the dashboard body
     *
     * @return string
     */
    public function renderBody(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = null;
        if (
            isset($_SESSION['user']) &&
            is_array($_SESSION['user']) &&
            isset($_SESSION['user']['id'])
        ) {
            $userIdRaw = $_SESSION['user']['id'];
            $userId = is_int($userIdRaw) ? $userIdRaw : (is_numeric($userIdRaw) ? (int) $userIdRaw : null);
        }

        ob_start();
        extract($this->data);
        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }
}
