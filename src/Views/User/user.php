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

        <!-- Mobile sort controls -->
        <div class="mobile-sort-controls">
            <label for="mobile-sort">Trier par :</label>
            <select id="mobile-sort" onchange="window.location.href=this.value">
                <option value="?sort=prenom&order=<?= ($SORT === 'prenom' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                    <?= $SORT === 'prenom' ? 'selected' : '' ?>>
                    Prénom
                </option>
                <option value="?sort=nom&order=<?= ($SORT === 'nom' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                    <?= $SORT === 'nom' ? 'selected' : '' ?>>
                    Nom
                </option>
                <option value="?sort=mail&order=<?= ($SORT === 'mail' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                    <?= $SORT === 'mail' ? 'selected' : '' ?>>
                    Email
                </option>
                <option value="?sort=role&order=<?= ($SORT === 'role' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                    <?= $SORT === 'role' ? 'selected' : '' ?>>
                    Rôle
                </option>
            </select>
        </div>

        <table>
            <thead>
                <tr>
                    <th>
                        <a href="?sort=prenom&order=<?= ($SORT === 'prenom' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                            class="sort-link<?= $SORT === 'prenom' ? ' active' : '' ?>">
                            Prénom <span class="sort-icon">
                                <?= $SORT === 'prenom' ? ($ORDER === 'ASC' ? '▲' : '▼') : '⇅' ?>
                            </span>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=nom&order=<?= ($SORT === 'nom' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                            class="sort-link<?= $SORT === 'nom' ? ' active' : '' ?>">
                            Nom <span
                                class="sort-icon"><?= $SORT === 'nom' ? ($ORDER === 'ASC' ? '▲' : '▼') : '⇅' ?></span>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=mail&order=<?= ($SORT === 'mail' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                            class="sort-link<?= $SORT === 'mail' ? ' active' : '' ?>">
                            Email <span
                                class="sort-icon"><?= $SORT === 'mail' ? ($ORDER === 'ASC' ? '▲' : '▼') : '⇅' ?></span>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=role&order=<?= ($SORT === 'role' && $ORDER === 'ASC') ? 'DESC' : 'ASC' ?>"
                            class="sort-link<?= $SORT === 'role' ? ' active' : '' ?>">
                            Rôle <span
                                class="sort-icon"><?= $SORT === 'role' ? ($ORDER === 'ASC' ? '▲' : '▼') : '⇅' ?></span>
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