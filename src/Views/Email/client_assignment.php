<?php
/**
 * @var string $CLIENT_NAME
 * @var string $RESPONSABLE_NAME
 * @var string $SAE_TITLE
 * @var string $STUDENT_NAME
 * @var string $SAE_URL
 */
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset='UTF-8'>
    <title>Affectation d'un étudiant à votre SAE</title>
</head>

<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
        <div style='background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
            <div style='background-color: #2196F3; color: white; padding: 20px; text-align: center;
        border-radius: 5px 5px 0 0; margin: -30px -30px 20px -30px;'>
                <h1 style='margin: 0; font-size: 24px;'>✅ Étudiant Affecté à Votre SAE</h1>
            </div>

            <p>Bonjour <strong><?php echo $CLIENT_NAME; ?></strong>,</p>

            <p>Nous vous informons qu'un étudiant a été affecté à votre SAE par le responsable <strong>
                    <?php echo $RESPONSABLE_NAME; ?></strong>.</p>

            <div style='background-color: #e3f2fd; padding: 20px; border-radius: 5px;
        margin: 20px 0; border-left: 4px solid #2196F3;'>
                <h3 style='color: #1565c0; margin-top: 0;'>📋 <?php echo $SAE_TITLE; ?></h3>
                <p style='color: #555; margin: 10px 0;'>
                    <strong>🎓 Étudiant affecté :</strong> <?php echo $STUDENT_NAME; ?>
                </p>
                <p style='color: #555; margin: 10px 0;'>
                    <strong>👨‍🏫 Responsable :</strong> <?php echo $RESPONSABLE_NAME; ?>
                </p>
            </div>

            <p>L'étudiant commencera bientôt à travailler sur votre projet.
                N'hésitez pas à vous connecter à la plateforme pour suivre l'avancement.</p>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='<?php echo $SAE_URL; ?>' style='display: inline-block; padding: 12px 30px;
            background-color: #2196F3; color: #fff;
            text-decoration: none; border-radius: 5px; font-weight: bold;'>
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