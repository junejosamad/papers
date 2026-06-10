<?php
/**
 * GET /api/papers/{id}/download
 * Download paper file
 */

$user = AuthMiddleware::optional();
$userId = $user ? $user['id'] : null;

if (!isset($paperId) || !$paperId) {
    ApiResponse::error('VALIDATION_ERROR', 'Paper ID is required', null, 400);
}

try {
    // Get paper
    $stmt = $pdo->prepare("SELECT id, title, file_name, file_path, file_type FROM papers WHERE id = ?");
    $stmt->execute([$paperId]);
    $paper = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paper) {
        ApiResponse::error('NOT_FOUND', 'Paper not found', null, 404);
    }

    $filePath = UPLOAD_DIR . $paper['file_path'];

    if (!file_exists($filePath)) {
        ApiResponse::error('NOT_FOUND', 'File not found', null, 404);
    }

    // Update download count
    $stmt = $pdo->prepare("UPDATE papers SET downloads = downloads + 1 WHERE id = ?");
    $stmt->execute([$paperId]);

    // Log activity if user is logged in
    if ($userId) {
        logActivity($pdo, $userId, 'download', $paperId);
    }

    // Set headers for download
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];

    $extension = strtolower(pathinfo($paper['file_path'], PATHINFO_EXTENSION));
    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

    // Clean filename for download
    $downloadName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $paper['title']) . '.' . $extension;

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    // Stream file
    readfile($filePath);
    exit;

} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Download failed', null, 500);
}
