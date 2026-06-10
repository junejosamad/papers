<?php
/**
 * GET /api/papers/{id}
 * Get single paper details
 */

$user = AuthMiddleware::optional();
$userId = $user ? $user['id'] : null;

if (!isset($paperId) || !$paperId) {
    ApiResponse::error('VALIDATION_ERROR', 'Paper ID is required', null, 400);
}

try {
    $favSelect = $userId
        ? ", IF(f.id IS NOT NULL, 1, 0) as is_favorited"
        : ", 0 as is_favorited";
    $favJoin = $userId
        ? "LEFT JOIN favorites f ON p.id = f.paper_id AND f.user_id = $userId"
        : "";

    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.enrollment_number as uploader_enrollment,
               u.full_name as uploader_name,
               u.campus as uploader_campus
               $favSelect
        FROM papers p
        JOIN users u ON p.user_id = u.id
        $favJoin
        WHERE p.id = ?
    ");
    $stmt->execute([$paperId]);
    $paper = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paper) {
        ApiResponse::error('NOT_FOUND', 'Paper not found', null, 404);
    }

    // Format data
    $paper['is_favorited'] = (bool) $paper['is_favorited'];
    $paper['upload_date'] = date('c', strtotime($paper['upload_date']));

    // File size formatted
    $bytes = $paper['file_size'];
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    $paper['file_size_formatted'] = round($bytes, 2) . ' ' . $units[$i];

    // Generate file URL
    $paper['view_url'] = SITE_URL . '/view.php?id=' . $paper['id'];
    $paper['download_url'] = SITE_URL . '/api/papers/' . $paper['id'] . '/download';

    // Remove sensitive fields
    unset($paper['file_path']);
    unset($paper['file_name']);

    // Cache for 5 minutes
    ApiResponse::setCacheHeaders(300);

    ApiResponse::success($paper);

} catch (Exception $e) {
    error_log("Paper show error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Failed to fetch paper', null, 500);
}
