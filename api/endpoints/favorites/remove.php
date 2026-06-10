<?php
/**
 * DELETE /api/favorites/{paper_id}
 * Remove paper from favorites
 */

$user = AuthMiddleware::require();

if (!isset($paperId) || !$paperId) {
    ApiResponse::error('VALIDATION_ERROR', 'Paper ID is required', null, 400);
}

try {
    // Remove from favorites
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND paper_id = ?");
    $stmt->execute([$user['id'], $paperId]);

    ApiResponse::success(['message' => 'Removed from favorites', 'is_favorited' => false]);

} catch (Exception $e) {
    error_log("Remove favorite error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to remove favorite', null, 500);
}
