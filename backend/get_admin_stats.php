<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['admin_id'])){ echo json_encode([]); exit(); }

try {
    $total     = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
    $completed = $pdo->query("SELECT COUNT(*) FROM requests WHERE status='completed'")->fetchColumn();
    $pending   = $pdo->query("SELECT COUNT(*) FROM requests WHERE status NOT IN ('completed')")->fetchColumn();
    $overdue   = $pdo->query("
        SELECT COUNT(*) FROM requests
        WHERE status NOT IN ('completed')
        AND request_time < NOW() - INTERVAL 15 DAY
    ")->fetchColumn();
    $collectors = $pdo->query("SELECT COUNT(*) FROM collectors")->fetchColumn();
    $users      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    echo json_encode([
        'total'      => (int)$total,
        'completed'  => (int)$completed,
        'pending'    => (int)$pending,
        'overdue'    => (int)$overdue,
        'collectors' => (int)$collectors,
        'users'      => (int)$users,
    ]);
} catch(Exception $e){
    echo json_encode(['error' => $e->getMessage()]);
}
?>