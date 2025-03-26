<?php
require_once 'session_handler.php'; // ✅ Gestion propre des sessions
require_once 'db_connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔹 Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id_cible = isset($_POST['id_cible']) ? intval($_POST['id_cible']) : 0;

// ✅ Empêcher un utilisateur de se liker lui-même
if ($id_cible <= 0 || $id_cible == $user_id) {
    header("Location: detailprofile.php?id=" . $id_cible . "&error=self_like");
    exit();
}

try {
    // 🔹 Vérifier si l'utilisateur a déjà liké ce profil
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE id_utilisateur = :user_id AND id_cible = :id_cible");
    $stmt->execute(['user_id' => $user_id, 'id_cible' => $id_cible]);
    $has_liked = $stmt->fetchColumn() > 0;

    if ($has_liked) {
        // 🔹 Supprimer le like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE id_utilisateur = :user_id AND id_cible = :id_cible");
        $stmt->execute(['user_id' => $user_id, 'id_cible' => $id_cible]);

        // 🔹 Décrémenter le compteur de likes dans la table utilisateurs
        $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET nb_likes = nb_likes - 1 WHERE id_utilisateur = :id_cible");
        $stmtUpdate->execute(['id_cible' => $id_cible]);

        // 🚨 **Supprimer la notification associée au like**
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = :id_cible AND sender_id = :user_id AND type = 'like'");
        $stmt->execute(['user_id' => $user_id, 'id_cible' => $id_cible]);

        // ✅ Empêcher l'envoi d'un email lors de la suppression du like
        file_put_contents("debug_log.txt", "✅ Like supprimé - Aucun email envoyé.\n", FILE_APPEND);

    } else {
        // 🔹 Ajouter le like
        $stmt = $pdo->prepare("INSERT INTO likes (id_utilisateur, id_cible, date_like, vu) VALUES (:user_id, :id_cible, NOW(), 0)");
        $stmt->execute(['user_id' => $user_id, 'id_cible' => $id_cible]);

        // 🔹 Incrémenter le compteur de likes dans la table utilisateurs
        $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET nb_likes = nb_likes + 1 WHERE id_utilisateur = :id_cible");
        $stmtUpdate->execute(['id_cible' => $id_cible]);

        // ✅ Ajouter une notification pour l'utilisateur ciblé
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, date_notification, vu) 
                               VALUES (:id_cible, :user_id, 'like', NOW(), 0)");
        $stmt->execute(['user_id' => $id_cible, 'sender_id' => $user_id]);
    }

    // ✅ Redirection correcte après suppression ou ajout
    header("Location: detailprofile.php?id=" . $id_cible);
    exit();

} catch (PDOException $e) {
    file_put_contents("debug_log.txt", "❌ Erreur SQL : " . $e->getMessage() . "\n", FILE_APPEND);
    header("Location: detailprofile.php?id=" . $id_cible . "&error=db_error");
    exit();
}
?>





