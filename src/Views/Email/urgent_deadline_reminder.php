<?php

/**
 * Email urgent : dernier jour pour la remise d'une SAE.
 *
 * Cette vue est utilisée pour notifier un étudiant le dernier jour
 * avant la date de rendu.
 */

/** @var string $CLIENT_NAME */
/** @var string $RESPONSABLE_NAME */
/** @var string $SAE_TITLE */
/** @var string $STUDENT_NAME */
/** @var string $SAE_URL */
/** @var string $DATE_RENDU */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚠️ Dernier jour - SAE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff0f0;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #d32f2f;
            margin: 0;
            font-size: 26px;
        }
        .alert-badge {
            display: inline-block;
            background-color: #ff3d00;
            color: #fff;
            font-weight: bold;
            font-size: 16px;
            padding: 10px 20px;
            border-radius: 25px;
            margin: 15px 0;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        .content {
            margin-top: 20px;
        }
        .deadline-highlight {
            background-color: #ff5722;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
        }
        .info-box {
            background-color: #ffe0b2;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 5px;
            color: #bf360c;
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background-color: #d32f2f;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #b71c1c;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>🚨 DERNIER JOUR</h1>
        <div class="alert-badge">23h59 pour rendre votre SAE !</div>
    </div>

    <div class="content">
        <p>Bonjour <strong><?= htmlspecialchars($STUDENT_NAME) ?></strong>,</p>

        <p>Attention, c’est le <strong>dernier jour</strong> pour remettre votre SAE.</p>

        <div class="deadline-highlight">
            ⚠️ DERNIER JOUR - <?= htmlspecialchars($DATE_RENDU) ?>
        </div>

        <div class="info-box">
            <strong>📋 SAE :</strong> <?= htmlspecialchars($SAE_TITLE) ?>
        </div>

        <div class="info-box">
            <strong>👨‍🏫 Responsable :</strong> <?= htmlspecialchars($RESPONSABLE_NAME) ?>
        </div>

        <p>Ne tardez plus ! Vérifiez que votre livrable est complet et soumis avant la fin de la journée.</p>

        <div style="text-align: center;">
            <a href="<?= htmlspecialchars($SAE_URL) ?>" class="button">
                🚀 Accéder à ma SAE
            </a>
        </div>
    </div>

    <div class="footer">
        <p>Ceci est un message automatique de SAE Manager</p>
        <p>Merci de ne pas répondre à cet email</p>
    </div>
</div>
</body>
</html>
