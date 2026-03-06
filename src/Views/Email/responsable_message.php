<?php
/**
 * Email template for responsable message to student
 *
 * @var string $STUDENT_NAME
 * @var string $MESSAGE
 * @var string $RESPONSABLE_NAME
 * @var string $SUBJECT
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($SUBJECT); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .message-box {
            background-color: white;
            padding: 20px;
            border-left: 4px solid #4CAF50;
            margin: 20px 0;
        }
        .signature {
            margin-top: 30px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Message de votre responsable SAE</h1>
</div>
<div class="content">
    <p>Bonjour <strong><?php echo htmlspecialchars($STUDENT_NAME); ?></strong>,</p>

    <div class="message-box">
        <h3><?php echo htmlspecialchars($SUBJECT); ?></h3>
        <p><?php echo $MESSAGE; ?></p>
    </div>

    <div class="signature">
        <p>Cordialement,<br>
            <strong><?php echo htmlspecialchars($RESPONSABLE_NAME); ?></strong><br>
            Responsable SAE Manager</p>
    </div>
</div>
</body>
</html>