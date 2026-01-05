<?php
// Définir les variables par défaut si elles n'existent pas
$SORT = $SORT ?? 'date_creation';
$ORDER = $ORDER ?? 'ASC';
$ERROR_MESSAGE = $ERROR_MESSAGE ?? '';
$USERS_ROWS = $USERS_ROWS ??  '';
$PAGINATION = $PAGINATION ?? '';
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