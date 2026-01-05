<link rel="stylesheet" href="/_assets/css/user.css">

<main class="dashboard-page">
    <section class="users">
        <h2>Liste des utilisateurs</h2>

        <?= $ERROR_MESSAGE ??  '' ?>

        <table>
            <thead>
            <tr>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
            </tr>
            </thead>
            <tbody>
            <?= $USERS_ROWS ?? '' ?>
            </tbody>
        </table>
        <div class="pagination">
            <?= $PAGINATION ?? '' ?>
        </div>
    </section>
</main>