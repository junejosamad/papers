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

// Log view activity
// Log view activity (only for students)
if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'view', $paper_id);
}

// Increment views counter
$stmt = $pdo->prepare("UPDATE papers SET views = views + 1 WHERE id = ?");
$stmt->execute([$paper_id]);

// Set headers based on file type
if ($paper['file_type'] === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $paper['file_name'] . '"');
} else {
    // Image
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_path);
    finfo_close($finfo);

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $paper['file_name'] . '"');
}

header('Content-Length: ' . filesize($file_path));
header('Cache-Control: public, max-age=3600');

// Output file
readfile($file_path);
exit;
?>