<?php

/**
 * Error Message Template
 *
 * Template variables:
 * @var string $MESSAGE_KEY The error message text to display
 */

/** @var string $MESSAGE_KEY */

?>

<link rel="stylesheet" href="/_assets/css/error.css">

<li class="error-message">
    <?php echo $MESSAGE_KEY; ?>
</li>