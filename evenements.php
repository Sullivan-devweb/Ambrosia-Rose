<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session et connexion DB
session_start();
require_once("db_connect.php");

// Vérifier si l'utilisateur est connecté
$user_id = $_SESSION['user_id'] ?? null;

// ✅ Récupérer tous les événements à venir avec le nombre de participants
$stmt = $pdo->query("
    SELECT e.id, e.titre, e.description, e.date_event, e.heure, e.prix,
           (SELECT COUNT(*) FROM participants p WHERE p.event_id = e.id) AS nombre_participants
    FROM evenements e 
    WHERE e.date_event >= CURDATE() 
    ORDER BY e.date_event ASC
");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Message de confirmation de paiement
$message = $_GET['message'] ?? null;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
<style>
    :root {
        --primary-blue: #001F54;
        --accent-pink: #F4C2C2;
        --soft-pink: #fce4ec;
        --participant-blue: #001F54;
        --text-color: #555;
    }

    html, body {
        height: 100%; /* Permet à la page d'avoir une hauteur de 100% */
        margin: 0;
        display: flex;
        flex-direction: column;
    }

    body {
        font-family: 'Poppins', sans-serif;
        color: var(--primary-blue);
        background: #f8f9fa;
        flex: 1;
    }

    .container-fluid {
        padding: 40px 5%;
        flex: 1; /* Cela garantit que le container prend tout l'espace disponible */
    }

    .event-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .event-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: center; /* Centrer le titre */
    }

    .event-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-blue);
        text-align: center; /* Centrer le titre de l'événement */
    }

    .event-price {
        background: var(--accent-pink);
        color: var(--primary-blue);
        padding: 8px 14px;
        border-radius: 15px;
        font-size: 1.1rem;
        font-weight: bold;
    }

    .event-description {
        text-align: center; /* Centrer le paragraphe de description */
    }

    .participants-container {
        background: var(--soft-pink);
        padding: 15px;
        border-radius: 8px;
        margin-top: 10px;
    }

    .participants {
        font-size: 1rem;
        color: var(--participant-blue);
        font-weight: bold;
    }

    .participants-list {
        list-style: none;
        padding: 0;
        margin: 10px 0;
        display: flex;
        flex-wrap: wrap;
    }

    .participants-list li {
        background: white;
        color: var(--participant-blue);
        font-weight: bold;
        padding: 6px 14px;
        margin: 5px;
        border-radius: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .register-section {
        text-align: center;
        margin-top: 20px;
    }

    .btn-register {
        background: var(--accent-pink);
        color: var(--primary-blue);
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-register:hover {
        background: var(--soft-pink);
        color: var(--primary-blue);
    }

    .message-already-registered {
        font-size: 1rem;
        color: var(--participant-blue);
        font-weight: bold;
        padding: 10px;
        text-align: center;
    }

    footer {
        margin-top: auto; /* Force le footer à rester en bas */
    }
</style>


</head>
<body>

<?php include("nav.php"); ?>

<!-- Affichage du message de confirmation -->
<?php if ($message): ?>
    <div class="alert alert-success text-center" role="alert">
        <?php 
        if ($message === 'paiement_reussi') {
            echo "Votre paiement a été effectué avec succès ! Vous êtes inscrit à cet événement.";
        } elseif ($message === 'paiement_echoue') {
            echo "Le paiement a échoué. Veuillez réessayer.";
        } elseif ($message === 'erreur') {
            echo "Une erreur est survenue lors du paiement. Veuillez contacter le support.";
        }
        ?>
    </div>
<?php endif; ?>

<div class="container-fluid">
    <h1 class="text-center mb-4"><i class="fa-solid fa-calendar-days"></i> Prochains Événements</h1>

    <?php if ($events): ?>
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="event-header">
                    <h3 class="event-title"><?= htmlspecialchars($event['titre']) ?></h3>
                    <span class="event-price"><?= number_format($event['prix'], 2) ?> €</span>
                </div>
                <p class="event-date"><i class="fa-regular fa-clock"></i> <?= date("d/m/Y", strtotime($event['date_event'])) . ' à ' . date("H:i", strtotime($event['heure'])) ?></p>
                <p class="event-description"><?= nl2br(htmlspecialchars($event['description'])) ?></p>

                <!-- ✅ Liste des participants -->
                <div class="participants-container">
                    <p class="participants"><i class="fa-solid fa-user"></i> Participants (<?= $event['nombre_participants'] ?>) :</p>
                    <ul class="participants-list">
                        <?php
                        // Récupérer les prénoms des participants
                        $stmtParticipants = $pdo->prepare("
                            SELECT u.prenom FROM participants p
                            JOIN utilisateurs u ON p.user_id = u.id_utilisateur
                            WHERE p.event_id = ?
                        ");
                        $stmtParticipants->execute([$event['id']]);
                        $participants = $stmtParticipants->fetchAll(PDO::FETCH_ASSOC);

                        if ($participants) {
                            foreach ($participants as $participant) {
                                echo "<li>" . htmlspecialchars($participant['prenom']) . "</li>";
                            }
                        } else {
                            echo "<li>Aucun inscrit pour le moment.</li>";
                        }
                        ?>
                    </ul>
                </div>

                <!-- ✅ Vérification si l'utilisateur est déjà inscrit -->
                <div class="register-section">
                    <?php
                    // Vérifier si l'utilisateur connecté est déjà inscrit
                    $stmtCheck = $pdo->prepare("SELECT id FROM participants WHERE user_id = ? AND event_id = ?");
                    $stmtCheck->execute([$user_id, $event['id']]);
                    $deja_inscrit = $stmtCheck->fetch();
                    ?>

                    <?php if ($user_id && $deja_inscrit): ?>
                        <p class="message-already-registered">✅ Vous êtes déjà inscrit à cet événement.</p>
                    <?php else: ?>
                        <a href="paiement_evenement.php?event_id=<?= $event['id'] ?>" class="btn btn-register">Réserver ma place</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center">Il n'y a pas encore d'événements prévus. Restez connectés pour les prochaines annonces !</p>
    <?php endif; ?>
</div>

<?php include("footer.php"); ?>
</body>
</html>


