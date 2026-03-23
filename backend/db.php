<?php

function env_value(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    if ($value === false && array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    }
    if ($value === false || $value === null) {
        return $default;
    }
    $value = trim((string)$value);
    return $value === '' ? $default : $value;
}

$host = env_value("SMARTBIN_DB_HOST", "localhost");
$user = env_value("SMARTBIN_DB_USER", "root");
$password = env_value("SMARTBIN_DB_PASSWORD", "");
$database = env_value("SMARTBIN_DB_NAME", "smartbin");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>
