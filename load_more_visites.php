<?php
require_once 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo 'empty';
    exit();
}

$user_id = $_SESSION['user_id'];
$offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
$limit = 5; // Charger 5 visites à chaque fois

// Vérifier si l'utilisateur est premium ou admin
$stmtRole = $pdo->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = :user_id");
$stmtRole->execute(['user_id' => $user_id]);
$role = $stmtRole->fetchColumn();
$is_premium = in_array(trim($role), ['premium', 'admin']);


// Activer le mode débogage pour voir les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $stmt = $pdo->prepare("
        SELECT u.id_utilisateur, u.prenom, u.date_naissance, u.image_profil, v.date_visite
        FROM visites v
        JOIN utilisateurs u ON v.visiteur_id = u.id_utilisateur
        WHERE v.visite_id = :user_id AND v.visiteur_id != :user_id
        ORDER BY v.date_visite DESC
        LIMIT :limit OFFSET :offset
    ");

    // Bind les paramètres correctement
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $visites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($visites) > 0) {
        foreach ($visites as $visite) {
            // Calculer l'âge
            $date_naissance = new DateTime($visite['date_naissance']);
            $aujourdhui = new DateTime();
            $age = $date_naissance->diff($aujourdhui)->y;

            // Déterminer le jour
            $jour = date('Y-m-d', strtotime($visite['date_visite']));
            if ($jour == date('Y-m-d')) {
                $jour_label = "Aujourd'hui";
            } elseif ($jour == date('Y-m-d', strtotime('-1 day'))) {
                $jour_label = "Hier";
            } else {
                $jour_label = date('d/m/Y', strtotime($jour));
            }

            echo '<a href="detailprofile.php?id='.htmlspecialchars($visite['id_utilisateur']).'" class="visit-card-link">';
            echo '<div class="visit-card '. ($is_premium ? '' : 'blurred') .'">';
            echo '<img class="visit-image" src="'.htmlspecialchars($visite['image_profil']).'" alt="Profil">';
            echo '<span class="visit-info">';
            echo htmlspecialchars($visite['prenom']).' ';
            echo '<span class="visit-age">('.$age.' ans)</span>';
            echo '<small class="visit-time">'.$jour_label.' à '.date('H:i', strtotime($visite['date_visite'])).'</small>';
            echo '</span>';
            echo '</div>';
            echo '</a>';

        }
    } else {
        echo 'empty';
    }
} catch (Exception $e) {
    echo 'Erreur : '.$e->getMessage();
}
?>

