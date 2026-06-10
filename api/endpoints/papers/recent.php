<?php
/**
 * GET /api/papers/recent
 * Get recently uploaded papers
 */

$user = AuthMiddleware::optional();
$userId = $user ? $user['id'] : null;
$limit = min(20, max(5, (int) ($_GET['limit'] ?? 10)));

// Cache key
$cacheKey = "papers_recent_$limit";

try {
    $data = ApiCache::remember($cacheKey, CACHE_TTL_SHORT, function () use ($pdo, $userId, $limit) {
        $favSelect = $userId
            ? ", IF(f.id IS NOT NULL, 1, 0) as is_favorited"
            : ", 0 as is_favorited";
        $favJoin = $userId
            ? "LEFT JOIN favorites f ON p.id = f.paper_id AND f.user_id = $userId"
            : "";

        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.file_type, p.campus, p.department,
                   p.course_code, p.course_title, p.year, p.exam_type,
                   p.views, p.downloads, p.upload_date,
                   u.full_name as uploader_name
                   $favSelect
            FROM papers p
            JOIN users u ON p.user_id = u.id
            $favJoin
            ORDER BY p.upload_date DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    });

    // Format data
    foreach ($data as &$paper) {
        $paper['is_favorited'] = (bool) $paper['is_favorited'];
        $paper['upload_date'] = date('c', strtotime($paper['upload_date']));
    }

    ApiResponse::setCacheHeaders(CACHE_TTL_SHORT);
    ApiResponse::success($data);

} catch (Exception $e) {
    error_log("Recent papers error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch recent papers', null, 500);
}
