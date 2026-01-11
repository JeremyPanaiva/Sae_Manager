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
     * Converts URLs in text to clickable links
     *
     * @param string $text
     * @return string
     */
    private function rendreLiensCliquables(string $text): string
    {
        $pattern = '/(https?:\/\/[^\s]+)/i';
        $replacement = '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>';
        $result = preg_replace($pattern, $replacement, $text);

        return $result !== null ? $result : $text;
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
            $userId = (int) $_SESSION['user']['id'];
        }

        // Rest of your dashboard rendering logic...
        // Using $userId safely now

        ob_start();
        extract($this->data);
        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output :  '';
    }
}