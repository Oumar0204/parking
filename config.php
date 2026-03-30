<?php
$host = "localhost";
$dbname = "parking";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=parking", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
