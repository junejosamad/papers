<?php
/**
 * POST /api/auth/forgot-password
 * Request password reset email
 */

$input = getRequestInput();

// Validate input
$errors = validateInput($input, [
    'email' => 'required|email'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$email = strtolower(trim($input['email']));

try {
    // Find user
    $stmt = $pdo->prepare("
        SELECT id, enrollment_number, email 
        FROM users 
        WHERE email = ? AND is_verified = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Always return success to prevent email enumeration
    if (!$user) {
        ApiResponse::success([
            'message' => 'If an account with this email exists, you will receive a password reset link.'
        ]);
    }

    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store reset token
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_reset_token = ?, password_reset_expiry = ?
        WHERE id = ?
    ");
    $stmt->execute([$resetToken, $tokenExpiry, $user['id']]);

    // Send reset email
    try {
        sendPasswordResetEmail($user['email'], $resetToken, $user['enrollment_number']);
    } catch (Exception $e) {
        error_log("Reset email error: " . $e->getMessage());
    }

    ApiResponse::success([
        'message' => 'If an account with this email exists, you will receive a password reset link.'
    ]);

} catch (Exception $e) {
    error_log("Forgot password error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Request failed', null, 500);
}
