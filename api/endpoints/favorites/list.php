<?php
/**
 * GET /api/favorites
 * List user's favorite papers
 */

$user = AuthMiddleware::require();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(MAX_PAGE_SIZE, max(1, (int) ($_GET['per_page'] ?? DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $total = (int) $stmt->fetchColumn();

    // Get favorites with paper details
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.file_type, p.campus, p.department,
               p.course_code, p.course_title, p.teacher_name, p.year, p.exam_type,
               p.views, p.downloads, p.upload_date, f.created_at as favorited_at,
               u.full_name as uploader_name
        FROM favorites f
        JOIN papers p ON f.paper_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user['id'], $perPage, $offset]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data
    foreach ($favorites as &$paper) {
        $paper['is_favorited'] = true;
        $paper['upload_date'] = date('c', strtotime($paper['upload_date']));
        $paper['favorited_at'] = date('c', strtotime($paper['favorited_at']));
    }

    ApiResponse::noCache();
    ApiResponse::paginated($favorites, $page, $perPage, $total);

} catch (Exception $e) {
    error_log("Favorites list error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch favorites', null, 500);
}
