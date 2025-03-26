<?php
require_once 'db_connect.php';
session_start();

// ✅ Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    try {
        // ✅ Mettre is_online à 0 et enregistrer la dernière connexion
        $stmt = $pdo->prepare("UPDATE utilisateurs SET is_online = 0, derniere_connexion = NOW() WHERE id_utilisateur = :user_id");
        $stmt->execute(['user_id' => $user_id]);

        // ✅ Supprimer la session SQL
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);

        // ✅ Vérification de la suppression de la session SQL
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $session_count = $stmt->fetchColumn();

        if ($session_count == 0) {
            error_log("✅ Déconnexion réussie : L'utilisateur $user_id est bien hors ligne et session supprimée.");
        } else {
            error_log("❌ ERREUR : La session de l'utilisateur $user_id n'a pas été supprimée !");
        }

    } catch (PDOException $e) {
        error_log("❌ ERREUR SQL lors de la déconnexion de l'utilisateur $user_id : " . $e->getMessage());
    }
}

// ✅ Supprimer la session PHP
session_unset();
session_destroy();

// ✅ Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// ✅ Rediriger vers la page de connexion
header("Location: https://ambrosiarose.404cahorsfound.fr/");
exit();
