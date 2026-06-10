<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$paper_id = $data['paper_id'] ?? null;

if (!$paper_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paper ID required']);
    exit;
}

try {
    // Check if already favorite
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND paper_id = ?");
    $stmt->execute([$_SESSION['user_id'], $paper_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Remove
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND paper_id = ?");
        $stmt->execute([$_SESSION['user_id'], $paper_id]);
        $action = 'removed';
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, paper_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $paper_id]);
        $action = 'added';
    }

    echo json_encode(['success' => true, 'action' => $action]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>