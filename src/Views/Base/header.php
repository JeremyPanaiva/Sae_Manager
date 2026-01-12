<?php

/**
 * Header Template
 *
 * Displays the main header with navigation and user information.
 *
 * Template variables:
 * @var string $CANONICAL_URL Canonical URL of the current page
 * @var string $USERNAME_KEY Display name of the logged-in user
 * @var string $ROLE_KEY User role (e.g., "Étudiant", "Enseignant", "Admin")
 * @var string $ROLE_CLASS CSS class for role badge styling
 * @var string $USER_META_STYLE Inline CSS for user meta section visibility
 * @var string $PROFILE_BTN_STYLE Inline CSS for profile button visibility
 * @var string $LINK_KEY URL for login/logout link
 * @var string $CONNECTION_LINK_KEY Text for connection link (e.g., "Se connecter" or "Se déconnecter")
 * @var string $INSCRIPTION_LINK_KEY URL for registration link
 * @var string $INSCRIPTION_STYLE_KEY Inline CSS for registration button visibility
 * @var string $NAV_STYLE Inline CSS for navigation visibility
 * @var string $DASHBOARD_LINK_KEY URL for the dashboard page
 * @var string $SAE_LINK_KEY URL for the SAE list page
 * @var string $USERS_LINK_KEY URL for the users management page
 *
 * @package SaeManager\Views\Base
 * @author JeremyPanaiva & mohamedDriouchi
 */

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="SAE Manager :  plateforme de suivi et de gestion des SAE pour les étudiants et enseignants d'AMU.">
    <link rel="canonical" href="<?php echo $CANONICAL_URL; ?>">

    <script type="application/ld+json">
        {
            "@context":  "https://schema.org",
            "@graph": [
                {
                    "@type": "WebSite",
                    "@id": "https://sae-manager.alwaysdata.net/#website",
                    "url": "https://sae-manager.alwaysdata.net/",
                    "name": "SAE Manager",
                    "description": "Plateforme de suivi et gestion des SAE pour étudiants et enseignants d'AMU."
                },
                {
                    "@type": "Organization",
                    "@id": "https://sae-manager.alwaysdata.net/#organization",
                    "name": "SAE Manager",
                    "url": "https://sae-manager.alwaysdata.net/"
                }
            ]
        }
    </script>
    <link rel="stylesheet" href="/_assets/css/index.css">

    <link rel="icon" type="image/png" href="/_assets/img/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/_assets/img/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/_assets/img/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/_assets/img/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/_assets/img/favicon/site.webmanifest" />

    <title>SAE Manager</title>
</head>

<body>
    <header class="header">
        <section class="header-content" aria-label="En-tête de la page">
            <section class="logo">
                <a href="/" class="logo-link">
                    <img src="/_assets/img/SM_logo.png" alt="SAE Manager" class="logo-img">
                </a>
            </section>
            <section class="user-info" aria-label="Informations utilisateur">
                <div class="user-meta" style="<?php echo $USER_META_STYLE; ?>">
                    <p>
                        👤 <?php echo $USERNAME_KEY; ?>
                        <span class="role-badge role-<?php echo $ROLE_CLASS; ?>"><?php echo $ROLE_KEY; ?></span>
                    </p>

                </div>
                <div class="user-actions">
                    <a href="/user/profile" class="btn btn-outline header-btn-transparent"
                        style="<?php echo $PROFILE_BTN_STYLE; ?>">Mon
                        profil</a>
                    <a href="<?php echo $LINK_KEY; ?>" class="btn btn-outline header-btn-transparent">
                        <?php echo $CONNECTION_LINK_KEY; ?></a>
                    <a href="<?php echo $INSCRIPTION_LINK_KEY; ?>" class="btn btn-outline"
                        style="<?php echo $INSCRIPTION_STYLE_KEY; ?>">S'inscrire</a>
                </div>
            </section>
        </section>
    </header>

    <nav class="nav" style="<?php echo $NAV_STYLE; ?>" aria-label="Navigation principale">
        <ul class="nav-content">
            <li class="nav-item">
                <a href="<?php echo $DASHBOARD_LINK_KEY; ?>">Tableau de bord</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $SAE_LINK_KEY; ?>">Mes SAE</a>
            </li>
            <?php if ($ROLE_KEY === 'Responsable') : ?>
                <li class="nav-item">
                    <a href="<?php echo $USERS_LINK_KEY; ?>">Utilisateurs</a>
                </li>
            <?php endif; ?>
        </ul>

    </nav>