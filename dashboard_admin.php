<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 1) {
    header("Location: admin_login.php");
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$spots = $pdo->query("SELECT * FROM parking_spot ORDER BY number_parking_spot ASC")->fetchAll(PDO::FETCH_ASSOC);
$waitingList = $pdo->query("
    SELECT w.*, u.username
    FROM waiting_list w
    JOIN users u ON w.user_id = u.id_user
    ORDER BY w.position_wait ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace administrateur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <a href="dashboard_admin.php">Accueil admin</a>
    <a href="reservation.php">Réserver une place</a>
    <a href="logout.php">Déconnexion</a>
</div>

<div class="container">
    <div class="card">
        <h2>Bienvenue administrateur <?= htmlspecialchars($_SESSION["username"]) ?></h2>
    </div>

    <div class="card">
        <h3>Liste des utilisateurs</h3>
        <table class="table">
            <tr>
                <th>ID</th>
                <th>Nom d'utilisateur</th>
                <th>Admin</th>
                <th>Actif</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user["id_user"] ?></td>
                    <td><?= htmlspecialchars($user["username"]) ?></td>
                    <td><?= $user["is_admin"] ? 'Oui' : 'Non' ?></td>
                    <td><?= $user["is_active"] ? 'Oui' : 'Non' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h3>Liste des places</h3>
        <table class="table">
            <tr>
                <th>ID</th>
                <th>Numéro</th>
                <th>Disponible</th>
            </tr>
            <?php foreach ($spots as $spot): ?>
                <tr>
                    <td><?= $spot["id_park"] ?></td>
                    <td><?= $spot["number_parking_spot"] ?></td>
                    <td><?= $spot["is_available"] ? 'Oui' : 'Non' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h3>Liste d'attente</h3>
        <table class="table">
            <tr>
                <th>Position</th>
                <th>Utilisateur</th>
                <th>Date de demande</th>
            </tr>
            <?php foreach ($waitingList as $waiting): ?>
                <tr>
                    <td><?= $waiting["position_wait"] ?></td>
                    <td><?= htmlspecialchars($waiting["username"]) ?></td>
                    <td><?= $waiting["request_date"] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

</body>
</html>
