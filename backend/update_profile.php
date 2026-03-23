<?php
/*
 * backend/update_profile.php
 * Updates user profile: name, email, phone, optional password
 * POST: name, email, phone, password (optional)
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];

$name     = trim($_POST['name']     ?? '');
$email    = trim($_POST['email']    ?? '');
$phone    = trim($_POST['phone']    ?? '');

$address = trim($_POST['address'] ?? '');
$password = trim($_POST['password'] ?? '');

// Basic validation
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name is required.']);
    exit();
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'A valid email is required.']);
    exit();
}

try {
    // Check email uniqueness (excluding current user)
    $dup = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :uid");
    $dup->execute([':email' => $email, ':uid' => $user_id]);
    if ($dup->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This email is already in use.']);
        exit();
    }

    if (!empty($password)) {
        // Password provided — hash and update
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit();
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "UPDATE users SET name=:name, email=:email, phone=:phone, address=:address, password=:pw WHERE id=:uid"
        );
        $stmt->execute([':name'=>$name,':email'=>$email,':phone'=>$phone,':address'=>$address,':pw'=>$hash,':uid'=>$user_id]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE users SET name=:name, email=:email, phone=:phone, address=:address WHERE id=:uid"
        );
        $stmt->execute([':name'=>$name,':email'=>$email,':phone'=>$phone,':address'=>$address,':uid'=>$user_id]);
    }

    // Refresh session values
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_address'] = $address;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}