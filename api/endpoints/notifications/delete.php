<?php
/**
 * DELETE /api/notifications/{id}
 * Delete notification
 */

$user = AuthMiddleware::require();

if (!isset($notificationId) || !$notificationId) {
    ApiResponse::error('VALIDATION_ERROR', 'Notification ID is required', null, 400);
}

try {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $user['id']]);

    ApiResponse::success(['message' => 'Notification deleted']);

} catch (Exception $e) {
    error_log("Delete notification error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to delete notification', null, 500);
}
