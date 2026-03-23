<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode([]); exit(); }
try {
    $stmt = $pdo->query("
        SELECT ward_id, COUNT(*) as count
        FROM requests
        WHERE ward_id IS NOT NULL
        GROUP BY ward_id
        ORDER BY ward_id ASC
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e){ echo json_encode([]); }
?>
