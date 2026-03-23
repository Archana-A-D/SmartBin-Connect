<?php
require_once "db.php";

$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$ward = $_POST['ward'];
$phone = $_POST['phone'];
$address = $_POST['address'] ?? '';

try {
	$stmt = $pdo->prepare("INSERT INTO users (name, email, password, ward, phone, address) VALUES (:name, :email, :password, :ward, :phone, :address)");
	$stmt->execute([
		':name' => $name,
		':email' => $email,
		':password' => $password,
		':ward' => $ward,
		':phone' => $phone,
		':address' => $address
	]);
	echo "<script>alert('Registration Successful');window.location.href='../login.html';</script>";
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}

?>
