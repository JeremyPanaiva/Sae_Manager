<link rel="stylesheet" href="/_assets/css/inscription.css">

<section class="main" aria-label="Contenu principal">
    <form action="/user/change-password" method="POST">
        <fieldset>
            <legend>Changer mon mot de passe</legend>

            <?= $ERROR_MESSAGE ?? '' ?>
            <?= $SUCCESS_MESSAGE ?? '' ?>

            <label for="old_password">Ancien mot de passe :</label>
            <input type="password" id="old_password" name="old_password" required
                placeholder="Votre mot de passe actuel">

            <label for="new_password">Nouveau mot de passe :</label>
            <input type="password" id="new_password" name="new_password" minlength="8" required
                placeholder="Nouveau mot de passe">
            <small style="display:block; margin-bottom:15px; color:#666; font-size:0.9em;">Minimum 8 caractères</small>

            <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required
                placeholder="Confirmer le nouveau mot de passe">

            <input type="submit" value="Enregistrer le nouveau mot de passe">

            <div class="back-link" style="margin-top: 15px; text-align: center;">
                <a href="/user/profile">← Retour au profil</a>
            </div>
        </fieldset>
    </form>
</section>