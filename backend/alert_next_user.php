<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

try {
    // Only set if not already alerted and not completed
    $stmt = $pdo->prepare("
        UPDATE requests 
        SET alert_sent = 1 
        WHERE id = :id 
        AND alert_sent = 0 
        AND status != 'completed'
    ");
    $stmt->execute([':id' => $request_id]);

    echo json_encode([
        'success' => true,
        'alerted' => $stmt->rowCount() > 0
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>