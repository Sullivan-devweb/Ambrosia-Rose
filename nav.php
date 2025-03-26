<?php
require_once 'session_handler.php'; // Toujours en premier
require_once 'db_connect.php'; // Ensuite la connexion BDD

// ✅ Vérifier si l'utilisateur est connecté
$user_id = $_SESSION['user_id'] ?? null;
$isConnected = false;
$userProfileImage = 'img/default-profile.jpg';
$userRole = '';

$visitCount = 0;
$likeCount = 0;
$messageCount = 0;

if ($user_id) {
    // 🔹 Récupérer les infos utilisateur
    $stmt = $pdo->prepare("SELECT image_profil, role FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $isConnected = true;
        $userProfileImage = !empty($user['image_profil']) ? $user['image_profil'] : $userProfileImage;
        $userRole = $user['role'];
        
        // 🔹 Récupérer les notifications
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM visites WHERE visite_id = :user_id AND vu = 0");
        $stmt->execute(['user_id' => $user_id]);
        $visitCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE id_cible = :user_id AND vu = 0");
        $stmt->execute(['user_id' => $user_id]);
        $likeCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE destinataire_id = :user_id AND vu = 0");
        $stmt->execute(['user_id' => $user_id]);
        $messageCount = (int) $stmt->fetchColumn();
    } else {
        session_unset();
        session_destroy();
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <!-- 🏆 Logo -->
        <a class="navbar-brand" href="index.php">
            <img src="img/Logo_Ambr.png" alt="Logo Ambrosia">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if ($isConnected): ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="accueil.php"><i class="fas fa-home"></i> Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="evenements.php"><i class="fas fa-calendar-alt"></i> Événements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="messagerie.php">
                        <i class="fas fa-envelope"></i> Messagerie
                        <?php if ($messageCount > 0): ?>
                            <span id="message-badge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                <?= $messageCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="visites.php">
                        <i class="fas fa-eye"></i> Mes visites
                        <?php if ($visitCount > 0): ?>
                            <span id="visit-badge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                <?= $visitCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="page_like.php">
                        <i class="fas fa-heart"></i> Mes likes
                        <?php if ($likeCount > 0): ?>
                            <span id="like-badge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                <?= $likeCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- 🔥 Bouton Premium (visible uniquement pour les non-premiums) -->
                <?php if ($userRole !== 'premium'): ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="premium.php">
                        <i class="fas fa-gem"></i> Premium
                    </a>
                </li>
                <?php endif; ?>

                <!-- 👑 Admin Panel (uniquement si admin) -->
                <?php if ($userRole === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php"><i class="fas fa-user-shield"></i> Admin</a>
                </li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>

            <!-- 👤 Connexion / Inscription -->
            <div class="user-actions ms-auto">
                <?php if (!$isConnected): ?>
                    <a href="connexion.php" class="btn btn-danger"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                    <a href="inscription.php" class="btn btn-danger"><i class="fas fa-user-plus"></i> Inscription</a>
                <?php else: ?>
                    <a href="editprofil.php" class="user-profile">
                        <img src="<?= htmlspecialchars($userProfileImage) ?>" class="rounded-circle" width="40" height="40">
                    </a>
                    <a href="deconnexion.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>



<script>
document.addEventListener("DOMContentLoaded", function () {
    function resetNotifications(type) {
        fetch("update_notifications.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "type=" + type
        }).then(response => response.json())
          .then(data => {
              if (data.status === "success") {
                  let badge = null;
                  if (type === "likes") {
                      badge = document.getElementById("like-badge");
                  } else if (type === "visites") {
                      badge = document.getElementById("visit-badge");
                  } else if (type === "messages") {
                      badge = document.getElementById("message-badge");
                  }
                  if (badge) {
                      badge.style.display = "none";
                  }
              }
          })
          .catch(error => console.error("Erreur:", error));
    }

    document.querySelectorAll(".nav-link").forEach(link => {
        link.addEventListener("click", function() {
            if (this.querySelector(".badge")) {
                resetNotifications(this.getAttribute("href").split(".php")[0]);
            }
        });
    });

    // ✅ Cacher les badges s'ils sont vides
    document.querySelectorAll(".badge").forEach(badge => {
        if (badge.innerText.trim() === "0") {
            badge.style.display = "none";
        }
    });
});

</script>



<style>
/* 🔹 Styles pour le badge de notifications */
.badge {
    font-size: 0.75rem;
    padding: 5px 8px;
}
</style>

<script src="nav.js"></script>
<script src="session_refresh.js"></script>