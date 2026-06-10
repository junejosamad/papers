<?php
/**
 * POST /api/papers/{id}/view
 * Log paper view
 */

$user = AuthMiddleware::optional();
$userId = $user ? $user['id'] : null;

if (!isset($paperId) || !$paperId) {
    ApiResponse::error('VALIDATION_ERROR', 'Paper ID is required', null, 400);
}

try {
    // Check paper exists
    $stmt = $pdo->prepare("SELECT id FROM papers WHERE id = ?");
    $stmt->execute([$paperId]);

    if (!$stmt->fetch()) {
        ApiResponse::error('NOT_FOUND', 'Paper not found', null, 404);
    }

    // Update view count
    $stmt = $pdo->prepare("UPDATE papers SET views = views + 1 WHERE id = ?");
    $stmt->execute([$paperId]);

    // Log activity if user is logged in
    if ($userId) {
        logActivity($pdo, $userId, 'view', $paperId);
    }

    ApiResponse::success(['message' => 'View logged']);

} catch (Exception $e) {
    error_log("View log error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to log view', null, 500);
}
