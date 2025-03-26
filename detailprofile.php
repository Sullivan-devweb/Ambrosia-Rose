<?php
require_once 'session_handler.php'; // 🔐 Gestion de session et inactivité
require 'db_connect.php';

// ✅ Vérifier que l'utilisateur est connecté
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("Erreur : Vous devez être connecté pour voir cette page.");
}

// ✅ Vérifier que l'ID passé en GET est valide
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID utilisateur invalide.");
}

$id_utilisateur = $_GET['id'];

// ✅ Enregistrer une visite (sauf si l'utilisateur consulte son propre profil)
if ((int) $user_id !== (int) $id_utilisateur) {
    $stmt = $pdo->prepare("
        INSERT INTO visites (visiteur_id, visite_id, date_visite) 
        VALUES (:user_id, :id_utilisateur, NOW())
    ");
    $stmt->execute([
        'id_utilisateur' => $id_utilisateur,
        'user_id' => $user_id
    ]);
}

// ✅ Récupérer les infos du profil + dernière activité
$stmt = $pdo->prepare("
    SELECT u.*, 
           COALESCE(s.last_activity, u.derniere_connexion) AS last_connection, 
           u.is_online
    FROM utilisateurs u
    LEFT JOIN sessions s ON u.id_utilisateur = s.user_id
    WHERE u.id_utilisateur = :id
");
$stmt->bindParam(':id', $id_utilisateur, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// ❌ Si aucun utilisateur trouvé
if (!$row) {
    die("Utilisateur introuvable.");
}

// 🔢 Nombre total de likes
$total_likes = $row['nb_likes'] ?? 0;

// 🟢 Vérifier si l'utilisateur est en ligne
$is_online = ($row['is_online'] == 1);
$last_connection_text = "Jamais connecté";

// 🕓 Si hors ligne, formater la dernière connexion
if (!$is_online && !empty($row['last_connection']) && $row['last_connection'] !== "0000-00-00 00:00:00") {
    $last_connection_time = strtotime($row['last_connection']);
    $diff_seconds = time() - $last_connection_time;

    if ($diff_seconds < 3600) {
        $last_connection_text = "Il y a " . floor($diff_seconds / 60) . " minutes";
    } elseif (date('Y-m-d', $last_connection_time) == date('Y-m-d')) {
        $last_connection_text = "Aujourd'hui à " . date('H:i', $last_connection_time);
    } elseif (date('Y-m-d', $last_connection_time) == date('Y-m-d', strtotime('-1 day'))) {
        $last_connection_text = "Hier à " . date('H:i', $last_connection_time);
    } else {
        $last_connection_text = "Dernière connexion le " . date('d/m/Y à H:i', $last_connection_time);
    }
}

// ❤️ Vérifier si l'utilisateur connecté a déjà liké ce profil
$stmt_like = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE id_utilisateur = :user_id AND id_cible = :id_utilisateur");
$stmt_like->execute(['user_id' => $user_id, 'id_utilisateur' => $id_utilisateur]);
$has_liked = $stmt_like->fetchColumn() > 0;

// ✅ Si l'utilisateur consulte son propre profil : marquer les likes comme vus
if ($user_id == $id_utilisateur) {
    $stmt = $pdo->prepare("UPDATE likes SET vu = 1 WHERE id_cible = :user_id AND vu = 0");
    $stmt->execute(['user_id' => $user_id]);
}

// 📝 Description du profil
$description = $row['description'] ?? "Aucune description disponible";

// 🖼️ Galerie d'images
$galerie_images = !empty($row['galerie_images']) ? array_unique(array_filter(explode(',', $row['galerie_images']))) : [];
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="detailprofile.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="badge_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="detailprofile" data-user-id="<?= htmlspecialchars($user_id); ?>">

    <?php include("nav.php"); ?>

    <div class="container my-5">
        <div class="profile-container shadow p-4 rounded">

            <!-- 🧑‍💼 En-tête profil -->
            <div class="profile-header d-flex flex-lg-row align-items-center justify-content-between gap-4 mb-4">
                <div class="profile-photo">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#profileImageModal">
                        <img id="profile-preview" class="img-thumbnail rounded-circle" src="<?= htmlspecialchars($row['image_profil']); ?>" alt="Photo de profil">
                    </a>
                    <?php if ($row['role'] == 'premium'): ?>
                        <div class="premium-badge">Premium</div>
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <h2 class="profile-name">
                        <?= htmlspecialchars($row['prenom']); ?>
                        <?php if ($row['genre'] == 'Homme'): ?>
                            <i class="fas fa-mars text-primary"></i>
                        <?php elseif ($row['genre'] == 'Femme'): ?>
                            <i class="fas fa-venus text-danger"></i>
                        <?php endif; ?>
                    </h2>

                    <p class="profile-detail"><strong>Âge :</strong> <?= date_diff(date_create($row['date_naissance']), date_create('today'))->y; ?> ans</p>
                    <p class="profile-detail"><strong>Ville :</strong> <?= htmlspecialchars($row['ville']); ?></p>
                    <p class="profile-detail"><strong>Pays :</strong> <?= htmlspecialchars($row['pays_residence']); ?></p>

                    <p class="profile-status">
                        <?php if ($is_online): ?>
                            <span class="online-status"><i class="fas fa-circle text-success"></i> En ligne</span>
                        <?php else: ?>
                            <span class="last-connection"><?= $last_connection_text; ?></span>
                        <?php endif; ?>
                    </p>

                    <p class="profile-likes"><strong>Nombre de likes :</strong> <span id="like-count"><?= htmlspecialchars($total_likes); ?></span> ❤️</p>

                    <!-- 🔘 Actions : Like & Message -->
                    <div class="profile-actions">
                        <form method="POST" action="like.php">
                            <input type="hidden" name="id_cible" value="<?= htmlspecialchars($id_utilisateur); ?>">
                            <button type="submit" class="btn btn-like <?= $has_liked ? 'liked' : ''; ?>">
                                <i class="fas fa-heart"></i> <?= $has_liked ? "Retirer le like" : "J'aime"; ?>
                            </button>
                        </form>

                        <a href="messagerie.php?contact_id=<?= htmlspecialchars($id_utilisateur) ?>" class="btn btn-message">
                            <i class="fas fa-envelope"></i> Envoyer un message
                        </a>
                    </div>
                </div>
            </div>

            <!-- 📝 Description -->
            <div class="profile-section mb-4 p-4 rounded shadow-sm bg-light">
                <h3 class="text-center mb-4"><i class="fas fa-align-left"></i> Description</h3>
                <p class="text-center"><?= nl2br(htmlspecialchars($description)); ?></p>
            </div>

            <!-- ℹ️ Informations complémentaires -->
            <?php if ($has_info): ?>
                <div class="profile-section mb-4 p-4 rounded shadow-sm bg-light">
                    <h3 class="text-center mb-4"><i class="fas fa-user"></i> En savoir plus...</h3>
                    <div class="row">
                        <div class="col-md-6 profile-details">
                            <?php if (!empty($row['situation_amoureuse'])): ?>
                                <p><i class="fas fa-heart"></i> <strong>Situation amoureuse :</strong> <?= htmlspecialchars($row['situation_amoureuse']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['enfants'])): ?>
                                <p><i class="fas fa-baby"></i> <strong>Enfants :</strong> <?= htmlspecialchars($row['enfants']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['souhaite_enfants'])): ?>
                                <p><i class="fas fa-child"></i> <strong>Souhaite des enfants :</strong> <?= htmlspecialchars($row['souhaite_enfants']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['localisation_importante'])): ?>
                                <p><i class="fas fa-map-marker-alt"></i> <strong>Localisation :</strong> <?= htmlspecialchars($row['localisation_importante']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['traits_personnalite'])): ?>
                                <p><i class="fas fa-user-astronaut"></i> <strong>Personnalité :</strong> <?= htmlspecialchars($row['traits_personnalite']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['valeurs_relation'])): ?>
                                <p><i class="fas fa-handshake"></i> <strong>Valeurs :</strong> <?= htmlspecialchars($row['valeurs_relation']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['passions'])): ?>
                                <p><i class="fas fa-gamepad"></i> <strong>Passions :</strong> <?= htmlspecialchars($row['passions']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['relation_avec_sport'])): ?>
                                <p><i class="fas fa-dumbbell"></i> <strong>Sport :</strong> <?= htmlspecialchars($row['relation_avec_sport']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['relation_avec_technologie'])): ?>
                                <p><i class="fas fa-laptop-code"></i> <strong>Technologie :</strong> <?= htmlspecialchars($row['relation_avec_technologie']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 profile-details">
                            <?php if (!empty($row['type_relation'])): ?>
                                <p><i class="fas fa-heartbeat"></i> <strong>Type de relation :</strong> <?= htmlspecialchars($row['type_relation']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['importance_engagement'])): ?>
                                <p><i class="fas fa-balance-scale"></i> <strong>Engagement :</strong> <?= htmlspecialchars($row['importance_engagement']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['rythme_relation'])): ?>
                                <p><i class="fas fa-hourglass-half"></i> <strong>Rythme :</strong> <?= htmlspecialchars($row['rythme_relation']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['relation_distance'])): ?>
                                <p><i class="fas fa-globe"></i> <strong>Relation à distance :</strong> <?= htmlspecialchars($row['relation_distance']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['moyen_communication'])): ?>
                                <p><i class="fas fa-envelope"></i> <strong>Moyen de communication :</strong> <?= htmlspecialchars($row['moyen_communication']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($row['frequence_communication'])): ?>
                                <p><i class="fas fa-comments"></i> <strong>Fréquence de communication :</strong> <?= htmlspecialchars($row['frequence_communication']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 🖼️ Galerie d'images -->
            <div class="profile-section mb-4">
                <h3 class="text-center mb-3">Galerie d'images</h3>
                <div class="gallery-container d-flex flex-wrap justify-content-center gap-3">
                    <?php if (!empty($galerie_images)): ?>
                        <?php foreach ($galerie_images as $image): ?>
                            <div class="gallery-item">
                                <a href="#" class="open-gallery-modal" data-bs-toggle="modal" data-bs-target="#galleryModal" data-image="uploads/gallery/<?= htmlspecialchars(trim($image)); ?>">
                                    <img src="uploads/gallery/<?= htmlspecialchars(trim($image)); ?>" alt="Image utilisateur" class="img-thumbnail">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Aucune image disponible</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 🔙 Bouton retour -->
            <div class="text-center">
                <button class="btn btn-secondary" onclick="window.history.back()">Retour</button>
            </div>
        </div>
    </div>

    <!-- 🔍 Modale Galerie -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="galleryModalLabel">Aperçu de l'image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modal-image" src="" alt="Image agrandie" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- 👤 Modale photo de profil -->
    <div class="modal fade" id="profileImageModal" tabindex="-1" aria-labelledby="profileImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileImageModalLabel">Photo de Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modal-profile-image" src="" alt="Image de profil" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <?php include("footer.php"); ?>

    <!-- 📦 Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 📸 Galerie image click
            document.querySelectorAll(".open-gallery-modal").forEach(item => {
                item.addEventListener("click", function() {
                    let imageUrl = this.getAttribute("data-image");
                    document.getElementById("modal-image").setAttribute("src", imageUrl);
                });
            });

            // 👤 Image de profil
            document.getElementById("profile-preview").addEventListener("click", function() {
                let imageUrl = this.getAttribute("src");
                if (imageUrl) {
                    document.getElementById("modal-profile-image").setAttribute("src", imageUrl);
                }
            });
        });
    </script>

    <script src="envoyer_mail.js"></script>
</body>
</html>






