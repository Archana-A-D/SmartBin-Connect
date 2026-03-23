<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode([]);
    exit();
}

$ward = $_SESSION['collector_ward'] ?? null;

if ($ward === null || $ward === '') {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM requests
        WHERE ward_id = :ward AND status = 'requested'
        ORDER BY request_time ASC
    ");
    $stmt->execute([':ward' => $ward]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>
