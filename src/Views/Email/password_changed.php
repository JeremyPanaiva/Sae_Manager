<?php
/**
 * @var string $LOGIN_LINK
 */
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset='UTF-8'>
    <title>Modification de votre mot de passe</title>
</head>

<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2 style='color: #2c3e50;'>Modification de votre mot de passe</h2>

        <p>Bonjour,</p>

        <p>Le mot de passe de votre compte SAE Manager a été modifié avec succès.</p>

        <p>Si vous êtes à l'origine de cette modification, vous pouvez ignorer cet email.</p>

        <p style="color: #e74c3c; font-weight: bold;">
            Si vous n'avez pas modifié votre mot de passe, un tiers a peut-être accédé à votre compte.
            Veuillez réinitialiser votre mot de passe immédiatement ou contacter un administrateur.
        </p>

        <div style='text-align: center; margin: 30px 0;'>
            <a href='<?php echo $LOGIN_LINK; ?>' style='background-color: #3498db; color: white; padding: 12px 24px;
           text-decoration: none; border-radius: 5px; display: inline-block;'>
                Accéder à mon compte
            </a>
        </div>

        <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>

        <p style='font-size: 12px; color: #666;'>
            Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
            <a href='<?php echo $LOGIN_LINK; ?>'>
                <?php echo $LOGIN_LINK; ?>
            </a>
        </p>
    </div>
</body>

</html>