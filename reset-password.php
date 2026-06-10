<?php
// ============================================
// PASSWORD RESET PAGE
// Save as: reset-password.php
// ============================================

require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$user = null;

// Verify token
if ($token) {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE password_reset_token = ? 
        AND password_reset_expiry > NOW() 
        AND is_verified = 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $valid_token = true;
    } else {
        $error = 'Invalid or expired password reset link. Please request a new one.';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify token again
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE password_reset_token = ? 
        AND password_reset_expiry > NOW() 
        AND is_verified = 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Invalid or expired password reset link.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Update password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, 
                password_reset_token = NULL, 
                password_reset_expiry = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$password_hash, $user['id']]);

        $success = 'Password reset successful! You can now login with your new password.';
        $valid_token = false;

        // Log activity
        logActivity($pdo, $user['id'], 'password_reset');
    }
}

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-section">
                <span class="material-icons logo-icon">lock_reset</span>
                <h1>Reset Password</h1>
                <p>Bahria Papers Portal</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="material-icons">error</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="material-icons">check_circle</span>
                    <?php echo $success; ?>
                    <div style="margin-top: 15px;">
                        <a href="login/" class="btn btn-primary">
                            <span class="material-icons">login</span>
                            Go to Login
                        </a>
                    </div>
                </div>
            <?php elseif ($valid_token): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="info-box"
                        style="margin-bottom: 20px; padding: 12px; background: #E3F2FD; border-radius: 8px; font-size: 14px; color: #1565C0;">
                        <span class="material-icons" style="font-size: 18px; vertical-align: middle;">info</span>
                        Resetting password for: <strong><?php echo htmlspecialchars($user['enrollment_number']); ?></strong>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="6" required
                            placeholder="Enter new password (min 6 characters)">
                        <span class="helper-text">Minimum 6 characters</span>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="6" required
                            placeholder="Re-enter new password">
                    </div>

                    <button type="submit" name="reset_password" class="btn btn-primary btn-full">
                        <span class="material-icons">check_circle</span>
                        Reset Password
                    </button>
                </form>
            <?php elseif (!$token): ?>
                <div class="alert alert-error">
                    <span class="material-icons">error</span>
                    No reset token provided. Please use the link from your email.
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 20px;">
                <a href="login/" style="color: #1976D2; text-decoration: none; font-size: 14px;">
                    <span class="material-icons" style="font-size: 16px; vertical-align: middle;">arrow_back</span>
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</body>

</html>