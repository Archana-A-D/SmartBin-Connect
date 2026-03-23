<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

$ward = $_SESSION['collector_ward'] ?? null;
$batchSize = 15;

if ($ward === null || $ward === '') {
    echo json_encode(['success' => false, 'message' => 'Ward not assigned']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id
        FROM requests
        WHERE ward_id = :ward
          AND status = 'requested'
          AND (batch_id IS NULL OR batch_id = 0)
        ORDER BY request_time ASC
        LIMIT {$batchSize}
    ");
    $stmt->execute([':ward' => $ward]);
    $requestIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($requestIds) < $batchSize) {
        echo json_encode([
            'success' => false,
            'message' => "At least {$batchSize} queued requests are needed to schedule a batch."
        ]);
        exit();
    }

    $pickupDate = date('Y-m-d', strtotime('+1 day'));
    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE requests
        SET status = 'scheduled',
            pickup_date = :pickup_date,
            route_order = :route_order
        WHERE id = :id
    ");

    foreach ($requestIds as $index => $id) {
        $update->execute([
            ':pickup_date' => $pickupDate,
            ':route_order' => $index + 1,
            ':id' => $id,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'pickup_date' => $pickupDate,
        'count' => count($requestIds),
        'message' => 'Requests scheduled successfully'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(['success' => false, 'message' => 'Failed to schedule requests']);
}
?>
