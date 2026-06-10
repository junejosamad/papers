<?php
/**
 * PUT /api/requests/{id}/fulfill
 * Mark request as fulfilled
 */

$user = AuthMiddleware::require();

if (!isset($requestId) || !$requestId) {
    ApiResponse::error('VALIDATION_ERROR', 'Request ID is required', null, 400);
}

try {
    // Check request exists
    $stmt = $pdo->prepare("SELECT id, user_id, status FROM paper_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        ApiResponse::error('NOT_FOUND', 'Request not found', null, 404);
    }

    if ($request['status'] !== 'Open') {
        ApiResponse::error('VALIDATION_ERROR', 'Only open requests can be fulfilled', null, 400);
    }

    // Check permission (owner only)
    if ($request['user_id'] != $user['id']) {
        ApiResponse::error('FORBIDDEN', 'You can only fulfill your own requests', null, 403);
    }

    // Mark as fulfilled
    $stmt = $pdo->prepare("UPDATE paper_requests SET status = 'Fulfilled' WHERE id = ?");
    $stmt->execute([$requestId]);

    ApiResponse::success(['message' => 'Request marked as fulfilled']);

} catch (Exception $e) {
    error_log("Fulfill request error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fulfill request', null, 500);
}
