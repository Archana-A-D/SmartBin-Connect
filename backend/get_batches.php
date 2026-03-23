<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['error' => 'Unauthorised']);
    exit();
}

$ward = $_SESSION['collector_ward'];

try {
    // Get all batches for this ward with request counts
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            COUNT(r.id) as total,
            SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN r.status IN ('requested','accepted') THEN 1 ELSE 0 END) as active_count
        FROM batches b
        LEFT JOIN requests r ON r.batch_id = b.id
        WHERE b.ward_id = :ward
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([':ward' => $ward]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($batches);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>