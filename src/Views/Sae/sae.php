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

