<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode([]); exit(); }
try {
    $stmt = $pdo->query("
        SELECT
            ward_id,
            COUNT(*) as total,
            SUM(status='completed') as completed,
            SUM(status NOT IN ('completed')) as pending,
            SUM(status NOT IN ('completed') AND request_time < NOW() - INTERVAL 15 DAY) as overdue
        FROM requests
        WHERE ward_id IS NOT NULL
        GROUP BY ward_id
        ORDER BY ward_id ASC
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e){ echo json_encode([]); }
?>
