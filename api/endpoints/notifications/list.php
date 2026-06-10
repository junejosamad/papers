<?php
/**
 * GET /api/notifications
 * Get user notifications
 */

$user = AuthMiddleware::require();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $total = (int) $stmt->fetchColumn();

    // Get notifications
    $stmt = $pdo->prepare("
        SELECT id, type, message, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user['id'], $perPage, $offset]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notifications as &$notif) {
        $notif['is_read'] = (bool) $notif['is_read'];
        $notif['created_at'] = date('c', strtotime($notif['created_at']));
    }

    ApiResponse::noCache();
    ApiResponse::paginated($notifications, $page, $perPage, $total);

} catch (Exception $e) {
    error_log("Notifications error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch notifications', null, 500);
}
