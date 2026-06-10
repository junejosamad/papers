<?php
// ============================================
// REPORT PAPER HANDLER
// Save as: report.php
// ============================================
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$paper_id = (int)($_POST['paper_id'] ?? 0);
$report_type = sanitize($_POST['report_type'] ?? '');
$description = sanitize($_POST['description'] ?? '');

// Validate inputs
if (!$paper_id || !$report_type || !$description) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Check if paper exists
$stmt = $pdo->prepare("SELECT id FROM papers WHERE id = ?");
$stmt->execute([$paper_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Paper not found']);
    exit;
}

// Check if user already reported this paper
$stmt = $pdo->prepare("
    SELECT id FROM paper_reports 
    WHERE paper_id = ? AND user_id = ? AND status = 'pending'
");
$stmt->execute([$paper_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reported this paper']);
    exit;
}

try {
    // Insert report
    $stmt = $pdo->prepare("
        INSERT INTO paper_reports (paper_id, user_id, report_type, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$paper_id, $_SESSION['user_id'], $report_type, $description]);
    
    // Update paper report count
    $stmt = $pdo->prepare("UPDATE papers SET report_count = report_count + 1 WHERE id = ?");
    $stmt->execute([$paper_id]);
    
    // Log activity
    logActivity($pdo, $_SESSION['user_id'], 'report', $paper_id);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your report. We will review it shortly.'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit report. Please try again.']);
}
?>