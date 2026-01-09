<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Réinitialisation de mot de passe</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
    <h2 style='color: #2c3e50;'>Réinitialisation de votre mot de passe</h2>

    <p>Bonjour,</p>

    <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte SAE Manager.</p>

    <p>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :</p>

    <div style='text-align: center; margin: 30px 0;'>
        <a href='<?php echo $RESET_LINK; ?>'
           style='background-color: #3498db; color: white; padding: 12px 24px;
           text-decoration: none; border-radius: 5px; display: inline-block;'>
            Réinitialiser mon mot de passe
        </a>
    </div>

    <p><strong>Ce lien est valide pendant 1 heure.</strong></p>

    <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>

    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>

    <p style='font-size: 12px; color: #666;'>
        Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
        <a href='<?php echo $RESET_LINK; ?>'><?php echo $RESET_LINK; ?></a>
    </p>
</div>
</body>
</html>