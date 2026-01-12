<?php

/**
 * Email de rappel de date de rendu d'une SAE.
 *
 * Cette vue est utilisée pour notifier un étudiant
 * à l'approche de la date limite de rendu.
 */

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel - Date de rendu SAE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #ff6b6b;
        }
        .header h1 {
            color: #ff6b6b;
            margin: 0;
            font-size: 24px;
        }
        .alert-badge {
            background-color: #ff6b6b;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin: 15px 0;
            font-weight: bold;
            font-size: 14px;
        }
        .content {
            margin-top: 20px;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 5px;
            color: #856404;
        }
        .deadline-highlight {
            background-color: #ff6b6b;
            color: #ffffff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .checklist {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .checklist ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        .checklist li {
            margin: 8px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666666;
        }
    </style>
</head>

<body>
<div class="email-container">
    <div class="header">
        <h1>⏰ Rappel de date de rendu</h1>
        <div class="alert-badge">URGENT - 3 JOURS RESTANTS</div>
    </div>

    <div class="content">
        <p>
            Bonjour <strong><?= htmlspecialchars($STUDENT_NAME) ?></strong>,
        </p>

        <p>
            Ce message est un rappel important concernant la date de rendu de votre SAE.
        </p>

        <div class="deadline-highlight">
            ⚠️ Il ne vous reste que <strong>3 JOURS</strong> !
        </div>

        <div class="info-box">
            <strong>📋 SAE :</strong>
            <?= htmlspecialchars($SAE_TITLE) ?>
        </div>

        <div class="info-box">
            <strong>📅 Date de rendu :</strong>
            <?= htmlspecialchars($DATE_RENDU) ?>
        </div>

        <div class="info-box">
            <strong>👨‍🏫 Responsable :</strong>
            <?= htmlspecialchars($RESPONSABLE_NAME) ?>
        </div>

        <div class="checklist">
            <p><strong>N'oubliez pas de :</strong></p>
            <ul>
                <li>Finaliser votre livrable</li>
                <li>Vérifier que tous les documents requis sont prêts</li>
                <li>Relire et corriger votre travail</li>
                <li>Soumettre votre travail avant la date limite</li>
                <li>Contacter votre responsable en cas de questions</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="<?= htmlspecialchars($SAE_URL) ?>" class="button">
                Accéder à mes SAE
            </a>
        </div>

        <p style="margin-top: 20px; font-style: italic; color: #666666;">
            Bon courage pour la finalisation de votre projet !
        </p>
    </div>

    <div class="footer">
        <p>Ceci est un message automatique de SAE Manager</p>
        <p>Merci de ne pas répondre à cet email</p>
    </div>
</div>
</body>
</html>
