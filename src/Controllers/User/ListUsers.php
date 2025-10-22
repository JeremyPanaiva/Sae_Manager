<?php

namespace Controllers\User;

use Models\User\User;
use Views\User\UserListView;
use Views\Base\HeaderView;

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
        $header = new HeaderView();

        $limit = 10; // a modifier plus tard pour choisir sa limite voir un variable ^pur laisser le choix l'user 
        $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($currentPage - 1) * $limit;

        $users = $userModel->getUsersPaginated($limit, $offset);
        $totalUsers = $userModel->countUsers();
        $totalPages = ceil($totalUsers / $limit);

        // Génération du HTML de pagination
        $paginationHtml = '';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? "style='font-weight:bold;'" : "";
            $paginationHtml .= "<a href='/user/list?page=$i' $active>$i</a>";
        }

        // Données d’en-tête
        $headerData = $header->templateKeys();

        // Afficher la vue
        $view = new UserListView($users, $paginationHtml, $headerData);
        echo $view->render();
    }
}
