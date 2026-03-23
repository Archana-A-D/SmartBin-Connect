<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['error' => 'Unauthorised']);
    exit();
}

$ward     = $_SESSION['collector_ward'];
$type     = $_GET['type']     ?? 'queued';   // queued | batch
$batch_id = $_GET['batch_id'] ?? null;

try {
    if ($type === 'queued') {
        // Unassigned requests waiting in queue
        $stmt = $pdo->prepare("
            SELECT * FROM requests
            WHERE ward_id = :ward
            AND (batch_id IS NULL OR batch_id = 0)
            AND status NOT IN ('completed')
            ORDER BY request_time ASC
        ");
        $stmt->execute([':ward' => $ward]);

    } elseif ($type === 'batch' && $batch_id) {
        // Requests belonging to a specific batch
        $stmt = $pdo->prepare("
            SELECT * FROM requests
            WHERE batch_id = :bid
            ORDER BY route_order ASC, request_time ASC
        ");
        $stmt->execute([':bid' => $batch_id]);

    } elseif ($type === 'history') {
        // Completed requests
        $stmt = $pdo->prepare("
            SELECT r.*, b.pickup_date as batch_date, b.batch_number
            FROM requests r
            LEFT JOIN batches b ON b.id = r.batch_id
            WHERE r.ward_id = :ward
            AND r.status = 'completed'
            ORDER BY r.request_time DESC
        ");
        $stmt->execute([':ward' => $ward]);

    } else {
        echo json_encode([]);
        exit();
    }

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>