<?php
/**
 * POST /api/auth/login
 * User login - returns JWT token
 */

$input = getRequestInput();

// Validate input
$errors = validateInput($input, [
    'login' => 'required',
    'password' => 'required'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$login = trim($input['login']);
$password = $input['password'];

// Find user by enrollment number or email
try {
    $stmt = $pdo->prepare("
        SELECT id, enrollment_number, email, password_hash, full_name, 
               campus, department, semester, profile_image, whatsapp_number,
               is_verified, status, created_at, last_login
        FROM users 
        WHERE enrollment_number = ? OR email = ?
        LIMIT 1
    ");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ApiResponse::error('AUTH_INVALID', 'Invalid credentials', null, 401);
    }

    // Check password
    if (!password_verify($password, $user['password_hash'])) {
        ApiResponse::error('AUTH_INVALID', 'Invalid credentials', null, 401);
    }

    // Check if banned
    if ($user['status'] === 'banned') {
        ApiResponse::error('AUTH_BANNED', 'Your account has been banned', null, 403);
    }

    // Check if verified
    if (!$user['is_verified']) {
        ApiResponse::error('AUTH_UNVERIFIED', 'Please verify your email before logging in', null, 403);
    }

    // Generate JWT token
    $token = JWT::encode([
        'user_id' => $user['id'],
        'enrollment' => $user['enrollment_number']
    ]);

    // Generate refresh token
    $refreshToken = JWT::generateRefreshToken();
    $refreshHash = JWT::hashRefreshToken($refreshToken);
    $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY);

    // Store refresh token
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $stmt = $pdo->prepare("
        INSERT INTO api_tokens (user_id, token_hash, device_info, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $refreshHash, $deviceInfo, $expiresAt]);

    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Log activity
    logActivity($pdo, $user['id'], 'login');

    // Remove sensitive data
    unset($user['password_hash']);
    unset($user['is_verified']);

    ApiResponse::success([
        'user' => $user,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_at' => date('c', time() + JWT_ACCESS_EXPIRY)
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Login failed', null, 500);
}
