<?php
/**
 * GET /api/notifications/unread
 * Get unread notification count
 */

$user = AuthMiddleware::require();

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $count = (int) $stmt->fetchColumn();

    ApiResponse::noCache();
    ApiResponse::success(['unread_count' => $count]);

} catch (Exception $e) {
    error_log("Unread count error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch unread count', null, 500);
}
