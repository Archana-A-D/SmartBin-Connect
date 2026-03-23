<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

$batch_id = (int)($_POST['batch_id'] ?? 0);
if (!$batch_id) {
    echo json_encode(['success' => false, 'message' => 'Batch ID required.']);
    exit();
}

try {
    $pdo->beginTransaction();

    $ward = (int)($_SESSION['collector_ward'] ?? 0);

    $batchStmt = $pdo->prepare("
        SELECT * FROM batches
        WHERE id = :id AND ward_id = :ward
        LIMIT 1
    ");
    $batchStmt->execute([':id' => $batch_id, ':ward' => $ward]);
    $batch = $batchStmt->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Batch not found for your ward.']);
        exit();
    }

    // Mark batch completed
    $pdo->prepare("
        UPDATE batches
        SET status = 'completed', completed_at = NOW()
        WHERE id = :id AND ward_id = :ward
    ")->execute([':id' => $batch_id, ':ward' => $ward]);

    // Mark all remaining requests in batch as completed
    $pdo->prepare("
        UPDATE requests
        SET status = 'completed', batch_status = 'completed'
        WHERE batch_id = :bid
          AND ward_id = :ward
          AND status != 'completed'
    ")->execute([':bid' => $batch_id, ':ward' => $ward]);

    // Notify all users in batch
    $usersStmt = $pdo->prepare("
        SELECT DISTINCT user_id
        FROM requests
        WHERE batch_id = :bid AND ward_id = :ward
    ");
    $usersStmt->execute([':bid' => $batch_id, ':ward' => $ward]);
    $users = $usersStmt->fetchAll(PDO::FETCH_COLUMN);

    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, is_read, created_at)
        VALUES (:uid, 'info', :msg, 0, NOW())
    ");

    foreach ($users as $uid) {
        $msg = "✅ Your waste pickup for Batch #{$batch['batch_number']} (Ward {$batch['ward_id']}) has been completed. Thank you for using SmartWaste!";
        $notifStmt->execute([':uid' => $uid, ':msg' => $msg]);
    }

    // Check if next batch should be auto-created (15+ in queue)
    $queueStmt = $pdo->prepare("
        SELECT COUNT(*) FROM requests
        WHERE ward_id = :ward
        AND (batch_id IS NULL OR batch_id = 0)
        AND status NOT IN ('completed')
    ");
    $queueStmt->execute([':ward' => $batch['ward_id']]);
    $queued = (int)$queueStmt->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'notified'     => count($users),
        'queued_count' => $queued,
        'alert_next'   => $queued >= 15,
        'message'      => 'Batch completed. ' . count($users) . ' users notified.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
