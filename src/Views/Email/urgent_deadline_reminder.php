<?php

/**
 * Email urgent : dernier jour pour la remise d'une SAE.
 *
 * @var string $STUDENT_NAME
 * @var string $RESPONSABLE_NAME
 * @var string $SAE_TITLE
 * @var string $DATE_RENDU
 * @var string $HEURE_RENDU
 * @var string $SAE_URL
 */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rappel Urgent : Rendu SAE</title>
</head>

<body style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
<div style="max-width: 600px; margin: 20px auto; padding: 20px; background-color: #fcfcfc;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #eee;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);">

        <div style="background-color: #d35400; color: white; padding: 20px; text-align: center;
                border-radius: 8px 8px 0 0; margin: -30px -30px 20px -30px;">
            <h1 style="margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px;">
                ⚠️ Rappel : Dernier jour
            </h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;">Clôture des dépôts imminente</p>
        </div>

        <p>Bonjour <strong><?= htmlspecialchars($STUDENT_NAME) ?></strong>,</p>

        <p>Ceci est un message automatique pour vous informer que la date limite de rendu pour votre
            <strong>SAE</strong> arrive à échéance.</p>

        <div style="background-color: #fff5eb; border: 2px solid #f39c12; padding: 20px; border-radius: 8px;
                text-align: center; margin: 25px 0;">
                <span style="color: #d35400; font-size: 14px; font-weight: bold; text-transform: uppercase;">
                    Échéance finale
                </span>
            <div style="font-size: 24px; color: #a04000; font-weight: bold; margin: 5px 0;">
                Demain à <?= htmlspecialchars($HEURE_RENDU) ?>
            </div>
            <div style="color: #666; font-size: 14px;">(<?= htmlspecialchars($DATE_RENDU) ?>)</div>
        </div>

        <div style="margin-bottom: 25px;">
            <p style="margin: 5px 0;"><strong>Projet :</strong> <?= htmlspecialchars($SAE_TITLE) ?></p>
            <p style="margin: 5px 0;"><strong>Responsable :</strong> <?= htmlspecialchars($RESPONSABLE_NAME) ?></p>
        </div>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #bdc3c7;">
            <p style="margin-top: 0; font-weight: bold; color: #2c3e50;">Avant de rendre, vérifiez :</p>
            <ul style="margin-bottom: 0; padding-left: 20px; color: #555;">
                <li>Le format de vos fichiers (PDF, ZIP, etc.).</li>
                <li>Que tous les documents requis sont présents.</li>
                <li>Le bon fonctionnement des liens éventuels.</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 35px 0;">
            <a href="<?= htmlspecialchars($SAE_URL) ?>"
               style="display: inline-block; padding: 14px 35px; background-color: #d35400; color: #ffffff;
                          text-decoration: none; border-radius: 5px; font-weight: bold;
                          box-shadow: 0 2px 5px rgba(211, 84, 0, 0.3);">
                Accéder au dépôt
            </a>
        </div>

        <p style="color: #e74c3c; font-size: 13px; text-align: center;">
            <em>Attention : Tout retard pourra entraîner des pénalités sur la note finale.</em>
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

        <p style="font-size: 0.85em; color: #95a5a6; text-align: center; line-height: 1.4;">
            Cordialement,<br>
            <strong>L'équipe SAE Manager</strong><br>
            Ceci est un mail automatique, merci de ne pas y répondre.
        </p>
    </div>
</div>
</body>

</html>