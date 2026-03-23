<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

$ward         = $_SESSION['collector_ward'];
$collector_id = $_SESSION['collector_id'];
$batch_size   = 15;

try {
    $pdo->beginTransaction();

    // Count queued requests
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM requests
        WHERE ward_id = :ward
        AND (batch_id IS NULL OR batch_id = 0)
        AND status NOT IN ('completed')
    ");
    $countStmt->execute([':ward' => $ward]);
    $queued = (int)$countStmt->fetchColumn();

   if ($queued === 0) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'No queued requests to batch.']);
    exit();
}

if ($queued < $batch_size) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => "Need at least {$batch_size} requests to create a batch. Currently {$queued} in queue."
    ]);
    exit();
}

    // Get next batch number for this ward
    $numStmt = $pdo->prepare("SELECT COALESCE(MAX(batch_number),0)+1 FROM batches WHERE ward_id = :ward");
    $numStmt->execute([':ward' => $ward]);
    $batch_number = (int)$numStmt->fetchColumn();

    $take = min($queued, $batch_size);

    // Create batch record
    $insStmt = $pdo->prepare("
        INSERT INTO batches (ward_id, batch_number, status, total_requests, created_at)
        VALUES (:ward, :num, 'pending', :total, NOW())
    ");
    $insStmt->execute([':ward' => $ward, ':num' => $batch_number, ':total' => $take]);
    $batch_id = $pdo->lastInsertId();

    // Assign oldest $take queued requests to this batch
    $reqStmt = $pdo->prepare("
        SELECT id FROM requests
        WHERE ward_id = :ward
        AND (batch_id IS NULL OR batch_id = 0)
        AND status NOT IN ('completed')
        ORDER BY request_time ASC
        LIMIT :lim
    ");
    $reqStmt->bindValue(':ward', $ward);
    $reqStmt->bindValue(':lim',  $take, PDO::PARAM_INT);
    $reqStmt->execute();
    $ids = $reqStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $updStmt = $pdo->prepare("
            UPDATE requests
            SET batch_id = ?, batch_status = 'active'
            WHERE id IN ($placeholders)
        ");
        $updStmt->execute(array_merge([$batch_id], $ids));
    }

    // Notify collector
    $msg = "New batch #$batch_number created for Ward $ward with $take pickup requests. Set a pickup date to notify users.";
    $notifStmt = $pdo->prepare("
        INSERT INTO collector_notifications (collector_id, ward_id, type, message, created_at)
        VALUES (:cid, :ward, 'batch_ready', :msg, NOW())
    ");
    $notifStmt->execute([':cid' => $collector_id, ':ward' => $ward, ':msg' => $msg]);

    $pdo->commit();

    // Check if more requests are still queued
    $countStmt->execute([':ward' => $ward]);
    $remaining = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success'      => true,
        'batch_id'     => $batch_id,
        'batch_number' => $batch_number,
        'total'        => $take,
        'remaining'    => $remaining,
        'message'      => "Batch #$batch_number created with $take requests."
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
