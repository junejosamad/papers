<?php
/**
 * POST /api/auth/verify
 * Verify email and set password
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
    // Find user with valid token
    $stmt = $pdo->prepare("
        SELECT id, enrollment_number, email, full_name, campus, department, semester
        FROM users 
        WHERE verification_token = ? AND token_expiry > NOW() AND is_verified = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ApiResponse::error('INVALID_TOKEN', 'Invalid or expired verification token', null, 400);
    }

    // Hash password and update user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = ?, is_verified = 1, verification_token = NULL, token_expiry = NULL
        WHERE id = ?
    ");
    $stmt->execute([$passwordHash, $user['id']]);

    // Generate JWT token (auto-login after verification)
    $jwtToken = JWT::encode([
        'user_id' => $user['id'],
        'enrollment' => $user['enrollment_number']
    ]);

    // Generate refresh token
    $refreshToken = JWT::generateRefreshToken();
    $refreshHash = JWT::hashRefreshToken($refreshToken);
    $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY);

    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $stmt = $pdo->prepare("
        INSERT INTO api_tokens (user_id, token_hash, device_info, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $refreshHash, $deviceInfo, $expiresAt]);

    ApiResponse::success([
        'message' => 'Email verified successfully! You are now logged in.',
        'user' => $user,
        'token' => $jwtToken,
        'refresh_token' => $refreshToken,
        'expires_at' => date('c', time() + JWT_ACCESS_EXPIRY)
    ]);

} catch (Exception $e) {
    error_log("Verification error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Verification failed', null, 500);
}
