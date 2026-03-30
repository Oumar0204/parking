<?php
require_once 'config.php';

$username = "admin";
$password = password_hash("Admin1234!", PASSWORD_DEFAULT);
$is_admin = 1;

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin, is_active) VALUES (?, ?, ?, 1)");
$stmt->execute([$username, $password, $is_admin]);

echo "Admin créé";
?>
