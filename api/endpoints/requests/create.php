<?php
/**
 * POST /api/requests
 * Create a new paper request
 */

$user = AuthMiddleware::require();
$input = getRequestInput();

// Validate input
$errors = validateInput($input, [
    'campus' => 'required',
    'course_code' => 'required|min:2|max:50'
]);

if (!empty($errors)) {
    ApiResponse::validationError($errors);
}

$campus = sanitize($input['campus']);
$courseCode = sanitize($input['course_code']);
$message = isset($input['message']) ? sanitize($input['message']) : null;

// Valid campuses
$validCampuses = ['Islamabad E-8', 'Islamabad H-11', 'Karachi', 'Karachi Health Sciences', 'Lahore'];
if (!in_array($campus, $validCampuses)) {
    ApiResponse::validationError(['campus' => 'Invalid campus selected']);
}

try {
    // Insert request
    $stmt = $pdo->prepare("
        INSERT INTO paper_requests (user_id, campus, course_code, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $campus, $courseCode, $message]);

    $requestId = $pdo->lastInsertId();

    // Fetch created request
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name, u.enrollment_number
        FROM paper_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    $request['created_at'] = date('c', strtotime($request['created_at']));
    $request['has_voted'] = false;
    $request['is_owner'] = true;
    $request['comment_count'] = 0;

    ApiResponse::success([
        'message' => 'Request posted successfully!',
        'request' => $request
    ], null, 201);

} catch (Exception $e) {
    error_log("Create request error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to create request', null, 500);
}
