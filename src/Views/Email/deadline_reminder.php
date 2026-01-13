<?php

/**
 * Email de rappel à J-3 avant la remise d'une SAE.
 *
 * @var string $CLIENT_NAME
 * @var string $RESPONSABLE_NAME
 * @var string $SAE_TITLE
 * @var string $STUDENT_NAME
 * @var string $SAE_URL
 * @var string $DATE_RENDU
 * @var string $HEURE_RENDU
 */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rappel d'échéance SAE</title>
</head>

<body style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
<div style="max-width: 600px; margin: 20px auto; padding: 20px; background-color: #f9f9f9;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);">

        <div style="text-align: center; border-bottom: 1px solid #eee; margin-bottom: 25px; padding-bottom: 25px;">
            <h1 style="color: #2c3e50; margin: 0; font-size: 22px;">Rappel d'échéance - Projet SAE</h1>
            <div style="background-color: #2980b9; color: white; padding: 6px 14px; border-radius: 4px;
                    display: inline-block; margin-top: 15px; font-size: 13px; font-weight: 500;
                    text-transform: uppercase; letter-spacing: 0.5px;">
                Échéance dans 3 jours
            </div>
        </div>

        <p>Bonjour <strong><?= htmlspecialchars($STUDENT_NAME) ?></strong>,</p>

        <p>Nous vous rappelons que la date limite de rendu pour votre Situation d'Apprentissage et
            d'Évaluation (SAE) arrive prochainement à son terme.</p>

        <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 15px;
                border-radius: 6px; text-align: center; margin: 25px 0; font-size: 16px;">
            Dépôt attendu le <strong><?= htmlspecialchars($DATE_RENDU) ?></strong>
            <?php if (!empty($HEURE_RENDU)) : ?>
                à <strong><?= htmlspecialchars($HEURE_RENDU) ?></strong>
            <?php endif; ?>
        </div>

        <div style="margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>SAE :</strong> <?= htmlspecialchars($SAE_TITLE) ?></p>
            <p style="margin: 5px 0;"><strong>Responsable :</strong> <?= htmlspecialchars($RESPONSABLE_NAME) ?></p>
            <p style="margin: 5px 0;"><strong>Client :</strong> <?= htmlspecialchars($CLIENT_NAME) ?></p>
        </div>

        <div style="background-color: #fdfdfd; border: 1px solid #eaeaea; padding: 20px; border-radius: 6px;
                margin: 25px 0;">
            <p style="margin-top: 0; font-weight: 600; color: #2c3e50;">Dernières vérifications :</p>
            <ul style="margin-bottom: 0; padding-left: 20px; color: #555;">
                <li>Finalisez la rédaction de vos livrables.</li>
                <li>Vérifiez le format et la plateforme de dépôt (Moodle, Email, etc.).</li>
                <li>Prévoyez une marge de sécurité pour l'envoi des fichiers.</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= htmlspecialchars($SAE_URL) ?>"
               style="display: inline-block; padding: 12px 25px; background-color: #2c3e50; color: #ffffff;
                   text-decoration: none; border-radius: 4px; font-weight: 500; font-size: 14px;">
                Consulter les détails sur SAE Manager
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">

        <p style="font-size: 14px; color: #666;">
            Cordialement,<br>
            L'équipe pédagogique.
        </p>
    </div>

    <div style="margin-top: 20px; text-align: center; font-size: 11px; color: #999;">
        <p>Ce courriel a été généré automatiquement par SAE Manager.</p>
        <p>Merci de ne pas répondre directement à ce message.</p>
    </div>
</div>
</body>

</html>