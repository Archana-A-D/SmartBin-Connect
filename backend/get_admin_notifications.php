<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode([]); exit(); }
try {
    $stmt = $pdo->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 50");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e){ echo json_encode([]); }
?>