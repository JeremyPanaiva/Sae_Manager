<?php

/** @var string $STUDENT_NAME */
/** @var string $RESPONSABLE_NAME */
/** @var string $SAE_TITLE */
/** @var string $SAE_DESCRIPTION */
/** @var string $CLIENT_NAME */
/** @var string $DATE_RENDU */
/** @var string $SAE_URL */

?>
<! DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Nouvelle affectation SAE</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
    <div style='background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
        <div style='background-color: #4CAF50; color: white; padding: 20px; text-align: center;
        border-radius: 5px 5px 0 0; margin: -30px -30px 20px -30px;'>
            <h1 style='margin: 0; font-size: 24px;'>🎓 Nouvelle Affectation SAE</h1>
        </div>

        <p>Bonjour <strong><?= $STUDENT_NAME ?></strong>,</p>

        <p>Vous avez été affecté(e) à une nouvelle SAE par <strong><?= $RESPONSABLE_NAME ?></strong>.</p>

        <div style='background-color: #e8f5e9; padding: 20px; border-radius: 5px;
        margin: 20px 0; border-left: 4px solid #4CAF50;'>
            <h3 style='color: #2e7d32; margin-top:  0;'>📋 <?= $SAE_TITLE ?></h3>
            <p style='color: #555; margin: 10px 0;'><strong>Description :</strong></p>
            <p style='color: #555; margin: 10px 0;'><?= $SAE_DESCRIPTION ?></p>
                <hr style='border: none; border-top: 1px dashed #4CAF50; margin: 15px 0;'>
                <p style='color: #555; margin: 5px 0;'><strong>👨‍💼 Client :</strong> <?= $CLIENT_NAME ?></p>
                <p style='color: #555; margin: 5px 0;'><strong>👨‍🏫 Responsable :</strong> <?= $RESPONSABLE_NAME ?></p>
                <p style='color: #555; margin: 5px 0;'><strong>📅 Date de rendu :</strong> <?= $DATE_RENDU ?></p>
        </div>

        <p>Vous pouvez consulter les détails de cette SAE et suivre votre progression sur la plateforme.</p>

        <div style='text-align:  center; margin: 30px 0;'>
            <a href='<?= $SAE_URL ?>' style='display: inline-block; padding: 12px 30px;
            background-color: #4CAF50;
            color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                Voir mes SAE
            </a>
        </div>

        <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>

        <p style='font-size: 0.9em; color: #7f8c8d;'>
            Cordialement,<br>
            L'équipe SAE Manager
        </p>
    </div>
</div>
</body>
</html>