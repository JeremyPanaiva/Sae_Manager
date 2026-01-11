<?php

/** @var string $SUCCESS_MESSAGE_KEY */
/** @var string $ERRORS_KEY */

?>
<link rel="stylesheet" href="/_assets/css/inscription.css">
<script src="/_assets/script/showPassword.js"></script>

<section class="main" aria-label="Contenu principal">
    <form method="POST" action="/user/login">
        <fieldset>
            <?= $SUCCESS_MESSAGE_KEY ?>
            <?= $ERRORS_KEY ?>
            <legend>Connexion</legend>

            <label for="uname">Email :</label>
            <input type="email" id="uname" name="uname" required placeholder="exemple@etu.univ-amu.fr">

            <label for="psw">Mot de passe :</label>
            <div class="password-wrapper">
                <input type="password" id="psw" name="psw" required placeholder="Votre mot de passe">
                <span class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                         stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>

            <div class="forgot-password-link">
                <a href="/user/forgot-password">Mot de passe oublié ?</a>
            </div>

            <input type="submit" value="Se connecter" name="ok">
        </fieldset>

        <p class="form-footer">
            Pas encore de compte ? <a href="/user/register">Créer un compte</a>
        </p>
    </form>
</section>