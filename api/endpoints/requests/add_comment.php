<?php
/**
 * POST /api/requests/{id}/comments
 * Add comment to a request
 */

$user = AuthMiddleware::require();
$input = getRequestInput();

if (!isset($requestId) || !$requestId) {
    ApiResponse::error('VALIDATION_ERROR', 'Request ID is required', null, 400);
}

$errors = validateInput($input, [
    'message' => 'required|min:1|max:500'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$message = sanitize($input['message']);

try {
    // Check request exists
    $stmt = $pdo->prepare("SELECT id, user_id, course_code FROM paper_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        ApiResponse::error('NOT_FOUND', 'Request not found', null, 404);
    }

    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO request_comments (request_id, user_id, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$requestId, $user['id'], $message]);

    $commentId = $pdo->lastInsertId();

    // Create notification for request owner (if not self)
    if ($request['user_id'] != $user['id']) {
        $notifMsg = ($user['full_name'] ?? 'Someone') . " commented on your request for " . $request['course_code'];
        $link = "requests.php?search=" . urlencode($request['course_code']);
        $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'comment', ?, ?)")
            ->execute([$request['user_id'], $notifMsg, $link]);
    }

    // Return created comment
    $comment = [
        'id' => $commentId,
        'message' => $message,
        'full_name' => $user['full_name'],
        'enrollment_number' => $user['enrollment_number'],
        'created_at' => date('c')
    ];

    ApiResponse::success([
        'message' => 'Comment added',
        'comment' => $comment
    ], null, 201);

} catch (Exception $e) {
    error_log("Add comment error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to add comment', null, 500);
}
