<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');

// Works for both collector login and user login
// Collector accesses all ward requests, user sees only their own

$is_collector = isset($_SESSION['collector_id']);
$is_user      = isset($_SESSION['user_id']);

if(!$is_collector && !$is_user){
    echo json_encode([]);
    exit();
}

try {
    if($is_collector){
        // Collector sees ALL requests (filtered by ward in JS)
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.user_id,
                r.ward_id,
                r.latitude,
                r.longitude,
                r.status,
                r.request_time,
                r.phone,
                r.address,
                r.waste_type,
                r.pickup_date,
                r.unavailable,
                r.rescheduled_date,
                r.route_order,
                r.alert_sent
            FROM requests r
            ORDER BY r.request_time DESC
        ");
        $stmt->execute();
    } else {
        // User sees only their own requests
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.user_id,
                r.ward_id,
                r.latitude,
                r.longitude,
                r.status,
                r.request_time,
                r.phone,
                r.address,
                r.waste_type,
                r.pickup_date,
                r.unavailable,
                r.rescheduled_date,
                r.route_order,
                r.alert_sent
            FROM requests r
            WHERE r.user_id = :uid
            ORDER BY r.request_time DESC
        ");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
