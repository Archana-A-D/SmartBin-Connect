<?php
// Run this script ONCE to add a test collector with a hashed password to your collectors table.
// Then delete or rename this file for security.

require_once 'db.php';

$collector_id = 'COL101';
$password_plain = '1234';
$ward = '1';

$hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO collectors (collector_id, password, ward) VALUES (:cid, :pw, :ward)");
    $stmt->execute([
        ':cid' => $collector_id,
        ':pw' => $hashed_password,
        ':ward' => $ward
    ]);
    echo "Test collector added!<br>Collector ID: $collector_id<br>Password: $password_plain<br>Ward: $ward";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
