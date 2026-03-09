<link rel="stylesheet" href="/_assets/css/inscription.css">
<script src="/_assets/script/showPassword.js"></script>


<section class="main" aria-label="Contenu principal">
    <form action="/user/change-password" method="POST">
        <?php echo \Shared\CsrfGuard::getHiddenField(); ?>
        <fieldset>
            <legend>Changer mon mot de passe</legend>

            <?= $ERROR_MESSAGE ?? '' ?>
            <?= $SUCCESS_MESSAGE ?? '' ?>

            <label for="old_password">Mot de passe actuel :</label>
            <div class="password-wrapper">
                <input type="password" id="old_password" name="old_password" required
                    placeholder="Votre mot de passe actuel">
                <span class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>

            <label for="new_password">Nouveau mot de passe :</label>
            <div class="password-wrapper">
                <input type="password" id="new_password" name="new_password"
                    minlength="12" maxlength="30"
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+€£µ§?/\\|{}\[\]]).{12,}"
                    title="Le mot de passe doit contenir entre 12 et 30 caractères,
                       une majuscule, une minuscule, un chiffre et l'un de ces
                       caractères spéciaux : ! @ # $ % ^ & * ( ) _ + € £ µ § ? / \\ | { } [ ]"
                    required placeholder="Nouveau mot de passe">
                <span class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>
            <small style="display:block; margin-bottom:15px; color:#666; font-size:0.9em;">
                Minimum 12 caractères avec au moins une majuscule, une minuscule,
                un chiffre et l'un de ces caractères spéciaux :
                ! @ # $ % ^ & * ( ) _ + € £ µ § ? / \ | { } [ ]
            </small>

            <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" required
                    placeholder="Confirmer le nouveau mot de passe">
                <span class="toggle-password" aria-label="Afficher/masquer le mot de passe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
            </div>

            <input type="submit" value="Enregistrer le nouveau mot de passe">

            <div class="back-link" style="margin-top: 15px; text-align: center;">
                <a href="/user/profile">← Retour au profil</a>
            </div>
        </fieldset>
    </form>
</section>