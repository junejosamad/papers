<?php
/**
 * GET /api/activity
 * Get user activity log
 */

$user = AuthMiddleware::require();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;
$type = $_GET['type'] ?? '';

try {
    // Build query
    $where = "user_id = ?";
    $params = [$user['id']];

    if ($type && in_array($type, ['login', 'upload', 'download', 'view'])) {
        $where .= " AND action_type = ?";
        $params[] = $type;
    }

    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE $where");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    // Get activity with paper details
    $stmt = $pdo->prepare("
        SELECT a.id, a.action_type, a.created_at, a.ip_address,
               p.id as paper_id, p.title as paper_title, p.course_code
        FROM activity_logs a
        LEFT JOIN papers p ON a.paper_id = p.id
        WHERE a.$where
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($activities as &$activity) {
        $activity['created_at'] = date('c', strtotime($activity['created_at']));
    }

    ApiResponse::paginated($activities, $page, $perPage, $total);

} catch (Exception $e) {
    error_log("Activity error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch activity', null, 500);
}
