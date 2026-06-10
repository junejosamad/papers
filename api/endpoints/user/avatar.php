<?php
/**
 * POST /api/user/avatar
 * Upload profile image
 */

$user = AuthMiddleware::require();

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    ApiResponse::error('UPLOAD_ERROR', 'No file uploaded', null, 400);
}

$file = $_FILES['avatar'];

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    ApiResponse::error('UPLOAD_ERROR', 'File size exceeds 2MB limit', null, 400);
}

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($mimeType, $allowedTypes)) {
    ApiResponse::error('INVALID_FILE', 'Only image files are allowed', null, 400);
}

try {
    // Generate filename
    $extension = match ($mimeType) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        default => 'jpg'
    };
    $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $filename;

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        ApiResponse::error('UPLOAD_ERROR', 'Failed to save file', null, 500);
    }

    // Delete old avatar if exists
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $oldImage = $stmt->fetchColumn();

    if ($oldImage && file_exists(UPLOAD_DIR . $oldImage)) {
        unlink(UPLOAD_DIR . $oldImage);
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $stmt->execute([$filename, $user['id']]);

    ApiResponse::success([
        'message' => 'Avatar uploaded successfully',
        'profile_image' => $filename,
        'profile_image_url' => SITE_URL . '/uploads/' . $filename
    ]);

} catch (Exception $e) {
    error_log("Avatar upload error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to upload avatar', null, 500);
}
