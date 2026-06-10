<?php
/**
 * GET /api/requests/{id}/comments
 * Get comments for a request
 */

if (!isset($requestId) || !$requestId) {
    ApiResponse::error('VALIDATION_ERROR', 'Request ID is required', null, 400);
}

try {
    // Check request exists
    $stmt = $pdo->prepare("SELECT id FROM paper_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    if (!$stmt->fetch()) {
        ApiResponse::error('NOT_FOUND', 'Request not found', null, 404);
    }

    // Get comments
    $stmt = $pdo->prepare("
        SELECT c.id, c.message, c.created_at, u.full_name, u.enrollment_number
        FROM request_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.request_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$requestId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($comments as &$comment) {
        $comment['created_at'] = date('c', strtotime($comment['created_at']));
    }

    ApiResponse::success($comments);

} catch (Exception $e) {
    error_log("Get comments error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch comments', null, 500);
}
