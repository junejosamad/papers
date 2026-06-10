<?php
/**
 * GET /api/user/profile
 * Get user profile
 */

$user = AuthMiddleware::require();

try {
    // Get full profile with stats
    $stmt = $pdo->prepare("
        SELECT u.id, u.enrollment_number, u.email, u.full_name, u.campus, 
               u.department, u.semester, u.profile_image, u.whatsapp_number,
               u.created_at, u.last_login,
               (SELECT COUNT(*) FROM papers WHERE user_id = u.id) as uploads_count,
               (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) as favorites_count,
               (SELECT COUNT(*) FROM paper_requests WHERE user_id = u.id) as requests_count
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    $profile['created_at'] = date('c', strtotime($profile['created_at']));
    $profile['last_login'] = $profile['last_login'] ? date('c', strtotime($profile['last_login'])) : null;

    if ($profile['profile_image']) {
        $profile['profile_image_url'] = SITE_URL . '/uploads/' . $profile['profile_image'];
    }

    ApiResponse::noCache();
    ApiResponse::success($profile);

} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch profile', null, 500);
}
