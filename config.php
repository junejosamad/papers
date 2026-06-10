<?php
session_start();

function loadEnvFile($path)
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function envValue($key, $default = '')
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

loadEnvFile(__DIR__ . '/.env');

// Set Default Timezone
date_default_timezone_set(envValue('APP_TIMEZONE', 'Asia/Karachi'));

// Database Configuration
define('DB_HOST', envValue('DB_HOST', 'localhost'));
define('DB_USER', envValue('DB_USER', 'papers_user'));
define('DB_PASS', envValue('DB_PASS', ''));
define('DB_NAME', envValue('DB_NAME', 'papers'));

// Email Configuration - Brevo API
define('BREVO_API_KEY', envValue('BREVO_API_KEY', ''));

define('FROM_EMAIL', envValue('FROM_EMAIL', 'papers@example.com'));
define('FROM_NAME', envValue('FROM_NAME', 'Bahria Papers Portal'));

// Site Configuration
define('SITE_URL', rtrim(envValue('SITE_URL', 'http://localhost/papers'), '/'));
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', (int) envValue('MAX_FILE_SIZE', '10485760')); // 10MB in bytes

// Optional support/payment details shown in the dashboard donation prompts.
define('SUPPORT_PAYMENT_LABEL', envValue('SUPPORT_PAYMENT_LABEL', 'Support'));
define('SUPPORT_PAYMENT_VALUE', envValue('SUPPORT_PAYMENT_VALUE', 'Configure payment details'));
define('SUPPORT_PAYMENT_NOTE', envValue('SUPPORT_PAYMENT_NOTE', 'Set this in your deployment'));

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check Maintenance Mode
function checkMaintenanceMode($pdo)
{
    // Skip for maintenance page itself
    if (basename($_SERVER['PHP_SELF']) === 'maintenance.php') {
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'site_maintenance_mode'");
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result && $result['setting_value'] === '1') {
            header('Location: ' . SITE_URL . '/maintenance.php');
            exit;
        }
    } catch (Exception $e) {
        // If table doesn't exist yet, ignore
    }
}

// execute maintenance check
checkMaintenanceMode($pdo);

// Helper Functions
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function getSystemSetting($pdo, $key, $default = null)
{
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

require_once __DIR__ . '/includes/brevo_mailer.php';

function logActivity($pdo, $user_id, $action_type, $paper_id = null)
{
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action_type, paper_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $action_type,
        $paper_id,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['verified']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
// Legacy guard: application email now uses Brevo API over HTTPS.
function getMailer()
{
    throw new Exception("SMTP delivery is disabled. Use sendAppEmail() / sendBrevoEmail() for application email.");
}

function buildAccountEmailHtml($headerTitle, $headerSubtitle, $contentTitle, $introHtml, $actionText, $buttonText, $safeLink, $expiryHtml)
{
    return "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #1976D2; color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .header p { margin: 5px 0 0 0; font-size: 14px; }
            .content { background: #f5f5f5; padding: 40px 30px; }
            .content h2 { color: #1976D2; margin-top: 0; }
            .button { display: inline-block; padding: 15px 40px; background: #1976D2; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
            .link-box { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; word-break: break-all; border: 1px solid #ddd; }
            .warning { background: #FFF3E0; padding: 15px; border-left: 4px solid #FF9800; margin: 15px 0; }
            .footer { background: #333; color: #999; text-align: center; padding: 20px; font-size: 12px; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$headerTitle}</h1>
                <p>{$headerSubtitle}</p>
            </div>
            <div class='content'>
                <h2>{$contentTitle}</h2>
                <p>Hello,</p>
                <p>{$introHtml}</p>
                <p>{$actionText}</p>
                <center>
                    <a href='{$safeLink}' class='button' style='color: white;'>{$buttonText}</a>
                </center>
                <p>If the button does not work, copy and paste this link in your browser:</p>
                <div class='link-box'>
                    <a href='{$safeLink}' style='color: #1976D2;'>{$safeLink}</a>
                </div>
                <div class='warning'>
                    <p><strong>Important Security Information:</strong></p>
                    <ul style='margin: 5px 0; padding-left: 20px;'>
                        <li>{$expiryHtml}</li>
                        <li>If you did not request this email, please ignore it</li>
                        <li>For security, do not share this link with anyone</li>
                    </ul>
                </div>
            </div>
            <div class='footer'>
                <p><strong>Sigma ByteCraft Past Papers Portal</strong></p>
                <p>(c) 2025 Sigma ByteCraft. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function sendVerificationEmail($email, $token, $enrollment)
{
    $verification_link = SITE_URL . "/verify.php?token=" . urlencode($token);
    $safe_link = htmlspecialchars($verification_link, ENT_QUOTES, 'UTF-8');
    $safe_enrollment = htmlspecialchars($enrollment, ENT_QUOTES, 'UTF-8');

    $htmlBody = buildAccountEmailHtml(
        'Sigma ByteCraft',
        'Past Papers Portal',
        'Verify Your Account',
        "Welcome to <strong>Bahria University Past Papers Portal</strong>! Your enrollment number <strong>{$safe_enrollment}</strong> has been successfully registered.",
        'To complete your registration and set up your password, please click the button below:',
        'Verify Email & Set Password',
        $safe_link,
        'This verification link will expire in <strong>24 hours</strong>'
    );

    $plainBody = "Welcome to Sigma ByteCraft Past Papers Portal!\n\n" .
        "Your enrollment number: {$enrollment}\n\n" .
        "Please verify your account by visiting this link:\n{$verification_link}\n\n" .
        "This link will expire in 24 hours.\n\n" .
        "If you did not register for this account, please ignore this email.\n\n" .
        "---\n" .
        "Sigma ByteCraft Past Papers Portal\n" .
        "(c) 2025 All rights reserved.";

    $result = sendAppEmail($email, 'Verify Your Bahria Papers Portal Account', $htmlBody, $plainBody);
    if ($result['success']) {
        return true;
    }

    error_log("Brevo API verification email failure: " . json_encode($result));
    $_SESSION['dev_verification_link'] = $verification_link;
    $_SESSION['dev_enrollment'] = $enrollment;
    $_SESSION['email_error'] = $result['error'] ?: ('Brevo API returned HTTP ' . $result['http_code']);
    return false;
}

function sendPasswordResetEmail($email, $token, $enrollment)
{
    $reset_link = SITE_URL . "/reset-password.php?token=" . urlencode($token);
    $safe_link = htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8');
    $safe_enrollment = htmlspecialchars($enrollment, ENT_QUOTES, 'UTF-8');

    $htmlBody = buildAccountEmailHtml(
        'Password Reset Request',
        'Sigma ByteCraft Past Papers Portal',
        'Reset Your Password',
        "We received a request to reset the password for your account with enrollment number <strong>{$safe_enrollment}</strong>.",
        'To reset your password, please click the button below:',
        'Reset Password',
        $safe_link,
        'This password reset link will expire in <strong>1 hour</strong>'
    );

    $plainBody = "Password Reset Request\n\n" .
        "We received a request to reset the password for your account with enrollment number: {$enrollment}\n\n" .
        "To reset your password, please visit this link:\n{$reset_link}\n\n" .
        "This link will expire in 1 hour.\n\n" .
        "If you did not request this password reset, please ignore this email.\n\n" .
        "---\n" .
        "Sigma ByteCraft Past Papers Portal\n" .
        "(c) 2025 All rights reserved.";

    $result = sendAppEmail($email, 'Reset Your Password - Bahria Papers Portal', $htmlBody, $plainBody);
    if ($result['success']) {
        return true;
    }

    error_log("Brevo API password reset email failure: " . json_encode($result));
    $_SESSION['dev_reset_link'] = $reset_link;
    $_SESSION['dev_enrollment'] = $enrollment;
    $_SESSION['email_error'] = $result['error'] ?: ('Brevo API returned HTTP ' . $result['http_code']);
    return false;
}
?>
