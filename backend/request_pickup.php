<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user_id'])){
    die("User not logged in");
}

$user_id  = $_SESSION['user_id'];
$phone    = $_POST['phone']    ?? '';
$address  = $_POST['address']  ?? '';
$ward     = $_POST['ward']     ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude= $_POST['longitude']?? null;
$waste    = implode(",", $_POST['waste'] ?? []);

try {
    $stmt = $pdo->prepare("
        INSERT INTO requests
        (user_id, ward_id, waste_type, phone, address, latitude, longitude, pickup_date, status, request_time)
        VALUES
        (:uid, :ward, :waste, :phone, :address, :lat, :lng, NULL, 'requested', NOW())
    ");
    $stmt->execute([
        ':uid'   => $user_id,
        ':ward'  => $ward,
        ':waste' => $waste,
        ':phone' => $phone,
        ':address'=> $address,
        ':lat'   => $latitude ?: null,
        ':lng'   => $longitude?: null,
    ]);

    // Check if ward queue has reached 15 — notify collector
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM requests
        WHERE ward_id = :ward
        AND (batch_id IS NULL OR batch_id = 0)
        AND status NOT IN ('completed')
    ");
    $countStmt->execute([':ward' => $ward]);
    $queueCount = (int)$countStmt->fetchColumn();

    if ($queueCount >= 15) {
        $colStmt = $pdo->prepare("
            SELECT id FROM collectors
            WHERE ward_assigned = :ward LIMIT 1
        ");
        $colStmt->execute([':ward' => $ward]);
        $collector = $colStmt->fetch(PDO::FETCH_ASSOC);

        if ($collector) {
            $dupCheck = $pdo->prepare("
                SELECT COUNT(*) FROM collector_notifications
                WHERE collector_id = :cid
                AND type = 'queue_ready'
                AND created_at > NOW() - INTERVAL 1 HOUR
            ");
            $dupCheck->execute([':cid' => $collector['id']]);
            $alreadyNotified = (int)$dupCheck->fetchColumn();

            if ($alreadyNotified === 0) {
                $msg = "Your queue for Ward {$ward} now has {$queueCount} requests waiting. You can now create a new batch and schedule a pickup date.";
                $pdo->prepare("
                    INSERT INTO collector_notifications
                    (collector_id, ward_id, type, message, is_read, created_at)
                    VALUES (:cid, :ward, 'queue_ready', :msg, 0, NOW())
                ")->execute([':cid'=>$collector['id'],':ward'=>$ward,':msg'=>$msg]);
            }
        }
    }

    // Return JSON so JS can handle success cleanly
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Pickup request error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
}
?>