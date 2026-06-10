<?php
/**
 * GET /api/favorites/check/{paper_id}
 * Check if paper is favorited
 */

$user = AuthMiddleware::require();

if (!isset($paperId) || !$paperId) {
    ApiResponse::error('VALIDATION_ERROR', 'Paper ID is required', null, 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND paper_id = ?");
    $stmt->execute([$user['id'], $paperId]);
    $isFavorited = $stmt->fetch() ? true : false;

    ApiResponse::success(['is_favorited' => $isFavorited]);

} catch (Exception $e) {
    error_log("Check favorite error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to check favorite', null, 500);
}
