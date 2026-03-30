<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 0) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];
$message = "";
$messageType = "";

/*
|--------------------------------------------------------------------------
| 1. Vérifier si l'utilisateur a déjà une réservation active
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("SELECT * FROM reservation WHERE user_id = ? AND status = 'active'");
$stmt->execute([$userId]);
$activeReservation = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 2. Vérifier si l'utilisateur est déjà en liste d'attente
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("SELECT * FROM waiting_list WHERE user_id = ?");
$stmt->execute([$userId]);
$waitingEntry = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 3. Traitement de la demande de réservation
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($activeReservation) {
        $message = "Vous avez déjà une réservation active.";
        $messageType = "error";
    } elseif ($waitingEntry) {
        $message = "Vous êtes déjà en liste d'attente à la position " . $waitingEntry["position_wait"] . ".";
        $messageType = "error";
    } else {
        try {
            $pdo->beginTransaction();

            /*
            ------------------------------------------------------------------
            | Chercher une place disponible
            ------------------------------------------------------------------
            */
            $stmt = $pdo->prepare("SELECT * FROM parking_spot WHERE is_available = 1 ORDER BY RAND() LIMIT 1");
            $stmt->execute();
            $spot = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($spot) {
                /*
                --------------------------------------------------------------
                | Une place est disponible : on crée la réservation
                --------------------------------------------------------------
                */
                $parkingSpotId = $spot["id_park"];
                $startDate = date("Y-m-d H:i:s");

                // durée par défaut = 1 jour
                $endDate = date("Y-m-d H:i:s", strtotime("+1 day"));

                $stmt = $pdo->prepare("
                    INSERT INTO reservation (user_id, parking_spot_id, status, start_date, actual_end_date)
                    VALUES (?, ?, 'active', ?, ?)
                ");
                $stmt->execute([$userId, $parkingSpotId, $startDate, $endDate]);

                $stmt = $pdo->prepare("UPDATE parking_spot SET is_available = 0 WHERE id_park = ?");
                $stmt->execute([$parkingSpotId]);

                $pdo->commit();

                $message = "Votre réservation a bien été enregistrée. La place numéro " . $spot["number_parking_spot"] . " vous a été attribuée.";
                $messageType = "success";

                // On recharge la réservation active pour affichage à jour
                $stmt = $pdo->prepare("
                    SELECT r.*, p.number_parking_spot
                    FROM reservation r
                    JOIN parking_spot p ON r.parking_spot_id = p.id_park
                    WHERE r.user_id = ? AND r.status = 'active'
                ");
                $stmt->execute([$userId]);
                $activeReservation = $stmt->fetch(PDO::FETCH_ASSOC);

            } else {
                /*
                --------------------------------------------------------------
                | Aucune place disponible : ajout en liste d'attente
                --------------------------------------------------------------
                */
                $stmt = $pdo->query("SELECT COALESCE(MAX(position_wait), 0) + 1 AS next_position FROM waiting_list");
                $nextPosition = $stmt->fetch(PDO::FETCH_ASSOC)["next_position"];

                $stmt = $pdo->prepare("
                    INSERT INTO waiting_list (user_id, position_wait, request_date)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$userId, $nextPosition]);

                $pdo->commit();

                $message = "Aucune place n'est disponible actuellement. Vous avez été ajouté à la liste d'attente en position " . $nextPosition . ".";
                $messageType = "success";

                // On recharge l'entrée d'attente pour affichage à jour
                $stmt = $pdo->prepare("SELECT * FROM waiting_list WHERE user_id = ?");
                $stmt->execute([$userId]);
                $waitingEntry = $stmt->fetch(PDO::FETCH_ASSOC);
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Une erreur est survenue lors de la réservation : " . $e->getMessage();
            $messageType = "error";
        }
    }
}

/*
|--------------------------------------------------------------------------
| 4. Recharger les infos pour affichage
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT r.*, p.number_parking_spot
    FROM reservation r
    JOIN parking_spot p ON r.parking_spot_id = p.id_park
    WHERE r.user_id = ? AND r.status = 'active'
");
$stmt->execute([$userId]);
$activeReservation = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM waiting_list WHERE user_id = ?");
$stmt->execute([$userId]);
$waitingEntry = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réservation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <a href="dashboard_user.php">Accueil</a>
    <a href="reservation.php">Réserver une place</a>
    <a href="logout.php">Déconnexion</a>
</div>

<div class="container">

    <div class="card">
        <h2>Demande de réservation</h2>
        <p>
            Cette page vous permet de demander une place de parking.
            Si une place est disponible, elle vous sera attribuée immédiatement.
            Sinon, vous serez ajouté à la liste d'attente.
        </p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Ma situation actuelle</h3>

        <?php if ($activeReservation): ?>
            <p><strong>Réservation active :</strong> oui</p>
            <p><strong>Numéro de place :</strong> <?= htmlspecialchars($activeReservation["number_parking_spot"]) ?></p>
            <p><strong>Date de début :</strong> <?= htmlspecialchars($activeReservation["start_date"]) ?></p>
            <p><strong>Date de fin prévue :</strong> <?= htmlspecialchars($activeReservation["actual_end_date"]) ?></p>
            <p><strong>Statut :</strong> <?= htmlspecialchars($activeReservation["status"]) ?></p>

        <?php elseif ($waitingEntry): ?>
            <p><strong>Réservation active :</strong> non</p>
            <p><strong>Liste d'attente :</strong> oui</p>
            <p><strong>Votre position :</strong> <?= htmlspecialchars($waitingEntry["position_wait"]) ?></p>
            <p><strong>Date de demande :</strong> <?= htmlspecialchars($waitingEntry["request_date"]) ?></p>

        <?php else: ?>
            <p>Vous n'avez actuellement ni réservation active, ni place en liste d'attente.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Faire une demande</h3>

        <?php if ($activeReservation): ?>
            <p>Vous ne pouvez pas faire de nouvelle demande car vous avez déjà une réservation active.</p>

        <?php elseif ($waitingEntry): ?>
            <p>Vous ne pouvez pas faire de nouvelle demande car vous êtes déjà en liste d'attente.</p>

        <?php else: ?>
            <form method="POST">
                <button type="submit" class="btn">Demander une place</button>
            </form>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
