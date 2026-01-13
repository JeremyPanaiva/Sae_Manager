<?php
/**
 * Error Message Template
 *
 * @var string $MESSAGE_KEY
 *
 * Template variables:
 * - $MESSAGE_KEY: The error message text to display
 */

?>

<link rel="stylesheet" href="/_assets/css/error.css">

<li class="error-message">
    <?php echo $MESSAGE_KEY; ?>
</li>