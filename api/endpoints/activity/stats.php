<?php
/**
 * GET /api/activity/stats
 * Get activity statistics
 */

$user = AuthMiddleware::require();

try {
    $stmt = $pdo->prepare("
        SELECT action_type, COUNT(*) as count
        FROM activity_logs
        WHERE user_id = ?
        GROUP BY action_type
    ");
    $stmt->execute([$user['id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [
        'logins' => 0,
        'uploads' => 0,
        'downloads' => 0,
        'views' => 0
    ];

    foreach ($results as $row) {
        $key = $row['action_type'] . 's';
        if (isset($stats[$key])) {
            $stats[$key] = (int) $row['count'];
        }
    }

    ApiResponse::success($stats);

} catch (Exception $e) {
    error_log("Activity stats error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch stats', null, 500);
}
