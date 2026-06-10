<?php
// ============================================
// AJAX: GET NOTIFICATIONS
// Save as: ajax/get_notifications.php
// ============================================
require_once '../config.php';
requireLogin();

// Fetch unread notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unread count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = $stmt->fetchColumn();

// Mark as fetched (not read, just fetched for polling context if needed, but usually mark read on click)

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
?>