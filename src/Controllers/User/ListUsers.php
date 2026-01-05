<?php

namespace Controllers\User;

use Models\User\User;
use Views\User\UserListView;
use Shared\Exceptions\DataBaseException;

class ListUsers
{
    public const PATH = '/user/list';

    public static function support(string $uri, string $method): bool
    {
        return $uri === self::PATH && $method === 'GET';
    }

    public function control(): void
    {
        $userModel = new User();

        $limit = 10;
        $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($currentPage - 1) * $limit;

        // Récupérer les paramètres de tri
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_creation';
        $order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

        try {
            $users = $userModel->getUsersPaginated($limit, $offset, $sort, $order);
            $totalUsers = $userModel->countUsers();
            $totalPages = ceil($totalUsers / $limit);

            // Génération du HTML de pagination avec préservation du tri
            $sortParam = "&sort=$sort&order=$order";
            $paginationHtml = '';

            // Bouton "Précédent"
            if ($currentPage > 1) {
                $paginationHtml .= "<a href='/user/list?page=" . ($currentPage - 1) . $sortParam . "'>‹ Précédent</a>";
            }

            // Première page
            if ($currentPage > 3) {
                $paginationHtml .= "<a href='/user/list?page=1$sortParam'>1</a>";
                if ($currentPage > 4) {
                    $paginationHtml .= "<span>⋯</span>";
                }
            }

            // Pages autour de la page actuelle
            $start = max(1, $currentPage - 2);
            $end = min($totalPages, $currentPage + 2);

            for ($i = $start; $i <= $end; $i++) {
                if ($i == $currentPage) {
                    $paginationHtml .= "<a href='/user/list?page=$i$sortParam' class='active'>$i</a>";
                } else {
                    $paginationHtml .= "<a href='/user/list?page=$i$sortParam'>$i</a>";
                }
            }

            // Dernière page
            if ($currentPage < $totalPages - 2) {
                if ($currentPage < $totalPages - 3) {
                    $paginationHtml .= "<span>⋯</span>";
                }
                $paginationHtml .= "<a href='/user/list?page=$totalPages$sortParam'>$totalPages</a>";
            }

            // Bouton "Suivant"
            if ($currentPage < $totalPages) {
                $paginationHtml .= "<a href='/user/list?page=" . ($currentPage + 1) . $sortParam . "'>Suivant ›</a>";
            }

            // Afficher la vue
            $view = new UserListView($users, $paginationHtml, '', $sort, $order);
            echo $view->render();

        } catch (DataBaseException $e) {
            $view = new UserListView([], '', $e->getMessage());
            echo $view->render();
        }
    }
}