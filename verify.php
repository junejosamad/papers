<?php
require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$user = null;

if ($token) {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE verification_token = ? 
        AND token_expiry > NOW() 
        AND is_verified = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Invalid or expired verification link';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, is_verified = 1, verification_token = NULL, token_expiry = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$password_hash, $user['id']]);

        logActivity($pdo, $user['id'], 'signup');

        $success = 'Account verified successfully! You can now login.';
        $user = null; // Hide form
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-section">
                <span class="material-icons logo-icon">verified_user</span>
                <h1>Verify Your Account</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="material-icons">error</span>
                    <?php echo $error; ?>
                </div>
                <a href="index.php" class="btn btn-primary btn-full">Go to Login</a>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    <span class="material-icons">check_circle</span>
                    <?php echo $success; ?>
                </div>
                <a href="index.php" class="btn btn-primary btn-full">Go to Login</a>
            <?php elseif ($user): ?>
                <p style="text-align: center; margin-bottom: 24px;">
                    Set your password for<br>
                    <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                </p>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" minlength="8" required>
                        <span class="helper-text">Minimum 8 characters</span>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <span class="material-icons">check_circle</span>
                        Verify & Set Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>