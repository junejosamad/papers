<?php
/**
 * GET /api/user/uploads
 * Get user's uploaded papers
 */

$user = AuthMiddleware::require();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(MAX_PAGE_SIZE, max(1, (int) ($_GET['per_page'] ?? DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM papers WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $total = (int) $stmt->fetchColumn();

    // Get uploads
    $stmt = $pdo->prepare("
        SELECT id, title, file_type, campus, department, course_code, course_title,
               year, exam_type, views, downloads, upload_date
        FROM papers
        WHERE user_id = ?
        ORDER BY upload_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user['id'], $perPage, $offset]);
    $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($papers as &$paper) {
        $paper['upload_date'] = date('c', strtotime($paper['upload_date']));
    }

    ApiResponse::paginated($papers, $page, $perPage, $total);

} catch (Exception $e) {
    error_log("Uploads error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch uploads', null, 500);
}
