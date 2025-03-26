<?php
// 🔐 GESTION DES ACCÈS UTILISATEUR
require_once 'session_handler.php'; // Gestion de session et vérification d'inactivité
require_once 'db_connect.php';      // Connexion à la base de données

// 🔐 Rediriger si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// ✅ Fonction utilitaire pour afficher une icône si l'utilisateur est en ligne
function afficherIconeOnline($is_online) {
    return $is_online ? "<i class='fas fa-circle text-success'></i>" : "";
}

// 📦 Données utilisateur en session
$user_id = $_SESSION['user_id'];
$user_preference = $_SESSION['recherche'] ?? null; // ex: Homme, Femme, Homme et Femme

// 🛑 Vérifier que la préférence de recherche existe
if (!$user_preference) {
    die("Erreur : La préférence de recherche n'est pas définie.");
}

// 🔁 Pattern PRG : rediriger après POST (soumission du formulaire de recherche)
if (!empty($_POST['search'])) {
    $search = trim($_POST['search']);
    header("Location: accueil.php?search=" . urlencode($search));
    exit();
}

// 🔍 Traitement de la recherche utilisateur (via GET)
$search = $_GET['search'] ?? null;
$searchResults = [];

if (!empty($search)) {
    // 🔎 Requête SQL de recherche
    $sqlSearch = "
        SELECT DISTINCT u.id_utilisateur, u.prenom, u.image_profil, 
               TIMESTAMPDIFF(YEAR, u.date_naissance, CURDATE()) AS age, 
               u.ville, u.pays_residence, u.genre, u.nb_likes, u.role, 
               u.is_online
        FROM utilisateurs u 
        WHERE (u.prenom LIKE :search 
            OR u.ville LIKE :search 
            OR u.pays_residence LIKE :search 
            OR (TIMESTAMPDIFF(YEAR, u.date_naissance, CURDATE()) = :age))
        ORDER BY u.nb_likes DESC";

    $stmtSearch = $pdo->prepare($sqlSearch);
    $searchParam = "%$search%";
    $stmtSearch->bindParam(':search', $searchParam, PDO::PARAM_STR);

    if (is_numeric($search)) {
        $ageParam = intval($search);
        $stmtSearch->bindParam(':age', $ageParam, PDO::PARAM_INT);
    } else {
        $stmtSearch->bindValue(':age', null, PDO::PARAM_NULL);
    }

    $stmtSearch->execute();
    $searchResults = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
}

// 🌟 Sélection aléatoire de 4 profils premium
$sqlPremium = "
    SELECT u.id_utilisateur, u.prenom, u.image_profil, u.date_naissance, u.ville, 
           u.pays_residence, u.nb_likes, u.genre, u.role, u.is_online
    FROM utilisateurs u
    WHERE (u.genre = ? OR ? = 'Homme et Femme') 
          AND u.role = 'premium' 
          AND u.id_utilisateur != ?
    ORDER BY RAND()
    LIMIT 4";

$stmtPremium = $pdo->prepare($sqlPremium);
$stmtPremium->execute([$user_preference, $user_preference, $user_id]);
$premiumUsers = $stmtPremium->fetchAll(PDO::FETCH_ASSOC);

// 👥 Récupération des autres utilisateurs (par date de dernière connexion)
$sqlUtilisateurs = "
    SELECT u.id_utilisateur, u.prenom, u.image_profil, u.date_naissance, u.ville, 
           u.pays_residence, u.nb_likes, u.genre, u.role, u.is_online
    FROM utilisateurs u
    WHERE (u.genre = ? OR ? = 'Homme et Femme') 
          AND u.id_utilisateur != ? 
    ORDER BY u.derniere_connexion DESC";

$stmtUtilisateurs = $pdo->prepare($sqlUtilisateurs);
$stmtUtilisateurs->execute([$user_preference, $user_preference, $user_id]);
$utilisateurs = $stmtUtilisateurs->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambrosia - Rencontres Authentiques</title>

    <!-- Styles CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="accueil.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="badge_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <?php include("nav.php"); ?>

    <div class="container-fluid">
        <div class="row">

            <!-- 🧭 Colonne principale -->
            <div class="col-md-9">

                <!-- 🔎 Barre de recherche -->
                <section class="search-bar py-3">
                    <form method="POST" action="accueil.php" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Rechercher un prénom, une ville etc.." required>
                        <button type="submit" class="btn btn-primary">Rechercher</button>
                    </form>
                </section>

                <!-- 🔍 Résultats de la recherche -->
                <?php if (!empty($searchResults)): ?>
                    <section class="container text-center py-5">
                        <h2>Résultats de la recherche</h2>
                        <a href="accueil.php" class="btn btn-secondary mb-3">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>

                        <div class="row">
                            <?php foreach ($searchResults as $user): ?>
                                <div class="col-md-3">
                                    <a href="detailprofile.php?id=<?= htmlspecialchars($user['id_utilisateur']) ?>" class="text-decoration-none">
                                        <div class="profile-item">
                                            <img src="<?= htmlspecialchars($user['image_profil']) ?>" class="profile-image" alt="<?= htmlspecialchars($user['prenom']) ?>">
                                            <h3>
                                                <?= htmlspecialchars($user['prenom']) ?>
                                                <?= afficherIconeOnline($user['is_online']) ?>
                                                <?php if ($user['role'] == 'premium'): ?>
                                                    <div class="premium-badge">Premium</div>
                                                <?php endif; ?>
                                            </h3>
                                            <p><?= htmlspecialchars($user['pays_residence']) ?></p>
                                            <p><?= htmlspecialchars($user['ville']) ?></p>
                                            <p>Âge : <?= htmlspecialchars($user['age']) ?> ans</p>
                                            <p class="like-count">❤️ <?= htmlspecialchars($user['nb_likes']) ?> likes</p>
                                            <a href="messagerie.php?contact_id=<?= htmlspecialchars($user['id_utilisateur']) ?>" class="btn btn-primary">
                                                <i class="fas fa-envelope"></i> Envoyer un message
                                            </a>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 🌟 Utilisateurs premium -->
                <section class="container text-center py-5">
                    <h2>🔥 Utilisateurs Premium</h2>
                    <div class="row">
                        <?php if (!empty($premiumUsers)): ?>
                            <?php foreach ($premiumUsers as $user): ?>
                                <div class="col-md-3">
                                    <a href="detailprofile.php?id=<?= htmlspecialchars($user['id_utilisateur']) ?>" class="text-decoration-none">
                                        <div class="profile-item premium">
                                            <img src="<?= htmlspecialchars($user['image_profil']) ?>" class="profile-image" alt="<?= htmlspecialchars($user['prenom']) ?>">
                                            <h3>
                                                <?= htmlspecialchars($user['prenom']) ?>
                                                <?= afficherIconeOnline($user['is_online']) ?>
                                                <div class="premium-badge">Premium</div>
                                            </h3>
                                            <p><?= htmlspecialchars($user['pays_residence']) ?></p>
                                            <p><?= htmlspecialchars($user['ville']) ?></p>
                                            <p>Âge : <?= date_diff(date_create($user['date_naissance']), date_create('today'))->y ?> ans</p>
                                            <p class="like-count">❤️ <?= htmlspecialchars($user['nb_likes']) ?> likes</p>
                                            <a href="messagerie.php?contact_id=<?= htmlspecialchars($user['id_utilisateur']) ?>" class="btn btn-primary">
                                                <i class="fas fa-envelope"></i> Envoyer un message
                                            </a>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucun utilisateur premium disponible.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- 👥 Tous les utilisateurs -->
                <section class="container text-center py-5">
                    <h2>👥 Nos utilisateurs</h2>
                    <div class="row">
                        <?php if (!empty($utilisateurs)): ?>
                            <?php foreach ($utilisateurs as $user): ?>
                                <div class="col-md-3">
                                    <a href="detailprofile.php?id=<?= htmlspecialchars($user['id_utilisateur']) ?>" class="text-decoration-none">
                                        <div class="profile-item">
                                            <img src="<?= htmlspecialchars($user['image_profil']) ?>" class="profile-image" alt="<?= htmlspecialchars($user['prenom']) ?>">
                                            <h3>
                                                <?= htmlspecialchars($user['prenom']) ?>
                                                <?= afficherIconeOnline($user['is_online']) ?>
                                                <?php if ($user['role'] == 'premium'): ?>
                                                    <div class="premium-badge">Premium</div>
                                                <?php endif; ?>
                                            </h3>
                                            <p><?= htmlspecialchars($user['pays_residence']) ?></p>
                                            <p><?= htmlspecialchars($user['ville']) ?></p>
                                            <p>Âge : <?= date_diff(date_create($user['date_naissance']), date_create('today'))->y ?> ans</p>
                                            <p class="like-count">❤️ <?= htmlspecialchars($user['nb_likes']) ?> likes</p>
                                            <a href="messagerie.php?contact_id=<?= htmlspecialchars($user['id_utilisateur']) ?>" class="btn btn-primary">
                                                <i class="fas fa-envelope"></i> Envoyer un message
                                            </a>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucun utilisateur disponible.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- 📱 Colonne réseaux sociaux -->
            <div class="col-md-3">
                <aside class="social-feed">
                    <div class="facebook-container">
                        <h2><i class="fab fa-facebook"></i> Suivez-nous sur Facebook</h2>
                        <div class="facebook-box">
                            <div class="fb-page"
                                 data-href="https://www.facebook.com/AmbrosiaMaVieOfficielle"
                                 data-tabs="timeline"
                                 data-width="340"
                                 data-height="400"
                                 data-small-header="false"
                                 data-adapt-container-width="true"
                                 data-hide-cover="false"
                                 data-show-facepile="true">
                            </div>
                        </div>
                    </div>
                    <script async defer crossorigin="anonymous"
                            src="https://connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v12.0">
                    </script>
                </aside>
            </div>

        </div>
    </div>

    <?php include("footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
