<?php

/**
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel d'échéance - SAE</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .email-container {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .alert-badge {
            background-color: #2980b9; /* Bleu informatif pour J-3 */
            color: white;
            padding: 6px 14px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 15px;
            font-weight: 500;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .content {
            margin-top: 10px;
        }
        .content p {
            margin-bottom: 15px;
            color: #4a4a4a;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #2980b9;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
            font-size: 15px;
        }
        .info-box strong {
            display: inline-block;
            width: 140px;
            color: #2c3e50;
            font-weight: 600;
        }
        .deadline-highlight {
            background-color: #eff6ff; /* Fond bleu très pâle */
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin: 25px 0;
            font-size: 16px;
        }
        .button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #2c3e50;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            margin: 25px 0;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #1a252f;
        }
        .checklist {
            background-color: #fdfdfd;
            border: 1px solid #eaeaea;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .checklist p {
            margin-top: 0;
            color: #2c3e50;
            font-weight: 600;
        }
        .checklist ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: #555;
        }
        .checklist li {
            margin: 8px 0;
            list-style-type: disc;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 11px;
            color: #999;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>Rappel d'échéance - Projet SAE</h1>
        <div class="alert-badge">Date limite : J-3</div>
    </div>

    <div class="content">
        <p>Bonjour <strong><?= htmlspecialchars($STUDENT_NAME) ?></strong>,</p>

        <p>Nous vous rappelons que la date limite de rendu pour votre
            Situation d'Apprentissage et d'Évaluation (SAE) arrive prochainement à échéance.</p>

        <div class="deadline-highlight">
            Attention, la clôture est prévue dans <strong>3 jours</strong>.
            <br>
            <span style="font-size: 0.9em; font-weight: normal;">(<?= htmlspecialchars($DATE_RENDU) ?>)</span>
        </div>

        <div class="info-box">
            <strong>Intitulé de la SAE :</strong> <?= htmlspecialchars($SAE_TITLE) ?>
        </div>

        <div class="info-box">
            <strong>Date limite :</strong> <?= htmlspecialchars($DATE_RENDU) ?>
            <?php if (!empty($HEURE_RENDU)) : ?>
                à <?= htmlspecialchars($HEURE_RENDU) ?>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <strong>Enseignant référent :</strong> <?= htmlspecialchars($RESPONSABLE_NAME) ?>
        </div>

        <div class="checklist">
            <p>Check-list avant le rendu final :</p>
            <ul>
                <li>Finalisez la rédaction de vos livrables.</li>
                <li>Vérifiez bien le format et la plateforme de dépôt demandés (Moodle, Email, etc.).</li>
                <li>Prévoyez une marge de sécurité pour l'envoi de vos fichiers
                    <?php if (!empty($HEURE_RENDU)) : ?>
                        avant <?= htmlspecialchars($HEURE_RENDU) ?>
                    <?php endif; ?>.
                </li>
                <li>Relisez les consignes disponibles sur la fiche de la SAE.</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="<?= htmlspecialchars($SAE_URL) ?>" class="button">
                Consulter les détails de ma SAE
            </a>
        </div>

        <p style="margin-top: 20px; font-size: 14px; color: #666;">
            Cordialement,<br>
            L'équipe pédagogique.
        </p>
    </div>

    <div class="footer">
        <p>Ce courriel a été généré automatiquement par SAE Manager.</p>
        <p>Merci de ne pas répondre directement à ce message.</p>
    </div>
</div>
</body>
</html>