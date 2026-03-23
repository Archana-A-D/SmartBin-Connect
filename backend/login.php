<?php

session_start();
require_once "db.php";

$email = $_POST['email'];
$password = $_POST['password'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_ward'] = $user['ward'];
            header("Location: ../user-dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid password');window.location.href='../login.html';</script>";
        }
    } else {
        echo "<script>alert('Email not found');window.location.href='../login.html';</script>";
    }
} catch (Exception $e) {
    echo "<script>alert('Login error');window.location.href='../login.html';</script>";
}

?>