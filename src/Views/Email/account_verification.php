<!DOCTYPE html>
<html>

<head>
    <meta charset='UTF-8'>
    <title>Vérification de votre compte</title>
</head>

<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #2c3e50;'>Bienvenue sur SAE Manager !</h2>

        <p>Bonjour,</p>

        <p>Merci de vous être inscrit. Pour activer votre compte, veuillez confirmer votre adresse email en cliquant sur
            le lien ci-dessous :</p>

        <div style='text-align: center; margin: 30px 0;'>
            <a href='<?php echo $VERIFICATION_LINK; ?>'
                style='background-color: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                Vérifier mon compte
            </a>
        </div>

        <p>Si vous n'avez pas créé de compte sur SAE Manager, vous pouvez ignorer cet email.</p>

        <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>

        <p style='font-size: 12px; color: #666;'>
            Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
            <a href='<?php echo $VERIFICATION_LINK; ?>'><?php echo $VERIFICATION_LINK; ?></a>
        </p>
    </div>
</body>

</html>