<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

// Only collectors can accept
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
    $stmt = $pdo->prepare("
        UPDATE requests 
        SET status = 'accepted' 
        WHERE id = :id
          AND ward_id = :ward
          AND status = 'requested'
    ");
    $stmt->execute([':id' => $id, ':ward' => $collectorWard]);

    if($stmt->rowCount() > 0){
        echo json_encode(['success' => true, 'message' => 'Request accepted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or already accepted']);
    }

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
