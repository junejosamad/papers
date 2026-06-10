<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

$error = '';
$success = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $enrollment = sanitize($_POST['enrollment']);
    $password = $_POST['password'];

    if (preg_match('/^[0-9]{2}-[0-9]{6}-[0-9]{3}$/', $enrollment)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE enrollment_number = ? AND is_verified = 1");
        $stmt->execute([$enrollment]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if user is banned
            if ($user['is_banned'] == 1 || $user['status'] === 'banned') {
                $ban_reason = !empty($user['ban_reason']) ? htmlspecialchars($user['ban_reason']) : 'No reason provided.';
                $error = '<strong>Your account has been suspended.</strong><br>
                          <span style="font-size:13px;"><strong>Reason:</strong> ' . $ban_reason . '</span><br>
                          <span style="font-size:12px; color:#c0392b;">If you believe this is a mistake, please contact the administrator.</span>';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['enrollment'] = $user['enrollment_number'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['verified'] = true;

                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                logActivity($pdo, $user['id'], 'login');

                header('Location: ../dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid enrollment number or password';
        }
    } else {
        $error = 'Invalid enrollment number format';
    }
}

// Handle Signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $enrollment = sanitize($_POST['enrollment_signup']);

    if (preg_match('/^[0-9]{2}-[0-9]{6}-[0-9]{3}$/', $enrollment)) {
        $email = $enrollment . '@student.bahria.edu.pk';

        // Check if already exists
        $stmt = $pdo->prepare("SELECT id, is_verified, verification_token, token_expiry FROM users WHERE enrollment_number = ?");
        $stmt->execute([$enrollment]);
        $existing_user = $stmt->fetch();

        if ($existing_user) {
            if ($existing_user['is_verified']) {
                $error = 'This enrollment number is already registered and verified. Please login.';
            } else {
                // User exists but not verified - Resend verification email
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expiry = ? WHERE id = ?");
                $stmt->execute([$token, $expiry, $existing_user['id']]);

                if (sendVerificationEmail($email, $token, $enrollment)) {
                    $success = "Verification email sent to $email. Please check your inbox and spam/junk folder.";
                } else {
                    $error = "Failed to send verification email. Please try again.";
                }
            }
        } else {
            // Generate verification token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Insert user with temporary password
            $stmt = $pdo->prepare("
                INSERT INTO users (enrollment_number, email, password_hash, verification_token, token_expiry) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $temp_password = password_hash('temp_' . $enrollment, PASSWORD_DEFAULT);
            $stmt->execute([$enrollment, $email, $temp_password, $token, $expiry]);

            // Send verification email
            if (sendVerificationEmail($email, $token, $enrollment)) {
                $success = "Verification email sent to $email. Please check your inbox and spam/junk folder.";
            } else {
                $error = "Failed to send verification email. Please try again.";
            }
        }
    } else {
        $error = 'Invalid enrollment number format (XX-XXXXXX-XXX)';
    }
}

// Handle Forgot Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $enrollment = sanitize($_POST['enrollment_forgot']);

    if (preg_match('/^[0-9]{2}-[0-9]{6}-[0-9]{3}$/', $enrollment)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE enrollment_number = ? AND is_verified = 1");
        $stmt->execute([$enrollment]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate password reset token
            $reset_token = bin2hex(random_bytes(32));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expiry = ? WHERE id = ?");
            $stmt->execute([$reset_token, $reset_expiry, $user['id']]);

            // Send password reset email
            if (sendPasswordResetEmail($user['email'], $reset_token, $enrollment)) {
                $success = "Password reset link has been sent to your email. Please check your inbox and spam/junk folder.";
            } else {
                $error = "Failed to send password reset email. Please try again.";
            }
        } else {
            // Don't reveal if user exists or not for security
            $success = "If this enrollment number is registered, a password reset link has been sent to the associated email.";
        }
    } else {
        $error = 'Invalid enrollment number format (XX-XXXXXX-XXX)';
    }
}

if (isLoggedIn()) {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-section">
                <span class="material-icons logo-icon">school</span>
                <h1>Bahria University</h1>
                <p>Past Papers Portal</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="line-height: 1.6;">
                    <span class="material-icons" style="vertical-align: middle; margin-right: 6px;">block</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="material-icons">check_circle</span>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" method="POST" class="auth-form">
                <div class="form-group">
                    <label>Enrollment Number</label>
                    <input type="text" name="enrollment" pattern="[0-9]{2}-[0-9]{6}-[0-9]{3}"
                        placeholder="XX-XXXXXX-XXX" required>
                    <span class="helper-text">Format: XX-XXXXXX-XXX</span>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary btn-full">
                    <span class="material-icons">login</span>
                    Login
                </button>

                <div class="auth-links">
                    <a href="#" onclick="showForm('signup'); return false;">Create New Account</a>
                    <a href="#" onclick="showForm('forgot'); return false;">Forgot Password?</a>
                </div>
            </form>

            <!-- Signup Form -->
            <form id="signupForm" method="POST" class="auth-form" style="display: none;">
                <h3 style="text-align: center; margin-bottom: 20px; color: #333;">Create Account</h3>
                <div class="form-group">
                    <label>Enrollment Number</label>
                    <input type="text" name="enrollment_signup" pattern="[0-9]{2}-[0-9]{6}-[0-9]{3}"
                        placeholder="XX-XXXXXX-XXX" required>
                    <span class="helper-text">We'll send verification link to:
                        XX-XXXXXX-XXX@student.bahria.edu.pk</span>
                </div>

                <button type="submit" name="signup" class="btn btn-primary btn-full">
                    <span class="material-icons">mail</span>
                    Send Verification Email
                </button>

                <div class="info-box"
                    style="margin-top: 15px; padding: 12px; background: #E3F2FD; border-radius: 8px; font-size: 13px; color: #1565C0;">
                    <span class="material-icons" style="font-size: 16px; vertical-align: middle;">info</span>
                    <strong>Note:</strong> Please check your spam/junk folder if you don't receive the email within a
                    few minutes.
                </div>

                <div class="auth-links">
                    <a href="#" onclick="showForm('login'); return false;">Back to Login</a>
                </div>
            </form>

            <!-- Forgot Password Form -->
            <form id="forgotForm" method="POST" class="auth-form" style="display: none;">
                <h3 style="text-align: center; margin-bottom: 20px; color: #333;">Reset Password</h3>
                <div class="form-group">
                    <label>Enrollment Number</label>
                    <input type="text" name="enrollment_forgot" pattern="[0-9]{2}-[0-9]{6}-[0-9]{3}"
                        placeholder="XX-XXXXXX-XXX" required>
                    <span class="helper-text">Enter your enrollment number to reset password</span>
                </div>

                <button type="submit" name="forgot_password" class="btn btn-primary btn-full">
                    <span class="material-icons">lock_reset</span>
                    Send Reset Link
                </button>

                <div class="info-box"
                    style="margin-top: 15px; padding: 12px; background: #FFF3E0; border-radius: 8px; font-size: 13px; color: #E65100;">
                    <span class="material-icons" style="font-size: 16px; vertical-align: middle;">warning</span>
                    A password reset link will be sent to your registered email address. Please check spam/junk folder
                    too.
                </div>

                <div class="auth-links">
                    <a href="#" onclick="showForm('login'); return false;">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .auth-links {
            margin-top: 20px;
            text-align: center;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .auth-links a {
            color: #1976D2;
            text-decoration: none;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        #signupForm .auth-links,
        #forgotForm .auth-links {
            justify-content: center;
        }
    </style>

    <script>
        function showForm(formId) {
            document.querySelectorAll('.auth-form').forEach(f => f.style.display = 'none');
            document.getElementById(formId + 'Form').style.display = 'block';
            if (['login', 'signup', 'forgot'].includes(formId)) {
                history.replaceState(null, '', '#' + formId);
            }
        }

        function showFormFromHash() {
            const formId = window.location.hash.replace('#', '');
            if (['login', 'signup', 'forgot'].includes(formId)) {
                showForm(formId);
            }
        }

        showFormFromHash();
        window.addEventListener('hashchange', showFormFromHash);
    </script>
</body>

</html>
