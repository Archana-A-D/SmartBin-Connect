<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])){ echo json_encode([]); exit(); }

$status    = $_GET['status']    ?? 'all';
$ward      = $_GET['ward']      ?? 'all';
$search    = $_GET['search']    ?? '';
$overdue   = $_GET['overdue']   ?? '0';
$limit     = min((int)($_GET['limit'] ?? 100), 500);

try {
    $where  = [];
    $params = [];

    if($status !== 'all'){
        $where[] = 'r.status = :status';
        $params[':status'] = $status;
    }
    if($ward !== 'all'){
        $where[] = 'r.ward_id = :ward';
        $params[':ward'] = $ward;
    }
    if($search !== ''){
        $where[] = '(r.address LIKE :s OR r.waste_type LIKE :s2)';
        $params[':s']  = "%$search%";
        $params[':s2'] = "%$search%";
    }
    if($overdue === '1'){
        $where[] = "r.status NOT IN ('completed') AND r.request_time < NOW() - INTERVAL 15 DAY";
    }

    $sql = "
        SELECT
            r.id, r.ward_id, r.address, r.waste_type, r.status,
            r.request_time, r.pickup_date, r.latitude, r.longitude,
            r.alert_sent, r.batch_id,
            u.name  AS user_name,
            u.phone AS user_phone,
            TIMESTAMPDIFF(DAY, r.request_time, NOW()) AS days_old
        FROM requests r
        LEFT JOIN users u ON u.id = r.user_id
        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
        ORDER BY r.request_time DESC
        LIMIT $limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e){
    echo json_encode(['error' => $e->getMessage()]);
}
?>