<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

$ward = (int)($_POST['ward'] ?? 0);
if (!$ward) {
    echo json_encode(['success' => false, 'message' => 'Ward required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt
        FROM requests
        WHERE ward_id = :ward
          AND status NOT IN ('completed')
          AND request_time < NOW() - INTERVAL 15 DAY
    ");
    $stmt->execute([':ward' => $ward]);
    $cnt = (int)$stmt->fetchColumn();

    if ($cnt === 0) {
        echo json_encode(['success' => false, 'message' => 'No overdue requests for this ward']);
        exit();
    }

    $colStmt = $pdo->prepare("
        SELECT id, name
        FROM collectors
        WHERE ward_assigned = :ward
        LIMIT 1
    ");
    $colStmt->execute([':ward' => $ward]);
    $collector = $colStmt->fetch(PDO::FETCH_ASSOC);

    if (!$collector) {
        echo json_encode(['success' => false, 'message' => 'No collector found for Ward ' . $ward]);
        exit();
    }

    $msg = "URGENT: There are {$cnt} overdue pickup request(s) in Ward {$ward} that have been pending for more than 15 days. Please complete these pickups as soon as possible. - SmartWaste Admin";

    $notifStmt = $pdo->prepare("
        INSERT INTO collector_notifications (collector_id, ward_id, type, message, is_read, created_at)
        VALUES (:cid, :ward, 'overdue_alert', :msg, 0, NOW())
    ");
    $notifStmt->execute([
        ':cid' => $collector['id'],
        ':ward' => $ward,
        ':msg' => $msg,
    ]);

    $pdo->prepare("
        INSERT INTO admin_notifications (type, message, ward_id, is_read, created_at)
        VALUES ('overdue_sent', :msg, :ward, 0, NOW())
    ")->execute([
        ':msg' => "Overdue alert sent to collector {$collector['name']} for Ward {$ward} ({$cnt} requests).",
        ':ward' => $ward,
    ]);

    echo json_encode([
        'success' => true,
        'overdue_count' => $cnt,
        'collector_name' => $collector['name'],
        'message' => "Alert sent to {$collector['name']} for Ward {$ward}",
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
