<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT * FROM collector_notifications
        WHERE collector_id = :cid
        ORDER BY created_at DESC
        LIMIT 30
    ");
    $stmt->execute([':cid' => $_SESSION['collector_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>