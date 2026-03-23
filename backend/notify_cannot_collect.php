<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

require_once 'db.php';

$ward = trim($_POST['ward'] ?? '');
$reason = trim($_POST['reason'] ?? 'other');
$note = trim($_POST['note'] ?? '');
$rescheduleDate = trim($_POST['reschedule_date'] ?? '');

if ($ward === '') {
    echo json_encode(['success' => false, 'message' => 'Ward is required']);
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rescheduleDate)) {
    echo json_encode(['success' => false, 'message' => 'Valid reschedule date is required']);
    exit();
}

$selectedDate = DateTime::createFromFormat('Y-m-d', $rescheduleDate);
$today = new DateTime('today');

if (!$selectedDate || $selectedDate->format('Y-m-d') !== $rescheduleDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid reschedule date']);
    exit();
}

if ($selectedDate < $today) {
    echo json_encode(['success' => false, 'message' => 'Reschedule date cannot be in the past']);
    exit();
}

$reasonLabels = [
    'vehicle_breakdown' => 'Vehicle Breakdown',
    'staff_shortage' => 'Staff Shortage',
    'road_condition' => 'Poor Road Condition',
    'emergency' => 'Emergency',
    'weather' => 'Adverse Weather',
    'other' => 'Other'
];

$reasonText = $reasonLabels[$reason] ?? 'Other';
$rescheduleDateLabel = $selectedDate->format('d M Y');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id, user_id
         FROM requests
         WHERE ward_id = :ward AND status IN ('requested', 'accepted')"
    );
    $stmt->execute([':ward' => $ward]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $affectedCount = count($requests);

    if ($affectedCount > 0) {
        $update = $pdo->prepare(
            "UPDATE requests
             SET status = 'rescheduled',
                 rescheduled_date = :rescheduled_date,
                 pickup_date = :pickup_date
             WHERE ward_id = :ward AND status IN ('requested', 'accepted')"
        );
        $update->execute([
            ':rescheduled_date' => $rescheduleDate,
            ':pickup_date' => $rescheduleDate,
            ':ward' => $ward,
        ]);

        $notificationStmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, type, message, created_at)
             VALUES (:user_id, 'reschedule', :message, NOW())"
        );

        foreach ($requests as $request) {
            $message = "Your waste pickup for Ward {$ward} could not be completed due to {$reasonText}. "
                . "It has been rescheduled to {$rescheduleDateLabel}.";

            if ($note !== '') {
                $message .= " Collector note: {$note}";
            }

            $notificationStmt->execute([
                ':user_id' => $request['user_id'],
                ':message' => $message,
            ]);
        }
    }

    $adminMessage = "Collector for Ward {$ward} reported inability to collect on "
        . $today->format('Y-m-d')
        . ". Reason: {$reasonText}. Affected pickups: {$affectedCount}. Rescheduled to {$rescheduleDate}.";

    if ($note !== '') {
        $adminMessage .= " Note: {$note}";
    }

    $adminStmt = $pdo->prepare(
        "INSERT INTO admin_notifications (type, message, ward_id, created_at)
         VALUES ('cannot_collect', :message, :ward_id, NOW())"
    );
    $adminStmt->execute([
        ':message' => $adminMessage,
        ':ward_id' => $ward,
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'affected_count' => $affectedCount,
        'reschedule_date' => $rescheduleDate,
        'message' => $affectedCount . ' requests rescheduled to ' . $rescheduleDate,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('notify_cannot_collect: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
