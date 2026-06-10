<?php
/**
 * PUT /api/notifications/read-all
 * Mark all notifications as read
 */

$user = AuthMiddleware::require();

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);

    ApiResponse::success(['message' => 'All notifications marked as read']);

} catch (Exception $e) {
    error_log("Mark all read error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to mark all as read', null, 500);
}
