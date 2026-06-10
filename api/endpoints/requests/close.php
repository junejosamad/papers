<?php
/**
 * PUT /api/requests/{id}/close
 * Close a request
 */

$user = AuthMiddleware::require();

if (!isset($requestId) || !$requestId) {
    ApiResponse::error('VALIDATION_ERROR', 'Request ID is required', null, 400);
}

try {
    // Check request exists and get owner
    $stmt = $pdo->prepare("SELECT id, user_id, course_code, status FROM paper_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        ApiResponse::error('NOT_FOUND', 'Request not found', null, 404);
    }

    if ($request['status'] === 'Closed') {
        ApiResponse::error('VALIDATION_ERROR', 'Request is already closed', null, 400);
    }

    // Check permission (owner only for API)
    if ($request['user_id'] != $user['id']) {
        ApiResponse::error('FORBIDDEN', 'You can only close your own requests', null, 403);
    }

    // Close request
    $stmt = $pdo->prepare("UPDATE paper_requests SET status = 'Closed' WHERE id = ?");
    $stmt->execute([$requestId]);

    ApiResponse::success(['message' => 'Request closed successfully']);

} catch (Exception $e) {
    error_log("Close request error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to close request', null, 500);
}
