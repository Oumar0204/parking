<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 0) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

// Réservation actuelle
$stmt = $pdo->prepare("
    SELECT r.*, p.number_parking_spot
    FROM reservation r
    JOIN parking_spot p ON r.parking_spot_id = p.id_park
    WHERE r.user_id = ? AND r.status = 'active'
");
$stmt->execute([$userId]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

// Position en attente
$stmt2 = $pdo->prepare("SELECT * FROM waiting_list WHERE user_id = ?");
$stmt2->execute([$userId]);
$waiting = $stmt2->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace utilisateur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <a href="dashboard_user.php">Accueil</a>
    <a href="logout.php">Déconnexion</a>
</div>

<div class="container">
    <div class="card">
        <h2>Bienvenue <?= htmlspecialchars($_SESSION["username"]) ?></h2>
    </div>

    <div class="card">
        <h3>Ma réservation actuelle</h3>
        <?php if ($reservation): ?>
            <p><strong>Place attribuée :</strong> <?= $reservation["number_parking_spot"] ?></p>
            <p><strong>Date de début :</strong> <?= $reservation["start_date"] ?></p>
            <p><strong>Statut :</strong> <?= $reservation["status"] ?></p>
        <?php else: ?>
            <p>Aucune réservation active.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Ma position en liste d'attente</h3>
        <?php if ($waiting): ?>
            <p>Vous êtes en position <strong><?= $waiting["position_wait"] ?></strong>.</p>
        <?php else: ?>
            <p>Vous n'êtes pas en liste d'attente.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
