<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

$collectorWard = (int)($_SESSION['collector_ward'] ?? 0);

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Request ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE requests
        SET status = 'requested',
            unavailable = 1,
            pickup_date = NULL,
            rescheduled_date = NULL,
            batch_id = NULL,
            batch_status = 'queued'
        WHERE id = :id
          AND ward_id = :ward
          AND status IN ('requested', 'accepted')
    ");
    $stmt->execute([':id' => $id, ':ward' => $collectorWard]);

    echo json_encode([
        'success' => $stmt->rowCount() > 0,
        'message' => $stmt->rowCount() > 0 ? 'Request skipped' : 'Request not found or already processed'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to skip request']);
}
?>
