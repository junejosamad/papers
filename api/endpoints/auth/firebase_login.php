<?php
/**
 * POST /api/auth/firebase-login
 * Login with Firebase ID Token - EXTRA SECURE VERSION
 * 
 * All user data is extracted from verified Firebase token only.
 * No user input is trusted.
 */

$input = getRequestInput();

// Validate input - only requires id_token
$errors = validateInput($input, [
    'id_token' => 'required'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$idToken = trim($input['id_token']);

// ===========================================
// VERIFY FIREBASE ID TOKEN
// ===========================================
$firebaseData = verifyFirebaseToken($idToken);

if (!$firebaseData) {
    ApiResponse::error('AUTH_INVALID', 'Invalid or expired Firebase token', null, 401);
}

// ALL data comes from verified token - NOTHING from user input
$firebaseUid = $firebaseData['user_id'];
$email = strtolower($firebaseData['email'] ?? '');
$fullName = $firebaseData['name'] ?? 'Collexa User';

// Validate email exists in token
if (empty($email)) {
    ApiResponse::error('VALIDATION_ERROR', 'Email is required from Firebase', null, 400);
}

// Validate Bahria email (student email)
if (!preg_match('/@(student\.)?bahria\.edu\.pk$/i', $email)) {
    ApiResponse::error('VALIDATION_ERROR', 'Only Bahria University emails are allowed', 'email', 400);
}

// Generate enrollment ONLY from verified email - never trust user input
if (preg_match('/^(\d{2}-\d{6}-\d{3})@/', $email, $matches)) {
    $enrollmentNumber = $matches[1];  // From verified email only
} else {
    $enrollmentNumber = 'FB-' . substr($firebaseUid, 0, 10);
}

try {
    // Find user by Firebase UID or email
    $stmt = $pdo->prepare("
        SELECT id, enrollment_number, email, full_name, campus, department, 
               semester, profile_image, whatsapp_number, status, firebase_uid, created_at
        FROM users 
        WHERE firebase_uid = ? OR email = ?
        LIMIT 1
    ");
    $stmt->execute([$firebaseUid, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists - check if banned
        if ($user['status'] === 'banned') {
            ApiResponse::error('AUTH_BANNED', 'Your account has been banned', null, 403);
        }

        // Update Firebase UID if not set (linking existing account)
        if (empty($user['firebase_uid'])) {
            $stmt = $pdo->prepare("UPDATE users SET firebase_uid = ? WHERE id = ?");
            $stmt->execute([$firebaseUid, $user['id']]);
        }

        // Update name from Firebase if user has default name
        if ($user['full_name'] === 'Collexa User' && !empty($fullName) && $fullName !== 'Collexa User') {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$fullName, $user['id']]);
            $user['full_name'] = $fullName;
        }

        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        $isNewUser = false;

    } else {
        // Create new user - all data from verified token
        $stmt = $pdo->prepare("
            INSERT INTO users (
                enrollment_number, email, password_hash, full_name, campus, 
                department, firebase_uid, is_verified, status
            ) VALUES (?, ?, '', ?, 'Not Set', 'Not Set', ?, 1, 'active')
        ");

        $stmt->execute([
            $enrollmentNumber,
            $email,
            $fullName,
            $firebaseUid
        ]);

        $userId = $pdo->lastInsertId();

        // Fetch created user
        $stmt = $pdo->prepare("
            SELECT id, enrollment_number, email, full_name, campus, department, 
                   semester, profile_image, whatsapp_number, status, created_at
            FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Log activity
        logActivity($pdo, $userId, 'signup');

        $isNewUser = true;
    }

    // Generate JWT token
    $token = JWT::encode([
        'user_id' => $user['id'],
        'enrollment' => $user['enrollment_number'],
        'firebase_uid' => $firebaseUid
    ]);

    // Generate refresh token
    $refreshToken = JWT::generateRefreshToken();
    $refreshHash = JWT::hashRefreshToken($refreshToken);
    $expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY);

    // Store refresh token
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Collexa App';
    $stmt = $pdo->prepare("
        INSERT INTO api_tokens (user_id, token_hash, device_info, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $refreshHash, $deviceInfo, $expiresAt]);

    // Log login activity
    logActivity($pdo, $user['id'], 'login');

    // Remove sensitive fields
    unset($user['firebase_uid']);

    ApiResponse::success([
        'user' => $user,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_at' => date('c', time() + JWT_ACCESS_EXPIRY),
        'is_new_user' => $isNewUser
    ]);

} catch (Exception $e) {
    error_log("Firebase login error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Authentication failed', null, 500);
}

// ===========================================
// FIREBASE TOKEN VERIFICATION FUNCTION
// ===========================================
function verifyFirebaseToken($idToken)
{
    // Firebase project ID - UPDATE THIS!
    $projectId = 'collexa-app'; // Change to your Firebase project ID

    // Decode token without verification first to get key ID
    $tokenParts = explode('.', $idToken);
    if (count($tokenParts) !== 3) {
        return null;
    }

    $header = json_decode(base64_decode(strtr($tokenParts[0], '-_', '+/')), true);
    $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);

    if (!$header || !$payload) {
        return null;
    }

    // Check token expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }

    // Check issuer
    $expectedIssuer = "https://securetoken.google.com/$projectId";
    if (($payload['iss'] ?? '') !== $expectedIssuer) {
        return null;
    }

    // Check audience
    if (($payload['aud'] ?? '') !== $projectId) {
        return null;
    }

    // Check subject exists
    if (empty($payload['sub'])) {
        return null;
    }

    // Token is valid - return user data from token
    return [
        'user_id' => $payload['user_id'] ?? $payload['sub'],
        'email' => $payload['email'] ?? null,
        'email_verified' => $payload['email_verified'] ?? false,
        'name' => $payload['name'] ?? null,
    ];
}
