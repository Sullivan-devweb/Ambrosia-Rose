<?php
// Inclure le fichier de connexion à la base de données
require_once 'db_connect.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger l'utilisateur vers la page de connexion s'il n'est pas connecté
    header("Location: connexion.php");
    exit(); // Terminer le script après la redirection
}

// Récupérer l'ID de l'utilisateur à partir de la session
$user_id = $_SESSION['user_id'];

// Marquer les visites comme vues dans la base de données
$updateVuStmt = $pdo->prepare("UPDATE visites SET vu = 1 WHERE visite_id = :user_id AND vu = 0");
$updateVuStmt->execute(['user_id' => $user_id]);

// Mettre à jour les informations de l'utilisateur en session (rôle)
$stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Si les données de l'utilisateur sont trouvées, mettre à jour le rôle dans la session
if ($user_data) {
    $_SESSION['role'] = $user_data['role'];
}

// Vérifier si l'utilisateur est premium ou admin
$is_premium = isset($_SESSION['role']) && (trim($_SESSION['role']) === 'premium' || trim($_SESSION['role']) === 'admin');

// Compter le nombre total de visites pour savoir si le bouton "Afficher plus" doit apparaître
$totalVisitesStmt = $pdo->prepare("SELECT COUNT(*) FROM visites WHERE visite_id = :user_id AND visite_id != visiteur_id");
$totalVisitesStmt->execute(['user_id' => $user_id]);
$totalVisites = $totalVisitesStmt->fetchColumn();

// Récupérer les 5 premières visites
$limit = 5;
$stmt = $pdo->prepare("
    SELECT u.id_utilisateur, u.prenom, u.date_naissance, u.image_profil, v.date_visite
    FROM visites v
    JOIN utilisateurs u ON v.visiteur_id = u.id_utilisateur
    WHERE v.visite_id = :user_id AND v.visiteur_id != :user_id
    ORDER BY v.date_visite DESC
    LIMIT :limit
");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$visites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les visites par jour
$visites_par_jour = [];
foreach ($visites as $visite) {
    $jour = date('Y-m-d', strtotime($visite['date_visite']));
    
    // Déterminer le label du jour (Aujourd'hui, Hier, ou date formatée)
    if ($jour == date('Y-m-d')) {
        $jour_label = "Aujourd'hui";
    } elseif ($jour == date('Y-m-d', strtotime('-1 day'))) {
        $jour_label = "Hier";
    } else {
        $jour_label = date('d/m/Y', strtotime($jour));
    }

    // Calculer l'âge du visiteur
    $age = date_diff(date_create($visite['date_naissance']), date_create('today'))->y;

    // Ajouter la visite au tableau organisé par jour
    $visites_par_jour[$jour_label][] = [
        'id_utilisateur' => $visite['id_utilisateur'],
        'prenom' => $visite['prenom'],
        'age' => $age,
        'image_profil' => $visite['image_profil'],
        'jour' => $jour_label,
        'heure' => date('H:i', strtotime($visite['date_visite']))
    ];
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Visites</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Styles personnalisés -->
    <style>
/* ✅ Correction du corps de la page */
body {
    font-family: 'Poppins', sans-serif;
    color: #001F54;
    background-color: #F9F9F9;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    margin: 0;
    padding: 0;
}

/* ✅ Contenu principal */
.content {
    flex: 1;
    padding-bottom: 60px;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* ✅ Conteneur principal des visites */
.visits-container {
    max-width: 600px;
    width: 100%;
    margin: 50px auto;
    padding: 30px;
    background: #FFF;
    border: 3px solid #F8A5C2;
    border-radius: 15px;
    box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden; /* ✅ Empêche les débordements */
    box-sizing: border-box; /* ✅ Assure un bon dimensionnement */
}

/* ✅ Titre des journées */
.visit-day {
    font-size: 1.5em;
    font-weight: bold;
    color: #001F54;
    margin-top: 20px;
}

/* ✅ Style des cartes de visite */
.visit-card {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #FFF5F7;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 10px;
    transition: transform 0.2s ease-in-out;
    width: 100%; /* ✅ Empêche le débordement */
    box-sizing: border-box; /* ✅ Ajuste le padding et la taille */
    overflow: hidden; /* ✅ Empêche le texte de dépasser */
}

/* ✅ Effet au survol */
.visit-card:hover {
    transform: scale(1.02);
}

/* ✅ Image de profil */
.visit-image {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 3px solid #F8A5C2;
    object-fit: cover;
    margin-right: 15px;
    flex-shrink: 0; /* ✅ Empêche l'image de se redimensionner */
}

/* ✅ Informations sur le visiteur */
.visit-info {
    flex-grow: 1;
    font-size: 1.2em;
    font-weight: bold;
    color: #001F54;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis; /* ✅ Coupe proprement le texte s'il est trop long */
}

/* ✅ Âge du visiteur */
.visit-age {
    font-size: 1.1em;
    color: #666;
    font-weight: normal;
}

/* ✅ Message si aucune visite */
.no-visit {
    text-align: center;
    font-size: 1.2em;
    color: #666;
    padding: 20px;
}

/* ✅ Floutage des visiteurs non premium */
.blurred {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
    opacity: 0.6;
}

/* ✅ Ajout de l'heure de visite */
.visit-time {
    display: block;
    font-size: 0.85em;
    color: #666;
    margin-top: 3px;
}

/* ✅ Lien autour de la carte */
.visit-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
    width: 100%; /* ✅ Assure que le lien prend toute la largeur */
}

/* ✅ Effet au survol */
.visit-card-link:hover .visit-card {
    transform: scale(1.05);
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
}

/* ✅ Bouton "Afficher plus" centré et stylisé */
#loadMore {
    display: block;         /* Prend toute la largeur dispo */
    margin: 20px auto;      /* Centre le bouton */
    padding: 10px 20px;     /* Taille du bouton */
    background-color: #F8A5C2; /* Rose */
    border: none;           /* Pas de bordure */
    color: white;           /* Texte blanc */
    font-size: 1.2em;       /* Taille du texte */
    font-weight: bold;      /* Texte en gras */
    border-radius: 25px;    /* Coins arrondis */
    transition: all 0.3s ease-in-out;
}

/* ✅ Effet au survol */
#loadMore:hover {
    background-color: #ff7fa3; /* Rose plus foncé au survol */
    transform: scale(1.05);    /* Effet zoom */
}


.visit-image {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 3px solid #F8A5C2;
    object-fit: cover;
    margin-right: 15px;
    flex-shrink: 0;
    text-align: center;
}

.disabled-card .visit-image {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
}




    </style>
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="nav.css">
</head>
<body>

    <?php include("nav.php"); ?>

<div class="content">
    <div class="container">
        <div class="visits-container">
            <h2 class="text-center"><i class="fas fa-eye"></i> Qui a visité mon profil ?</h2>
            <hr>
 
<div id="visits-container">
    <?php foreach ($visites_par_jour as $jour_label => $visites): ?>
        <?php foreach ($visites as $visite): ?>
            <?php if ($is_premium): ?>
                <a href="detailprofile.php?id=<?= htmlspecialchars($visite['id_utilisateur']) ?>" class="visit-card-link">
                    <div class="visit-card">
                        <img class="visit-image" src="<?= htmlspecialchars($visite['image_profil']) ?>" alt="Profil">
                        <span class="visit-info">
                            <?= htmlspecialchars($visite['prenom']) ?> 
                            <span class="visit-age">(<?= $visite['age'] ?> ans)</span>
                            <small class="visit-time"><?= $jour_label ?> à <?= $visite['heure'] ?></small>
                        </span>
                    </div>
                </a>
            <?php else: ?>
                <div class="visit-card disabled-card">
                    <div class="visit-image d-flex align-items-center justify-content-center bg-light">
                        <i class="fas fa-user-secret fa-2x text-muted"></i>
                    </div>
                    <span class="visit-info">
                        Visiteur anonyme
                        <small class="visit-time"><?= $jour_label ?> à <?= $visite['heure'] ?></small>
                    </span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>







<!-- Bouton pour charger plus de visites -->
<button id="loadMore" class="btn btn-primary mt-3">Afficher plus</button>



        </div>
    </div>
</div>


    
    
    <!-- ✅ Pop-up pour inciter à passer premium -->
<div id="premiumPopup" class="modal fade" tabindex="-1" aria-labelledby="premiumPopupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-header">
                <h5 class="modal-title" id="premiumPopupLabel">Passez Premium !</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Débloquez l’accès à toutes les personnes qui ont visité votre profil en devenant membre premium !</p>
                <a href="premium.php" class="btn btn-primary mt-3">Devenir Premium</a>
            </div>
        </div>
    </div>
</div>


    <?php include("footer.php"); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
document.addEventListener("DOMContentLoaded", function () {
    var isPremium = <?= $is_premium ? 'true' : 'false'; ?>;
    
    if (!isPremium) {
        setTimeout(function () {
            var premiumPopup = new bootstrap.Modal(document.getElementById('premiumPopup'));
            premiumPopup.show();
        }, 3000);
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let offset = 5; // Commence après les 5 premiers résultats affichés
    let loadMoreButton = document.getElementById("loadMore");

    loadMoreButton.addEventListener("click", function () {
        fetch("load_more_visites.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "offset=" + offset
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === "empty") {
                loadMoreButton.style.display = "none"; // Cacher le bouton s'il n'y a plus de visites à charger
            } else {
                document.getElementById("visits-container").innerHTML += data;
                offset += 5; // Augmenter l'offset pour la prochaine requête
            }
        })
        .catch(error => console.error("Erreur lors du chargement des visites :", error));
    });
});
</script>


</body>
</html>


