<link rel="stylesheet" href="/_assets/css/legal.css">
<link rel="stylesheet" href="/_assets/css/contact.css">

<main class="legal-main">
    <header class="legal-header">
        <h1 class="legal-title">Contact</h1>
    </header>

    <section class="legal-content">
        <?php echo $MESSAGE_BLOCK; ?>

        <article class="legal-section">
            <h2 class="legal-subtitle">Nous écrire</h2>
            <p>Remplissez le formulaire ci-dessous pour nous contacter.</p>

            <form method="POST" action="/contact" class="legal-form" novalidate>
                <div class="legal-form-group">
                    <label for="contact-email">Votre email</label>
                    <input type="email" id="contact-email" name="email" required placeholder="votre.email@example.com">
                </div>

                <div class="legal-form-group">
                    <label for="contact-subject">Sujet</label>
                    <input type="text" id="contact-subject" name="subject"
                           required placeholder="Sujet de votre message">
                </div>

                <div class="legal-form-group">
                    <label for="contact-message">Votre message</label>
                    <textarea id="contact-message" name="message" rows="8" required
                              placeholder="Écrivez votre message ici..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Envoyer</button>
            </form>
        </article>

        <article class="legal-section">
            <h2 class="legal-subtitle">Adresse de destination</h2>
            <p>Les messages sont envoyés à l’adresse <strong>sae-manager@alwaysdata.net</strong></p>
        </article>
    </section>
</main>