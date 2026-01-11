<?php

/**
 * SAE Page Template
 *
 * Displays the SAE management page with user information and dynamic content.
 *
 * Template variables:
 * @var string $ROLE_KEY User role (e.g., "Étudiant", "Enseignant", "Admin")
 * @var string $USERNAME_KEY Display name of the logged-in user
 * @var string $CONTENT_KEY Dynamic HTML content for the SAE page
 *
 * @package SaeManager\Views\Sae
 * @author JeremyPanaiva & mohamedDriouchi
 */

?>

<link rel="stylesheet" href="/_assets/css/sae.css">

<section class="main sae-page" aria-label="Gestion des SAE">
    <fieldset class="sae-section">
        <legend>SAE - <?php echo $ROLE_KEY; ?></legend>
        <div class="user-info">
            <p><strong>Nom :</strong> <?php echo $USERNAME_KEY; ?></p>
            <p><strong>Rôle :</strong> <?php echo $ROLE_KEY; ?></p>
        </div>
        <hr>
        <div class="sae-content">
            <?php echo $CONTENT_KEY; ?>
        </div>
    </fieldset>
</section>

