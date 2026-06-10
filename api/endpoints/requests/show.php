<?php
/**
 * GET /api/requests/{id}
 * Get single request with comments
 */

$user = AuthMiddleware::optional();
$userId = $user ? $user['id'] : 0;

if (!isset($requestId) || !$requestId) {
    ApiResponse::error('VALIDATION_ERROR', 'Request ID is required', null, 400);
}

try {
    // Get request
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name, u.enrollment_number,
               (SELECT COUNT(*) FROM request_votes v WHERE v.request_id = r.id AND v.user_id = ?) as has_voted
        FROM paper_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$userId, $requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
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

    // Format data
    $request['has_voted'] = (bool) $request['has_voted'];
    $request['created_at'] = date('c', strtotime($request['created_at']));
    $request['is_owner'] = $user && $request['user_id'] == $user['id'];

    foreach ($comments as &$comment) {
        $comment['created_at'] = date('c', strtotime($comment['created_at']));
    }

    $request['comments'] = $comments;
    $request['comment_count'] = count($comments);

    ApiResponse::success($request);

} catch (Exception $e) {
    error_log("Request show error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch request', null, 500);
}
