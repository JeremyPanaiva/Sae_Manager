<?php

/**
 * Footer Template
 *
 * Displays the main footer with legal links and site navigation.
 *
 * Template variables:
 * @var string $LEGAL_LINK_KEY URL to the legal notices page
 * @var string $PLAN_LINK_KEY URL to the sitemap page
 *
 * @package SaeManager\Views\Base
 * @author JeremyPanaiva & mohamedDriouchi
 */

?>

<footer class="footer">
    <section class="footer-content" aria-label="Pied de page">
        <section class="footer-left" aria-label="Informations légales">
            <p>&copy; 2025 SAE Manager – Tous droits réservés</p>
        </section>

        <section class="footer-right" aria-label="Liens utiles">
            <a href="<?php echo $LEGAL_LINK_KEY; ?>">Mentions légales</a>
            <a href="<?php echo $PLAN_LINK_KEY; ?>">Plan du site</a>
            <a href="/contact">Contact</a>
        </section>
    </section>
</footer>

</body>
</html>