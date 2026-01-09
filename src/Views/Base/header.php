<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="SAE Manager : plateforme de suivi et de gestion des SAE pour les étudiants et enseignants d'AMU.">
    <!-- Canonical -->
    <link rel="canonical" href="<?php echo $CANONICAL_URL; ?>">

    <!-- JSON-LD -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
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

    <!-- Favicons -->
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

    <nav class="nav" style="<?php echo $NAV_STYLE; ?>" aria-label="Contenu principal">
        <ul class="nav-content">
            <li class="nav-item <?php echo $ACTIVE_DASHBOARD; ?>">
                <a href="<?php echo $DASHBOARD_LINK_KEY; ?>">Tableau de bord</a>
            </li>
            <li class="nav-item <?php echo $ACTIVE_SAE; ?>">
                <a href="<?php echo $SAE_LINK_KEY; ?>">SAEs</a>
            </li>
            <li class="nav-item <?php echo $ACTIVE_USERS; ?>">
                <a href="<?php echo $USERS_LINK_KEY; ?>">Utilisateurs</a>
            </li>
        </ul>
    </nav>