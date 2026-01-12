<?php

/**
 * Forgot Password Template
 *
 * Displays the password reset request form where users enter their email.
 *
 * Template variables:
 * @var string $SUCCESS_MESSAGE HTML success message to display (optional)
 * @var string $ERROR_MESSAGE HTML error message to display (optional)
 *
 * @package SaeManager\Views\User
 * @author JeremyPanaiva & mohamedDriouchi
 */

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="/_assets/css/inscription.css">
</head>

<body>

    <section class="main" aria-label="Contenu principal">

        <form method="POST" action="/user/forgot-password">
            <fieldset>
                <legend>Mot de passe oublié</legend>

                <p>Saisissez votre adresse email pour recevoir un lien de réinitialisation.</p>

                <?= $SUCCESS_MESSAGE ?>
                <?= $ERROR_MESSAGE ?>
                <label for="email">Adresse email :</label>
                <input type="email" id="email" name="email" required placeholder="votre.email@etu.univ-amu.fr">

                <input type="submit" value="Envoyer le lien">

                <div class="back-link">
                    <a href="/user/login">← Retour à la connexion</a>
                </div>
            </fieldset>
        </form>

    </section>

</body>

</html>