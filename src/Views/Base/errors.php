<?php
/**
 * Errors List Template
 *
 * Displays a list of error messages in a styled unordered list.
 * Used to render multiple validation or error messages together in the UI.
 *
 * Expected Variables:
 * - $ERRORS_KEY:  HTML string containing <li> error message elements
 */
?>

<link rel="stylesheet" href="/_assets/css/error.css">

<ul class="errors-list">
    <?php echo $ERRORS_KEY; ?>
</ul>