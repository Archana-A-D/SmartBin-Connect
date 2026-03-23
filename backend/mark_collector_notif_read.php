<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['collector_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$cid = $_SESSION['collector_id'];

try {
    if (!empty($_POST['all'])) {
        $pdo->prepare("UPDATE collector_notifications SET is_read=1 WHERE collector_id=:cid")->execute([':cid'=>$cid]);
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $pdo->prepare("UPDATE collector_notifications SET is_read=1 WHERE id=:id AND collector_id=:cid")->execute([':id'=>$id,':cid'=>$cid]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
?>