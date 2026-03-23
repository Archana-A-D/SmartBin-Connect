<?php

session_start();
require_once "db.php";

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header("Location: ../admin-login.php?error=1");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $storedPassword = (string)($admin['password'] ?? '');
        $passwordMatches = false;

        if ($storedPassword !== '') {
            $passwordMatches = password_verify($password, $storedPassword);

            if (!$passwordMatches && hash_equals($storedPassword, $password)) {
                $passwordMatches = true;

                if (strlen($storedPassword) < 60) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE admins SET password = :password WHERE id = :id");
                    $update->execute([
                        ':password' => $newHash,
                        ':id' => $admin['id'],
                    ]);
                }
            }
        }

        if ($passwordMatches) {
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: ../admin-dashboard.php");
            exit();
        }
    }

    header("Location: ../admin-login.php?error=1");
    exit();
} catch (Exception $e) {
    header("Location: ../admin-login.php?error=1");
    exit();
}

?>
