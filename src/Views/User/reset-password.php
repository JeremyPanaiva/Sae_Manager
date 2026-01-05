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
            <input type="password" id="password" name="password" 
                   minlength="8" maxlength="20"
                   pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}"
                   title="Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre"
                   required 
                   placeholder="Votre nouveau mot de passe">
            <small style="display:block; margin-bottom:15px; color:#666; font-size:0.9em;">
                Minimum 8 caractères avec au moins une majuscule, une minuscule et un chiffre
            </small>

            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" 
                   minlength="8" maxlength="20"
                   required
                   placeholder="Confirmez votre nouveau mot de passe">

            <input type="submit" value="Réinitialiser le mot de passe">

            <div class="back-link">
                <a href="/user/login">← Retour à la connexion</a>
            </div>
        </fieldset>
    </form>

</section>