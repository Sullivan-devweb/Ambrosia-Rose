<?php
session_start();
require 'db_connect.php';

if (!isset($_POST['search'])) {
    exit();
}

$search = trim($_POST['search']);

// Préparer la requête SQL pour rechercher prénom, ville, âge et centres d'intérêt
$sql = "
SELECT DISTINCT u.id_utilisateur, u.prenom, u.image_profil, 
       TIMESTAMPDIFF(YEAR, u.date_naissance, CURDATE()) AS age, 
       u.ville, u.genre, u.nb_likes
FROM utilisateurs u 
LEFT JOIN utilisateur_interets ui ON u.id_utilisateur = ui.id_utilisateur
LEFT JOIN centres_interet ci ON ui.interet_id = ci.id
WHERE u.prenom LIKE :search 
   OR u.ville LIKE :search 
   OR ci.nom LIKE :search 
   OR TIMESTAMPDIFF(YEAR, u.date_naissance, CURDATE()) = :age
ORDER BY u.nb_likes DESC";

$stmt = $pdo->prepare($sql);
$searchParam = "%$search%";
$ageParam = is_numeric($search) ? intval($search) : null;
$stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
$stmt->bindParam(':age', $ageParam, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "<p>Aucun résultat trouvé.</p>";
} else {
    foreach ($results as $user) {
        ?>
        <a href="detailprofile.php?id=<?php echo htmlspecialchars($user['id_utilisateur']); ?>" class="profile-link">
            <div class="profile-item">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($user['image_profil']); ?>" alt="Photo de <?php echo htmlspecialchars($user['prenom']); ?>" class="profile-image">
                    <h3><?php echo htmlspecialchars($user['prenom']); ?></h3>
                    <p class="like-count">❤️ <?php echo $user['nb_likes']; ?> likes</p>
                </div>
                <div class="profile-info">
                    <p><strong>Âge:</strong> <?php echo $user['age']; ?> ans</p>
                    <p><strong>Ville:</strong> <?php echo htmlspecialchars($user['ville']); ?></p>
                    <p><strong>Genre:</strong> <?php echo htmlspecialchars($user['genre']); ?></p>
                </div>
                <div class="interests-container">
                    <?php
                    $stmt_interests = $pdo->prepare("SELECT ci.nom FROM utilisateur_interets ui 
                                                     JOIN centres_interet ci ON ui.interet_id = ci.id 
                                                     WHERE ui.id_utilisateur = ?");
                    $stmt_interests->execute([$user['id_utilisateur']]);
                    $interests = $stmt_interests->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($interests)) {
                        echo '<div class="interests">' . implode('</div><div class="interests">', $interests) . '</div>'; 
                    } else {
                        echo "<p class='no-interests-message'>Aucun centre d'intérêt</p>";
                    }
                    ?>
                </div>
            </div>
        </a>
        <?php
    }
}
?>
