<?php
/**
 * PUT /api/user/profile
 * Update user profile
 */

$user = AuthMiddleware::require();
$input = getRequestInput();

// Allowed fields to update
$allowedFields = ['full_name', 'campus', 'department', 'semester', 'whatsapp_number'];
$updates = [];
$params = [];

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        $updates[] = "$field = ?";
        $params[] = $field === 'semester' ? (int) $input[$field] : sanitize($input[$field]);
    }
}

if (empty($updates)) {
    ApiResponse::error('VALIDATION_ERROR', 'No valid fields to update', null, 400);
}

// Validate campus if provided
if (isset($input['campus'])) {
    $validCampuses = ['Islamabad E-8', 'Islamabad H-11', 'Karachi', 'Karachi Health Sciences', 'Lahore'];
    if (!in_array($input['campus'], $validCampuses)) {
        ApiResponse::validationError(['campus' => 'Invalid campus selected']);
    }
}

// Validate semester if provided
if (isset($input['semester'])) {
    $semester = (int) $input['semester'];
    if ($semester < 1 || $semester > 10) {
        ApiResponse::validationError(['semester' => 'Semester must be between 1 and 10']);
    }
}

try {
    $params[] = $user['id'];
    $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);

    // Fetch updated profile
    $stmt = $pdo->prepare("
        SELECT id, enrollment_number, email, full_name, campus, department, 
               semester, profile_image, whatsapp_number
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    ApiResponse::success([
        'message' => 'Profile updated successfully',
        'user' => $profile
    ]);

} catch (Exception $e) {
    error_log("Update profile error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to update profile', null, 500);
}
