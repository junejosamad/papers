<?php
/**
 * GET /api/requests
 * List community paper requests
 */

$user = AuthMiddleware::optional();
$userId = $user ? $user['id'] : 0;

// Get filters
$status = $_GET['status'] ?? 'Open';
$campus = $_GET['campus'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'votes';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(MAX_PAGE_SIZE, max(1, (int) ($_GET['per_page'] ?? DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $perPage;

// Build query
$where = ["1=1"];
$params = [$userId];

if ($status !== 'All') {
    $where[] = "r.status = ?";
    $params[] = $status;
}

if ($campus) {
    $where[] = "r.campus = ?";
    $params[] = $campus;
}

if ($search) {
    $where[] = "(r.course_code LIKE ? OR r.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

$orderBy = match ($sort) {
    'newest' => 'r.created_at DESC',
    default => 'r.vote_count DESC, r.created_at DESC'
};

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM paper_requests r WHERE " . implode(' AND ', array_slice($where, 0));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM paper_requests r WHERE $whereClause");
    $stmt->execute(array_slice($params, 1)); // Skip user_id for count
    $total = (int) $stmt->fetchColumn();

    // Get requests with vote status
    $stmt = $pdo->prepare("
        SELECT r.id, r.campus, r.course_code, r.message, r.status, r.vote_count,
               r.created_at, u.full_name, u.enrollment_number,
               (SELECT COUNT(*) FROM request_votes v WHERE v.request_id = r.id AND v.user_id = ?) as has_voted,
               (SELECT COUNT(*) FROM request_comments c WHERE c.request_id = r.id) as comment_count
        FROM paper_requests r
        JOIN users u ON r.user_id = u.id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ");
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data
    foreach ($requests as &$request) {
        $request['has_voted'] = (bool) $request['has_voted'];
        $request['created_at'] = date('c', strtotime($request['created_at']));
        $request['is_owner'] = $user && $request['enrollment_number'] === $user['enrollment_number'];
    }

    ApiResponse::setCacheHeaders(60); // 1 minute cache
    ApiResponse::paginated($requests, $page, $perPage, $total);

} catch (Exception $e) {
    error_log("Requests list error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch requests', null, 500);
}
