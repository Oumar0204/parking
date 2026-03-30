<?php
require_once 'config.php';

$username = "oumar";
$password = password_hash("Oumar1234!", PASSWORD_DEFAULT);
$is_admin = 0;

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin, is_active) VALUES (?, ?, ?, 1)");
$stmt->execute([$username, $password, $is_admin]);

echo "Utilisateur créé";
?>
