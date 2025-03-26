<?php
require_once 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer le rôle de l'utilisateur
$stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user_data) {
    $_SESSION['role'] = $user_data['role'];
}

$is_premium = isset($_SESSION['role']) && (trim($_SESSION['role']) === 'premium' || trim($_SESSION['role']) === 'admin');

// 🔹 Compter le nombre total de likes pour savoir si on affiche le bouton
$totalLikesStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE id_cible = :user_id");
$totalLikesStmt->execute(['user_id' => $user_id]);
$totalLikes = $totalLikesStmt->fetchColumn();

// 🔹 Récupérer les premiers likes (limite 5)
$limit = 5;
$stmt = $pdo->prepare("
    SELECT u.id_utilisateur, u.prenom, u.date_naissance, u.image_profil, l.date_like
    FROM likes l
    JOIN utilisateurs u ON l.id_utilisateur = u.id_utilisateur
    WHERE l.id_cible = :user_id
    ORDER BY l.date_like DESC
    LIMIT :limit
");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Organiser les likes par jour
$likes_par_jour = [];
foreach ($likes as $like) {
    $jour = date('Y-m-d', strtotime($like['date_like']));
    
    if ($jour == date('Y-m-d')) {
        $jour_label = "Aujourd'hui";
    } elseif ($jour == date('Y-m-d', strtotime('-1 day'))) {
        $jour_label = "Hier";
    } else {
        $jour_label = date('d/m/Y', strtotime($jour));
    }

    $age = date_diff(date_create($like['date_naissance']), date_create('today'))->y;

    $likes_par_jour[$jour_label][] = [
        'id_utilisateur' => $like['id_utilisateur'],
        'prenom' => $like['prenom'],
        'age' => $age,
        'image_profil' => $like['image_profil'],
        'jour' => $jour_label,
        'heure' => date('H:i', strtotime($like['date_like']))
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Likes</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">

    <!-- Styles personnalisés -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #001F54;
            background-color: #F9F9F9;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content {
            flex: 1;
            padding-bottom: 60px;
        }
        .likes-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #FFF;
            border: 3px solid #F8A5C2;
            border-radius: 15px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
        }
        .like-card {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #FFF5F7;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
            transition: transform 0.2s ease-in-out;
            text-decoration: none;
        }
        .like-card:hover {
            transform: scale(1.02);
        }
        .like-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid #F8A5C2;
            object-fit: cover;
            margin-right: 15px;
        }
        .like-info {
            flex-grow: 1;
            font-size: 1.2em;
            font-weight: bold;
            color: #001F54;
        }
        .like-age {
            font-size: 1.1em;
            color: #666;
            font-weight: normal;
        }
        .like-time {
            display: block;
            font-size: 0.85em;
            color: #666;
        }
        .btn-primary {
            background-color: #F8A5C2;
            border: none;
            font-size: 1.1em;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease-in-out;
            display: block;
            margin: 20px auto;
        }
        .btn-primary:hover {
            background-color: #ff7fa3;
            transform: scale(1.05);
        }
        
                .blurred {
            filter: blur(5px);
            pointer-events: none;
            user-select: none;
            opacity: 0.6;
        }

    </style>
</head>
<body>

<?php include("nav.php"); ?>

<div class="content">
    <div class="container">
        <div class="likes-container">
            <h2 class="text-center"><i class="fas fa-heart"></i> Qui t'a liké ?</h2>
            <hr>

            <div id="likes-container">
                <?php foreach ($likes_par_jour as $jour_label => $likes): ?>
                    <?php foreach ($likes as $like): ?>
                        <a href="detailprofile.php?id=<?= htmlspecialchars($like['id_utilisateur']) ?>" class="like-card <?= $is_premium ? '' : 'blurred' ?>">
                            <img class="like-image" src="<?= htmlspecialchars($like['image_profil']) ?>" alt="Profil">
                            <span class="like-info">
                                <?= htmlspecialchars($like['prenom']) ?> 
                                <span class="like-age">(<?= $like['age'] ?> ans)</span>
                                <small class="like-time"><?= $like['jour'] ?> à <?= $like['heure'] ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($totalLikes > 5): ?>
                <button id="loadMoreLikes" class="btn btn-primary">Afficher plus</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let offset = 5;
    let loadMoreButton = document.getElementById("loadMoreLikes");

    if (loadMoreButton) {
        loadMoreButton.addEventListener("click", function () {
            fetch("load_more_likes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "offset=" + offset
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "empty") {
                    loadMoreButton.style.display = "none";
                } else {
                    document.getElementById("likes-container").innerHTML += data;
                    offset += 5;
                }
            })
            .catch(error => console.error("Erreur :", error));
        });
    }
});
</script>

</body>
</html>

