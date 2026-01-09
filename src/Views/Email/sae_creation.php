<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Nouvelle SAE créée</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
    <div style='background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
        <h2 style='color: #2c3e50; margin-top: 0;'>Bonjour <?php echo $RESPONSABLE_NAME; ?>,</h2>

        <p>Une nouvelle SAE a été proposée par <strong><?php echo $CLIENT_NAME; ?>
            </strong> et nécessite votre attention.</p>

        <div style='background-color: #ecf0f1; padding: 15px; border-radius: 5px; margin: 20px 0;'>
            <h3 style='color: #34495e; margin-top: 0;'>📋 <?php echo $SAE_TITLE; ?></h3>
            <p style='color: #555;'><strong>Description :</strong></p>
            <p style='color: #555;'><?php echo $SAE_DESCRIPTION; ?></p>
            <p style='color: #555;'><strong>Client :</strong> <?php echo $CLIENT_NAME; ?></p>
        </div>

        <p>Vous pouvez consulter cette SAE et l'attribuer à des étudiants en vous connectant à la plateforme.</p>

        <div style='text-align: center; margin: 30px 0;'>
            <a href='<?php echo $SAE_URL; ?>' style='display: inline-block; padding: 12px 30px; background-color: #3498db;
            color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                Voir les SAE
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