<link rel="stylesheet" href="/_assets/css/user.css">

<main class="dashboard-page">
    <section class="dashboard-section">
        <h2>Mon profil</h2>

        <?= $ERRORS_KEY ?? '' ?>
        <?= $SUCCESS_KEY ?? '' ?>

        <p><strong>Date de création du compte : </strong> <?= $date_creation ?? '' ?></p>

        <form action="/user/profile" method="POST" class="profile-form">
            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" value="<?= $prenom ?? '' ?>" required>

            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" value="<?= $nom ?? '' ?>" required>

            <label for="mail">Email :</label>
            <input type="email" id="mail" name="mail" value="<?= $mail ?? '' ?>" required>

            <p>
                <a href="/user/forgot-password" class="btn btn-outline">Modifier le mot de passe</a>
            </p>

            <input type="submit" value="Mettre à jour" class="btn btn-primary">
        </form>

        <!-- Zone de suppression du compte -->
        <div class="danger-zone">
            <h3>Zone dangereuse</h3>
            <p>La suppression de votre compte est <strong>définitive et irréversible</strong>. Toutes vos données seront
                supprimées.</p>

            <form action="/user/profile/delete" method="POST" class="delete-form"
                onsubmit="return confirm('⚠️ ATTENTION : Cette action est IRRÉVERSIBLE.\n\nÊtes-vous absolument certain de vouloir supprimer votre compte définitivement ?\n\nToutes vos SAE, to-do lists, avis et données personnelles seront perdues.');">
                <button type="submit" class="btn btn-danger">Supprimer définitivement mon compte</button>
            </form>
        </div>
    </section>
</main>