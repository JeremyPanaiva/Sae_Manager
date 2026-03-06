<?php

/**
 * Reset Password Template
 *
 * Displays the password reset form with new password fields.
 *
 * Template variables:
 * @var string $ERROR_MESSAGE HTML error message to display (optional)
 * @var string $token Password reset token from URL parameter
 *
 * @package SaeManager\Views\User
 * @author JeremyPanaiva & mohamedDriouchi
 */

?>

<link rel="stylesheet" href="/_assets/css/inscription.css">
<script src="/_assets/script/showPassword.js"></script>
<section class="main" aria-label="Contenu principal">

    <form method="POST" action="?page=reset-password" onsubmit="disableSubmit(this);">
        <fieldset>
            <legend>Nouveau mot de passe</legend>

            <p>Entrez votre nouveau mot de passe ci-dessous.</p>

            <?= $ERROR_MESSAGE ?>

            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label for="password">Nouveau mot de passe :</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" minlength="12" maxlength="30"
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{12,}" title="Le mot de passe doit contenir entre 12 et 30
                    caractères, une majuscule, une minuscule, un chiffre et un caractère spécial" required
                    placeholder="Votre nouveau mot de passe">
                <span class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>
            <small style="display:block; margin-bottom:15px; color:#666; font-size:0.9em;">
                Minimum 12 caractères avec au moins une majuscule, une minuscule, un chiffre et un caractère spécial
            </small>

            <label for="confirm_password">Confirmer le mot de passe :</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" minlength="12" maxlength="30"
                    required placeholder="Confirmez votre nouveau mot de passe">
                <span class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>
            <input type="submit" value="Réinitialiser le mot de passe">

            <div class="back-link">
                <a href="/user/login">← Retour à la connexion</a>
            </div>
        </fieldset>
    </form>

    <script>
        function disableSubmit(form) {
            const submitBtn = form.querySelector('input[type=submit]');
            submitBtn.disabled = true;
            submitBtn.value = 'Traitement...';
        }
    </script>

</section>