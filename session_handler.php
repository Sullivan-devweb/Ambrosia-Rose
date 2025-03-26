<?php
require_once 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Définir la durée d'inactivité (15 min = 900 sec)
$sessionTimeout = 900;

// ✅ Vérifier si l'utilisateur est connecté en base
$sessionId = session_id();
$stmt = $pdo->prepare("SELECT user_id, last_activity FROM sessions WHERE session_id = :session_id");
$stmt->execute(['session_id' => $sessionId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_id = $user['user_id'];
    $lastActivity = !empty($user['last_activity']) ? strtotime($user['last_activity']) : 0;
    $currentTime = time();

    if ($currentTime - $lastActivity > $sessionTimeout) {
        // ✅ Session expirée, marquer l'utilisateur hors ligne
        $stmt = $pdo->prepare("UPDATE utilisateurs SET is_online = 0, derniere_connexion = NOW() WHERE id_utilisateur = :user_id");
        $stmt->execute(['user_id' => $user_id]);

        // ✅ Supprimer la session en base
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $sessionId]);

        // ✅ Déconnecter l'utilisateur côté PHP
        session_unset();
        session_destroy();

        // ✅ Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        // ✅ Répondre différemment en fonction du type de requête
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(["success" => false]);
            exit();
        } else {
            header("Location: https://ambrosiarose.404cahorsfound.fr/?timeout=1");
            exit();
        }
    } else {
        // ✅ Vérifier si la session existe avant de mettre à jour
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE session_id = :session_id AND user_id = :user_id");
        $stmt->execute(['session_id' => $sessionId, 'user_id' => $user_id]);
        $sessionExists = $stmt->fetchColumn();

        if ($sessionExists) {
            // ✅ Mettre à jour `last_activity`
            $stmt = $pdo->prepare("UPDATE sessions SET last_activity = NOW() WHERE session_id = :session_id AND user_id = :user_id");
            $stmt->execute(['session_id' => $sessionId, 'user_id' => $user_id]);

            // ✅ Vérifier que l'utilisateur est bien en ligne
            $stmt = $pdo->prepare("UPDATE utilisateurs SET is_online = 1 WHERE id_utilisateur = :user_id");
            $stmt->execute(['user_id' => $user_id]);
        }
    }

    $_SESSION['user_id'] = $user_id;
} else {
    // ✅ Mettre hors ligne les utilisateurs sans session active
    $stmt = $pdo->prepare("UPDATE utilisateurs SET is_online = 0 WHERE id_utilisateur NOT IN 
                          (SELECT DISTINCT user_id FROM sessions WHERE last_activity >= NOW() - INTERVAL :sessionTimeout SECOND)");
    $stmt->execute(['sessionTimeout' => $sessionTimeout]);
}
?>



