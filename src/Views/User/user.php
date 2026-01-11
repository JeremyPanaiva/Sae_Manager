<?php

/**
 * User List Template
 *
 * Displays a sortable table of all users with pagination.
 *
 * Template variables:
 * @var string $SORT Current sort column (e.g., "prenom", "nom", "mail", "role", "date_creation")
 * @var string $ORDER Current sort order ("ASC" or "DESC")
 * @var string $ERROR_MESSAGE HTML error message to display (empty string if no error)
 * @var string $USERS_ROWS HTML table rows containing user data
 * @var string $PAGINATION HTML pagination controls
 *
 * @package SaeManager\Views\User
 * @author JeremyPanaiva & mohamedDriouchi
 */
?>
<link rel="stylesheet" href="/_assets/css/user.css">

<main class="dashboard-page">
    <section class="users">
        <h2>Liste des utilisateurs</h2>

        <?= $ERROR_MESSAGE ?>

        <table>
            <thead>
            <tr>
                <th>
                    <a href="?sort=prenom&order=<?= ($SORT === 'prenom' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                           class="sort-link<?= $SORT === 'prenom' ?  ' active' : '' ?>">
                        Prénom <?= $SORT === 'prenom' ? ($ORDER === 'ASC' ?  '▲' : '▼') : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=nom&order=<?= ($SORT === 'nom' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                           class="sort-link<?= $SORT === 'nom' ? ' active' : '' ?>">
                        Nom <?= $SORT === 'nom' ? ($ORDER === 'ASC' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=mail&order=<?= ($SORT === 'mail' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                       class="sort-link<?= $SORT === 'mail' ? ' active' : '' ?>">
                        Email <?= $SORT === 'mail' ? ($ORDER === 'ASC' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=role&order=<?= ($SORT === 'role' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                       class="sort-link<?= $SORT === 'role' ? ' active' : '' ?>">
                        Rôle <?= $SORT === 'role' ?  ($ORDER === 'ASC' ? '▲' : '▼') : '' ?>
                    </a>
                </th>
            </tr>
            </thead>
            <tbody>
            <?= $USERS_ROWS ?>
            </tbody>
        </table>
        <div class="pagination">
            <?= $PAGINATION ?>
        </div>
    </section>
</main>