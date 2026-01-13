<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Views\User\UserListView;
use Shared\Exceptions\DataBaseException;

/**
 * User list controller
 *
 * Displays a paginated list of all users in the system with sorting capabilities.
 * Allows sorting by various fields (creation date, name, email, role) in ascending
 * or descending order.  Provides pagination controls for navigating through large
 * user lists.
 *
 * @package Controllers\User
 */
class ListUsers implements ControllerInterface
{
    /**
     * User list page route path
     *
     * @var string
     */
    public const PATH = '/user/list';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $uri The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/list' and method is GET
     */
    public static function support(string $uri, string $method): bool
    {
        return $uri === self::PATH && $method === 'GET';
    }

    /**
     * Main controller method
     *
     * Retrieves a paginated list of users with optional sorting.    Generates pagination
     * controls with ellipsis for large page counts.   Handles database errors gracefully
     * by displaying an empty list with error message.
     *
     * Query parameters:
     * - page: Current page number (default: 1)
     * - sort: Sort field (default: 'date_creation')
     * - order: Sort order 'ASC' or 'DESC' (default: 'ASC')
     *
     * @return void
     */
    public function control(): void
    {
        $userModel = new User();

        // Pagination configuration
        $limit = 10;
        $pageRaw = $_GET['page'] ?? 1;
        $currentPage = is_numeric($pageRaw) ? max(1, (int)$pageRaw) : 1;
        $offset = ($currentPage - 1) * $limit;

        // Extract sorting parameters
        $sortRaw = $_GET['sort'] ?? 'date_creation';
        $sort = is_string($sortRaw) ? $sortRaw : 'date_creation';
        $orderRaw = $_GET['order'] ?? 'ASC';
        $order = is_string($orderRaw) ? strtoupper($orderRaw) : 'ASC';

        try {
            // Retrieve paginated and sorted users
            $users = $userModel->getUsersPaginated($limit, $offset, $sort, $order);
            $totalUsers = $userModel->countUsers();
            $totalPages = (int)ceil($totalUsers / $limit);

            // Generate pagination HTML with sort parameters preserved
            $sortParam = "&sort=$sort&order=$order";
            $paginationHtml = '';

            // Previous button
            if ($currentPage > 1) {
                $paginationHtml .= "<a href='/user/list?page=" . ($currentPage - 1) . $sortParam . "'>‹ Précédent</a>";
            }

            // First page with ellipsis
            if ($currentPage > 3) {
                $paginationHtml .= "<a href='/user/list?page=1$sortParam'>1</a>";
                if ($currentPage > 4) {
                    $paginationHtml .= "<span>⋯</span>";
                }
            }

            // Pages surrounding current page
            $start = max(1, $currentPage - 2);
            $end = min($totalPages, $currentPage + 2);

            for ($i = $start; $i <= $end; $i++) {
                if ($i == $currentPage) {
                    $paginationHtml .= "<a href='/user/list?page=$i$sortParam' class='active'>$i</a>";
                } else {
                    $paginationHtml .= "<a href='/user/list?page=$i$sortParam'>$i</a>";
                }
            }

            // Last page with ellipsis
            if ($currentPage < $totalPages - 2) {
                if ($currentPage < $totalPages - 3) {
                    $paginationHtml .= "<span>⋯</span>";
                }
                $paginationHtml .= "<a href='/user/list?page=$totalPages$sortParam'>$totalPages</a>";
            }

            // Next button
            if ($currentPage < $totalPages) {
                $paginationHtml .= "<a href='/user/list?page=" . ($currentPage + 1) . $sortParam . "'>Suivant ›</a>";
            }

            // Render user list view
            $view = new UserListView($users, $paginationHtml, '', $sort, $order);
            echo $view->render();
        } catch (DataBaseException $e) {
            // Display empty list with database error message
            $view = new UserListView([], '', $e->getMessage());
            echo $view->render();
        }
    }
}
