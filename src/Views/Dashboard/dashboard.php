<?php

/**
 * Dashboard Template
 *
 * @var string $ROLE_KEY User role
 * @var string $USERNAME_KEY User name
 * @var string $CONTENT_KEY Dynamic content
 */

/** @var string $ROLE_KEY */
/** @var string $USERNAME_KEY */
/** @var string $CONTENT_KEY */

?>
<link rel="stylesheet" href="/_assets/css/dashboard.css">

<section class="main dashboard-page" aria-label="Tableau de bord">
    <fieldset class="dashboard-section">
        <legend>Dashboard - <?php echo $ROLE_KEY; ?></legend>
        <div class="user-info">
            <p><strong>Nom :</strong> <?php echo $USERNAME_KEY; ?></p>
            <p><strong>Rôle :</strong> <?php echo $ROLE_KEY; ?></p>
        </div>
        <hr>
        <div class="dashboard-content">
            <?php echo $CONTENT_KEY; ?>
        </div>
    </fieldset>
</section>