<?php
/**
 * POST /api/favorites/{paper_id}
 * Add paper to favorites
 */

$user = AuthMiddleware::require();

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

    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND paper_id = ?");
    $stmt->execute([$user['id'], $paperId]);
    if ($stmt->fetch()) {
        ApiResponse::success(['message' => 'Already in favorites', 'is_favorited' => true]);
    }

    // Add to favorites
    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, paper_id) VALUES (?, ?)");
    $stmt->execute([$user['id'], $paperId]);

    ApiResponse::success(['message' => 'Added to favorites', 'is_favorited' => true], null, 201);

} catch (Exception $e) {
    error_log("Add favorite error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to add favorite', null, 500);
}
