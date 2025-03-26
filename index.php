<?php
require_once 'session_handler.php'; // Gestion de session et inactivité
require_once 'db_connect.php'; // Connexion à la base de données

if (isset($_GET['timeout'])) {
    echo '<div class="alert alert-warning text-center">Votre session a expiré. Vous avez été redirigé vers l\'accueil.</div>';
}

// 🔹 1️⃣ Récupérer le nombre total d'utilisateurs inscrits
$sqlUsers = "SELECT COUNT(*) AS total_utilisateurs FROM utilisateurs";
$stmtUsers = $pdo->query($sqlUsers);
$total_utilisateurs = $stmtUsers->fetch(PDO::FETCH_ASSOC)['total_utilisateurs'];

// 🔹 2️⃣ Récupérer le nombre total de likes envoyés
$sqlLikes = "SELECT COUNT(*) AS total_likes FROM likes";
$stmtLikes = $pdo->query($sqlLikes);
$total_likes = $stmtLikes->fetch(PDO::FETCH_ASSOC)['total_likes'];

// 🔹 3️⃣ Récupérer le nombre total d'événements organisés
$sqlEvents = "SELECT COUNT(*) AS total_evenements FROM evenements";
$stmtEvents = $pdo->query($sqlEvents);
$total_evenements = $stmtEvents->fetch(PDO::FETCH_ASSOC)['total_evenements'];

// 🔹 4️⃣ Récupérer 3 utilisateurs aléatoires avec une image de profil
$stmt = $pdo->query("SELECT prenom, image_profil, date_naissance, ville 
                     FROM utilisateurs 
                     WHERE image_profil IS NOT NULL 
                     ORDER BY RAND() 
                     LIMIT 3");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambrosia - Rencontres Authentiques</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <?php include("nav.php"); ?>

    <!-- Section Héroïque -->
    <section class="hero-section">
    <div class="content-wrapper">
        <h2 class="static-text">Bienvenue sur Ambrosia</h2>
        <h1 class="dynamic-text">Rejoignez une communauté sincère</h1>
    </div>
        <div class="hero-statistics">
        <div class="stat">
            <h3>🌍 <?php echo number_format($total_utilisateurs); ?></h3>
            <p>Utilisateurs inscrits</p>
        </div>
        <div class="stat">
            <h3>❤️ <?php echo number_format($total_likes); ?></h3>
            <p>Likes envoyés</p>
        </div>
        <div class="stat">
            <h3>🎉 <?php echo number_format($total_evenements); ?></h3>
            <p>Événements organisés</p>
        </div>
    </div>


    <!-- 🔽 Ajout de l'indicateur de défilement 🔽 -->
    <div class="scroll-down">
        <p>Déroule pour en savoir plus</p>
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- Section Pourquoi nous choisir -->
    <section class="why-choose">
        <h2>Pourquoi nous choisir ?</h2>
        <div class="why-icons">
            <div class="why-item">
                <i class="fas fa-shield-alt pink-icon"></i>
                <h3>Sécurité</h3>
                <p>Ambrosia garantit un environnement sécurisé et modéré.</p>
            </div>
            <div class="why-item">
                <i class="fas fa-heart pink-icon"></i>
                <h3>Authenticité</h3>
                <p>Des profils vérifiés pour des rencontres sincères.</p>
            </div>
            <div class="why-item">
                <i class="fas fa-users pink-icon"></i>
                <h3>Communauté</h3>
                <p>Rejoignez une communauté de célibataires partageant vos valeurs.</p>
            </div>
        </div>
    </section>
    
    <!-- Inscription Rapide -->
<section class="container text-center py-5">
    <div class="card mx-auto p-4 shadow-sm signup-bg">
        <h2 class="mb-3">Inscription rapide</h2>
        <p class="text-muted">Rejoignez notre communauté en quelques clics.</p>
        <form class="signup-form" action="inscription.php" method="POST">
            <div class="mb-3 form-group">
                <label for="prenom" class="form-label"><i class="fas fa-user pink-icon"></i> Prénom</label>
                <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Entrez votre prénom" required>
            </div>
            <div class="mb-3 form-group">
                <label for="email" class="form-label"><i class="fas fa-envelope pink-icon"></i> Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Entrez votre email" required>
            </div>
            <button type="submit" class="btn signup-btn" name="inscription_rapide">S'inscrire</button>
        </form>
    </div>
</section>

<!-- 📌 Section Affichage des profils recommandés -->
<section class="featured-users container">
    <h2 class="text-center mb-5">Rencontrez nos membres</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach (array_slice($users, 0, 3) as $user): ?>
            <div class="col d-flex justify-content-center">
                <div class="profile-card">
                    <img src="<?= htmlspecialchars($user['image_profil']) ?>" alt="Photo de <?= htmlspecialchars($user['prenom']) ?>" class="profile-img">
                    <h3><?= htmlspecialchars($user['prenom']) ?></h3>
                    <p><?= htmlspecialchars($user['ville']) ?>, 
    <?= date_diff(date_create($user['date_naissance']), date_create('today'))->y ?> ans
</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

    
   <!-- 📱 Téléchargement de l'application -->
<section class="store-availability">
    <h2>Téléchargez l'application Ambrosia</h2>
    <div class="phone-frame">
        <div class="phone-screen">
            <img src="img/app.png" alt="Aperçu de l'application Ambrosia" class="app-preview">
        </div>
    </div>
</section>
<!-- 📌 Boutons de téléchargement bien espacés en bas -->
            <div class="store-links">
                <a href="#"><img src="img/appleB.svg" alt="Apple Store"></a>
                <a href="#"><img src="img/googleB.png" alt="Google Play"></a>
            </div>
        </div>
    </div>
</section>


 


    <?php include("footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="index.js"></script>
</body>
</html>

