<?php
/**
 * GET /api/user/stats
 * Get user statistics
 */

$user = AuthMiddleware::require();

try {
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM papers WHERE user_id = ?) as total_uploads,
            (SELECT COALESCE(SUM(downloads), 0) FROM papers WHERE user_id = ?) as total_downloads,
            (SELECT COALESCE(SUM(views), 0) FROM papers WHERE user_id = ?) as total_views,
            (SELECT COUNT(*) FROM favorites WHERE user_id = ?) as total_favorites,
            (SELECT COUNT(*) FROM paper_requests WHERE user_id = ?) as total_requests,
            (SELECT COUNT(*) FROM activity_logs WHERE user_id = ? AND action_type = 'download') as papers_downloaded
    ");
    $stmt->execute([
        $user['id'],
        $user['id'],
        $user['id'],
        $user['id'],
        $user['id'],
        $user['id']
    ]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Convert to integers
    foreach ($stats as $key => $value) {
        $stats[$key] = (int) $value;
    }

    ApiResponse::success($stats);

} catch (Exception $e) {
    error_log("Stats error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch stats', null, 500);
}
