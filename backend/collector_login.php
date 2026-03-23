<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "db.php";

$collector_id = trim($_POST['collectorId'] ?? '');
$password     = trim($_POST['password']    ?? '');
$ward         = trim($_POST['ward']        ?? '');

if (!$collector_id || !$password || !$ward) {
    echo "<script>alert('Please fill in all fields.');window.location.href='../collector-login.html';</script>";
    exit();
}

try {

    $stmt = $pdo->prepare(
        "SELECT * FROM collectors
         WHERE email = :cid
         AND ward_assigned = :ward
         LIMIT 1"
    );
    $stmt->execute([
        ':cid'  => $collector_id,
        ':ward' => $ward,
    ]);
    $collector = $stmt->fetch(PDO::FETCH_ASSOC);
    $password_matches = false;

    if ($collector) {
        $stored_password = (string) ($collector['password'] ?? '');
        $password_matches = password_verify($password, $stored_password);

        // Support older collector records that still store plain-text passwords.
        if (!$password_matches && hash_equals($stored_password, $password)) {
            $password_matches = true;

            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $upgrade = $pdo->prepare("UPDATE collectors SET password = :password WHERE id = :id");
            $upgrade->execute([
                ':password' => $new_hash,
                ':id' => $collector['id'],
            ]);
        }
    }

    if ($collector && $password_matches) {

        // ── Login success ──────────────────────────────
        session_regenerate_id(true);
        $_SESSION['collector_id']    = $collector['id'];
        $_SESSION['collector_name']  = $collector['name'];
        $_SESSION['collector_email'] = $collector['email'];
        $_SESSION['collector_ward']  = $collector['ward_assigned'];

        echo "<script>
            localStorage.setItem('collectorLoggedIn', 'true');
            localStorage.setItem('collectorWard', " . json_encode((string) $collector['ward_assigned']) . ");
            localStorage.setItem('collectorId', " . json_encode((string) $collector['email']) . ");
            window.location.href='../collector-dashboard.php';
        </script>";
        exit();

    } else {

        // ── Debug: show exactly why it failed ─────────
        if (!$collector) {
            $msg = "No record found for email='{$collector_id}' and ward_assigned='{$ward}'";
        } else {
            $stored = $collector['password'];
            $msg = "Record found but password did not match. Hash starts with: " . substr($stored, 0, 10);
        }
        echo "<script>alert('" . addslashes($msg) . "');window.location.href='../collector-login.html';</script>";
        exit();
    }

} catch (PDOException $e) {
    $err = addslashes($e->getMessage());
    echo "<script>alert('Database error: $err');window.location.href='../collector-login.html';</script>";
    exit();
}
?>
