<link rel="stylesheet" href="/_assets/css/dashboard.css">
<script src="/_assets/script/dash.js">
  <?php
  /**
   * @var string $ROLE_KEY
   * @var string $USERNAME_KEY
   * @var string $CONTENT_KEY
   */
    ?>
</script>

<section class="main dashboard-page" aria-label="Tableau de bord">
  <fieldset class="dashboard-section">
    <legend>Tableau de bord - <?php echo $ROLE_KEY; ?></legend>
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