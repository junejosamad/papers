<?php
/**
 * POST /api/auth/reset-password
 * Reset password with token
 */

$input = getRequestInput();

// Validate input
$errors = validateInput($input, [
    'token' => 'required',
    'password' => 'required|min:8'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$token = trim($input['token']);
$password = $input['password'];

try {
    // Find user with valid reset token
    $stmt = $pdo->prepare("
        SELECT id, enrollment_number 
        FROM users 
        WHERE password_reset_token = ? AND password_reset_expiry > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ApiResponse::error('INVALID_TOKEN', 'Invalid or expired reset token', null, 400);
    }

    // Update password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = ?, password_reset_token = NULL, password_reset_expiry = NULL
        WHERE id = ?
    ");
    $stmt->execute([$passwordHash, $user['id']]);

    // Invalidate all existing refresh tokens
    $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE user_id = ?");
    $stmt->execute([$user['id']]);

    // Log activity
    logActivity($pdo, $user['id'], 'password_reset');

    ApiResponse::success([
        'message' => 'Password reset successful! Please login with your new password.'
    ]);

} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Password reset failed', null, 500);
}
