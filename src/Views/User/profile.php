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
            <input type="email" id="mail" name="mail" value="<?= $mail ?? '' ?>" required
                data-original-email="<?= $mail ?? '' ?>">

            <div class="profile-actions"
                style="margin-top: 20px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <a href="/user/change-password" class="btn btn-outline"
                    style="min-width: 200px; text-align: center;">Modifier le mot de passe</a>
                <input type="submit" value="Mettre à jour" class="btn btn-primary" style="min-width: 200px;">
            </div>
        </form>

        <script>
            document.querySelector('.profile-form').addEventListener('submit', function (e) {
                const mailInput = document.getElementById('mail');
                const originalMail = mailInput.dataset.originalEmail;

                if (mailInput.value !== originalMail) {
                    const confirmMessage = "⚠️ ATTENTION : CHANGEMENT D'EMAIL\n\n" +
                        "Vous êtes sur le point de modifier votre adresse email.\n" +
                        "Si vous confirmez :\n\n" +
                        "1. Vous serez immédiatement DÉCONNECTÉ.\n" +
                        "2. Un email de vérification sera envoyé à la nouvelle adresse (" + mailInput.value + ").\n" +
                        "3. Si vous avez fait une erreur de saisie, " +
                        "vous risquez de PERDRE L'ACCÈS À VOTRE COMPTE.\n\n" +
                        "Êtes-vous sûr que la nouvelle adresse est correcte ?";

                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                    }
                }
            });
        </script>

        <!-- Zone de suppression du compte -->
        <div class="danger-zone">
            <h3>Supprimer votre compte SAE Manager</h3>
            <p>La suppression de votre compte est <strong>définitive et irréversible</strong>. Toutes vos données seront
                supprimées.</p>

            <form action="/user/profile/delete" method="POST" class="delete-form"
                style="display: flex; justify-content: center;"
                onsubmit="return confirm('⚠️ ATTENTION : Cette action est irréversible.' +
                 '\n\nÊtes-vous absolument certain de vouloir supprimer votre compte définitivement ?\n\n' +
                  'Toutes vos SAE, to-do lists, avis et données personnelles seront perdues.');">
                <button type="submit" class="btn btn-danger" style="min-width: 200px;">Supprimer mon compte</button>
            </form>
        </div>
    </section>
</main>