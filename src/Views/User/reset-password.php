<link rel="stylesheet" href="/_assets/css/inscription.css">
<section class="main" aria-label="Contenu principal">

    <form method="POST" action="?page=reset-password"
        onsubmit="document.querySelector('input[type=submit]').disabled = true; document.querySelector('input[type=submit]').value = 'Traitement...';">
        <fieldset>
            <legend>Nouveau mot de passe</legend>

            <p>Entrez votre nouveau mot de passe ci-dessous.</p>

            <?= $ERROR_MESSAGE ?? '' ?>

            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <label for="password">Nouveau mot de passe :</label>
            <input type="password" id="password" name="password" required placeholder="Votre nouveau mot de passe"
                minlength="8">

            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required
                placeholder="Confirmez votre nouveau mot de passe" minlength="8">

            <input type="submit" value="Réinitialiser le mot de passe">

            <div class="back-link">
                <a href="/user/login">← Retour à la connexion</a>
            </div>
        </fieldset>
    </form>

</section>