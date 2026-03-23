<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

$batch_id    = (int)($_POST['batch_id']    ?? 0);
$pickup_date = trim($_POST['pickup_date']  ?? '');

if (!$batch_id || !$pickup_date) {
    echo json_encode(['success' => false, 'message' => 'Batch ID and pickup date are required.']);
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickup_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit();
}

// Must be today or future
if (strtotime($pickup_date) < strtotime('today')) {
    echo json_encode(['success' => false, 'message' => 'Pickup date must be today or in the future.']);
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

    // Update batch
    $stmt = $pdo->prepare("
        UPDATE batches
        SET pickup_date = :date, status = 'scheduled'
        WHERE id = :id AND ward_id = :ward
    ");
    $stmt->execute([':date' => $pickup_date, ':id' => $batch_id, ':ward' => $ward]);

    // Update all requests in this batch with the pickup date
    $stmt = $pdo->prepare("
        UPDATE requests
        SET pickup_date = :date
        WHERE batch_id = :bid
        AND ward_id = :ward
        AND status != 'completed'
    ");
    $stmt->execute([':date' => $pickup_date, ':bid' => $batch_id, ':ward' => $ward]);

    // Get all users in this batch
    $usersStmt = $pdo->prepare("
        SELECT DISTINCT user_id FROM requests
        WHERE batch_id = :bid
        AND ward_id = :ward
    ");
    $usersStmt->execute([':bid' => $batch_id, ':ward' => $ward]);
    $users = $usersStmt->fetchAll(PDO::FETCH_COLUMN);

    // Format date nicely
    $dateFormatted = date('l, d M Y', strtotime($pickup_date));
    $ward          = $batch['ward_id'];

    // Notify each user
    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, is_read, created_at)
        VALUES (:uid, 'info', :msg, 0, NOW())
    ");

    foreach ($users as $user_id) {
        $msg = "Your waste pickup (Ward $ward, Batch #{$batch['batch_number']}) has been scheduled for $dateFormatted. Please have your waste ready by 8:00 AM.";
        $notifStmt->execute([':uid' => $user_id, ':msg' => $msg]);
    }

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'pickup_date'  => $pickup_date,
        'notified'     => count($users),
        'message'      => count($users) . ' users notified of pickup on ' . $dateFormatted
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
