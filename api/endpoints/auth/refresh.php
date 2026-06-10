<?php
/**
 * POST /api/auth/refresh
 * Refresh JWT token using refresh token
 */

$input = getRequestInput();

// Validate input
$errors = validateInput($input, [
    'refresh_token' => 'required'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$refreshToken = trim($input['refresh_token']);
$tokenHash = JWT::hashRefreshToken($refreshToken);

try {
    // Find refresh token
    $stmt = $pdo->prepare("
        SELECT t.id, t.user_id, t.expires_at,
               u.enrollment_number, u.email, u.full_name, u.campus, u.department,
               u.semester, u.profile_image, u.status
        FROM api_tokens t
        JOIN users u ON t.user_id = u.id
        WHERE t.token_hash = ? AND t.expires_at > NOW()
    ");
    $stmt->execute([$tokenHash]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        ApiResponse::error('AUTH_EXPIRED', 'Invalid or expired refresh token', null, 401);
    }

    if ($result['status'] === 'banned') {
        ApiResponse::error('AUTH_BANNED', 'Your account has been banned', null, 403);
    }

    // Delete old refresh token
    $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE id = ?");
    $stmt->execute([$result['id']]);

    // Generate new JWT token
    $jwtToken = JWT::encode([
        'user_id' => $result['user_id'],
        'enrollment' => $result['enrollment_number']
    ]);

    // Generate new refresh token
    $newRefreshToken = JWT::generateRefreshToken();
    $newRefreshHash = JWT::hashRefreshToken($newRefreshToken);
    $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY);

    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $stmt = $pdo->prepare("
        INSERT INTO api_tokens (user_id, token_hash, device_info, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$result['user_id'], $newRefreshHash, $deviceInfo, $expiresAt]);

    // Build user object
    $user = [
        'id' => $result['user_id'],
        'enrollment_number' => $result['enrollment_number'],
        'email' => $result['email'],
        'full_name' => $result['full_name'],
        'campus' => $result['campus'],
        'department' => $result['department'],
        'semester' => $result['semester'],
        'profile_image' => $result['profile_image']
    ];

    ApiResponse::success([
        'user' => $user,
        'token' => $jwtToken,
        'refresh_token' => $newRefreshToken,
        'expires_at' => date('c', time() + JWT_ACCESS_EXPIRY)
    ]);

} catch (Exception $e) {
    error_log("Token refresh error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Token refresh failed', null, 500);
}
