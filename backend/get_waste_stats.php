<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
if(!isset($_SESSION['admin_id'])){ echo json_encode([]); exit(); }
try {
    $stmt = $pdo->query("
        SELECT waste_type
        FROM requests
        WHERE waste_type IS NOT NULL AND waste_type <> ''
    ");

    $counts = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $wasteTypes) {
        foreach (explode(',', $wasteTypes) as $type) {
            $label = trim($type);
            if ($label === '') {
                continue;
            }
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
    }

    arsort($counts);

    $data = [];
    foreach ($counts as $wasteType => $count) {
        $data[] = [
            'waste_type' => $wasteType,
            'count' => $count,
        ];
    }

    echo json_encode($data);
} catch(Exception $e){ echo json_encode([]); }
?>
