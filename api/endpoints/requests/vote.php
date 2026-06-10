<?php
/**
 * POST/DELETE /api/requests/{id}/vote
 * Toggle vote on request
 */

$user = AuthMiddleware::require();

if (!isset($requestId) || !$requestId) {
    ApiResponse::error('VALIDATION_ERROR', 'Request ID is required', null, 400);
}

try {
    // Check request exists
    $stmt = $pdo->prepare("SELECT id, user_id, course_code FROM paper_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        ApiResponse::error('NOT_FOUND', 'Request not found', null, 404);
    }

    // Check if already voted
    $stmt = $pdo->prepare("SELECT id FROM request_votes WHERE request_id = ? AND user_id = ?");
    $stmt->execute([$requestId, $user['id']]);
    $existingVote = $stmt->fetch();

    if ($method === 'DELETE' || $existingVote) {
        // Remove vote
        $pdo->prepare("DELETE FROM request_votes WHERE request_id = ? AND user_id = ?")
            ->execute([$requestId, $user['id']]);
        $pdo->prepare("UPDATE paper_requests SET vote_count = vote_count - 1 WHERE id = ? AND vote_count > 0")
            ->execute([$requestId]);

        $hasVoted = false;
        $message = 'Vote removed';
    } else {
        // Add vote
        $pdo->prepare("INSERT INTO request_votes (request_id, user_id) VALUES (?, ?)")
            ->execute([$requestId, $user['id']]);
        $pdo->prepare("UPDATE paper_requests SET vote_count = vote_count + 1 WHERE id = ?")
            ->execute([$requestId]);

        // Create notification for request owner (if not self)
        if ($request['user_id'] != $user['id']) {
            $notifMsg = ($user['full_name'] ?? 'Someone') . " voted on your request for " . $request['course_code'];
            $link = "requests.php?search=" . urlencode($request['course_code']);
            $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'vote', ?, ?)")
                ->execute([$request['user_id'], $notifMsg, $link]);
        }

        $hasVoted = true;
        $message = 'Vote added';
    }

    // Get updated vote count
    $stmt = $pdo->prepare("SELECT vote_count FROM paper_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $voteCount = (int) $stmt->fetchColumn();

    ApiResponse::success([
        'message' => $message,
        'has_voted' => $hasVoted,
        'vote_count' => $voteCount
    ]);

} catch (Exception $e) {
    error_log("Vote error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to process vote', null, 500);
}
