<?php
require_once 'config.php';
$support_email = getSystemSetting($pdo, 'support_email');
$support_phone = getSystemSetting($pdo, 'support_phone');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .maintenance-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .icon {
            font-size: 80px;
            color: #f39c12;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 10px;
            color: #2c3e50;
        }

        p {
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .contact-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .contact-item:last-child {
            margin-bottom: 0;
        }

        .contact-item .material-icons {
            color: #3f51b5;
            font-size: 20px;
        }

        .contact-label {
            font-weight: 500;
        }

        a {
            color: #3f51b5;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="maintenance-card">
        <span class="material-icons icon">engineering</span>
        <h1>Under Maintenance</h1>
        <p>We are currently performing scheduled maintenance to improve our services. Please check back later.</p>

        <?php if ($support_email || $support_phone): ?>
            <div class="contact-info">
                <p style="margin-bottom: 15px; font-weight: 500; color: #333;">For urgent inquiries, contact us:</p>

                <?php if ($support_email): ?>
                    <div class="contact-item">
                        <span class="material-icons">email</span>
                        <a
                            href="mailto:<?php echo htmlspecialchars($support_email); ?>"><?php echo htmlspecialchars($support_email); ?></a>
                    </div>
                <?php endif; ?>

                <?php if ($support_phone): ?>
                    <div class="contact-item">
                        <span class="material-icons">phone</span>
                        <span><?php echo htmlspecialchars($support_phone); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>