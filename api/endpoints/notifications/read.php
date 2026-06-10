<?php
/**
 * PUT /api/notifications/{id}/read
 * Mark notification as read
 */

$user = AuthMiddleware::require();

if (!isset($notificationId) || !$notificationId) {
    ApiResponse::error('VALIDATION_ERROR', 'Notification ID is required', null, 400);
}

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $user['id']]);

    ApiResponse::success(['message' => 'Notification marked as read']);

} catch (Exception $e) {
    error_log("Mark read error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to mark as read', null, 500);
}
