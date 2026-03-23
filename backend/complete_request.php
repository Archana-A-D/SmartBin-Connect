<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Only collectors can complete
if(!isset($_SESSION['collector_id'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit();
}

$collectorWard = (int)($_SESSION['collector_ward'] ?? 0);

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if(!$id){
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

try {
    // Get route_order of the request being completed
    $stmt = $pdo->prepare("
        SELECT route_order, ward_id 
        FROM requests 
        WHERE id = :id
          AND ward_id = :ward
    ");
    $stmt->execute([':id' => $id, ':ward' => $collectorWard]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$row){
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }

    $current_order = $row['route_order'];
    $ward_id       = $row['ward_id'];
    $next_order    = $current_order + 1;

    // Mark current request as completed
    $stmt = $pdo->prepare("
        UPDATE requests 
        SET status = 'completed' 
        WHERE id = :id
          AND ward_id = :ward
          AND status = 'accepted'
    ");
    $stmt->execute([':id' => $id, ':ward' => $collectorWard]);

    if($stmt->rowCount() === 0){
        echo json_encode(['success' => false, 'message' => 'Request not found or not in accepted state']);
        exit();
    }

    // Alert next house in route (same ward)
    if($current_order !== null){
        $stmt = $pdo->prepare("
            UPDATE requests 
            SET alert_sent = 1 
            WHERE route_order = :next_order 
            AND ward_id = :ward_id
            AND status != 'completed'
        ");
        $stmt->execute([
            ':next_order' => $next_order,
            ':ward_id'    => $ward_id
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Waste collection completed'
    ]);

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
