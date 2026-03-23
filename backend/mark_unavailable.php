<?php
/*
 * backend/mark_unavailable.php
 * Marks a request as unavailable and moves it to the next available batch
 * POST params: id (int)
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

$id      = (int)($_POST['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

try {
    // Verify request belongs to this user and is eligible
    $check = $pdo->prepare("
        SELECT r.id, r.ward_id, r.batch_id, r.pickup_date
        FROM requests r
        WHERE r.id = :id 
        AND r.user_id = :uid 
        AND r.status IN ('requested','accepted','rescheduled')
        AND r.unavailable = 0
    ");
    $check->execute([':id' => $id, ':uid' => $user_id]);
    $request = $check->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or not eligible']);
        exit();
    }

    $ward_id          = $request['ward_id'];
    $current_batch_id = $request['batch_id'];
    $current_date     = $request['pickup_date'];

    // Find the next scheduled batch for this ward
    // (a future batch that has a pickup_date set and is not completed)
    $nextBatchStmt = $pdo->prepare("
        SELECT id, pickup_date, batch_number
        FROM batches
        WHERE ward_id = :ward
        AND status NOT IN ('completed')
        AND id != :current_batch
        AND pickup_date IS NOT NULL
        AND pickup_date > CURDATE()
        ORDER BY pickup_date ASC
        LIMIT 1
    ");
    $nextBatchStmt->execute([
        ':ward'          => $ward_id,
        ':current_batch' => $current_batch_id ?: 0
    ]);
    $nextBatch = $nextBatchStmt->fetch(PDO::FETCH_ASSOC);

    if ($nextBatch) {
        // Move to next scheduled batch
        $new_date        = $nextBatch['pickup_date'];
        $next_batch_id   = $nextBatch['id'];
        $next_batch_num  = $nextBatch['batch_number'];

        $stmt = $pdo->prepare("
            UPDATE requests
            SET unavailable      = 1,
                rescheduled_date = :new_date,
                pickup_date      = :new_date,
                batch_id         = :batch_id,
                batch_status     = 'active'
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([
            ':new_date'  => $new_date,
            ':batch_id'  => $next_batch_id,
            ':id'        => $id,
            ':uid'       => $user_id
        ]);

        // Notify user about reschedule
        $dateFormatted = date('l, d M Y', strtotime($new_date));
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, is_read, created_at)
            VALUES (:uid, 'reschedule', :msg, 0, NOW())
        ");
        $notifStmt->execute([
            ':uid' => $user_id,
            ':msg' => "Your pickup request has been rescheduled to Batch #{$next_batch_num} on {$dateFormatted} (Ward {$ward_id}). We'll see you then!"
        ]);

        echo json_encode([
            'success'         => true,
            'new_date'        => $new_date,
            'next_batch_num'  => $next_batch_num,
            'message'         => "Rescheduled to Batch #{$next_batch_num} on {$dateFormatted}"
        ]);

    } else {
        // No next batch with a date yet — just mark unavailable
        // and remove from current batch so it goes back to queue
        $stmt = $pdo->prepare("
            UPDATE requests
            SET unavailable      = 1,
                rescheduled_date = NULL,
                pickup_date      = NULL,
                batch_id         = NULL,
                batch_status     = 'queued'
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([':id' => $id, ':uid' => $user_id]);

        // Notify user
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, is_read, created_at)
            VALUES (:uid, 'reschedule', :msg, 0, NOW())
        ");
        $notifStmt->execute([
            ':uid' => $user_id,
            ':msg' => "Your pickup request (Ward {$ward_id}) has been moved back to the queue. You will be notified once a new pickup date is scheduled by the collector."
        ]);

        echo json_encode([
            'success'  => true,
            'new_date' => null,
            'message'  => 'Moved back to queue. You will be notified when a date is set.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
