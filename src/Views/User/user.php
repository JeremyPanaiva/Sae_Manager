<link rel="stylesheet" href="/_assets/css/user.css">

<main>
    <section class="users">
        <h2>Liste des utilisateurs</h2>
        <table>
            <thead>
                <tr>
                    <th>Prenom</th>
                    <th>Nom</th>
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