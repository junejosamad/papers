<?php
require_once 'config.php';
requireLogin();

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$paper_id = (int) $_GET['id'];

// Get paper info
$stmt = $pdo->prepare("SELECT * FROM papers WHERE id = ?");
$stmt->execute([$paper_id]);
$paper = $stmt->fetch();

if (!$paper) {
    header('Location: dashboard.php?error=Paper not found');
    exit;
}

$file_path = UPLOAD_DIR . $paper['file_path'];

if (!file_exists($file_path)) {
    header('Location: dashboard.php?error=File not found');
    exit;
}

// Update download count
$stmt = $pdo->prepare("UPDATE papers SET downloads = downloads + 1 WHERE id = ?");
$stmt->execute([$paper_id]);

// Log activity
// Log activity (only for students)
if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'download', $paper_id);
}

// Set headers for download
header('Content-Type: application/octet-stream');
header(contentDispositionHeader('attachment', $paper['file_name']));
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
readfile($file_path);
exit;
?>