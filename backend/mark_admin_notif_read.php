<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode(['success'=>false]); exit(); }
try {
    if(!empty($_POST['all'])){
        $pdo->query("UPDATE admin_notifications SET is_read=1");
    } else {
        $id=(int)($_POST['id']??0);
        if($id) $pdo->prepare("UPDATE admin_notifications SET is_read=1 WHERE id=:id")->execute([':id'=>$id]);
    }
    echo json_encode(['success'=>true]);
} catch(Exception $e){ echo json_encode(['success'=>false]); }
?>