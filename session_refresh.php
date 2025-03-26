<?php
require_once 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionTimeout = 900; // 15 minutes

$sessionId = session_id();
$stmt = $pdo->prepare("SELECT user_id, last_activity FROM sessions WHERE session_id = :session_id");
$stmt->execute(['session_id' => $sessionId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $lastActivity = !empty($user['last_activity']) ? strtotime($user['last_activity']) : 0;
    $currentTime = time();

    if ($currentTime - $lastActivity > $sessionTimeout) {
        // ✅ Vérifier une dernière fois avant suppression
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE session_id = :session_id AND last_activity >= NOW() - INTERVAL :timeout SECOND");
        $stmt->execute(['session_id' => $sessionId, 'timeout' => $sessionTimeout]);
        $stillActive = $stmt->fetchColumn();

        if (!$stillActive) {
            // ✅ Marquer l'utilisateur hors ligne
            $stmt = $pdo->prepare("UPDATE utilisateurs SET is_online = 0, derniere_connexion = NOW() WHERE id_utilisateur = :user_id");
            $stmt->execute(['user_id' => $user['user_id']]);

            // ✅ Supprimer la session
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = :session_id");
            $stmt->execute(['session_id' => $sessionId]);

        // ✅ Déconnecter la session PHP
session_unset();
session_destroy();

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

echo json_encode(["success" => false]); // Session expirée
exit();

        }
    }

    // ✅ Rafraîchir la session uniquement si elle existe encore
    $stmt = $pdo->prepare("UPDATE sessions SET last_activity = NOW() WHERE session_id = :session_id AND user_id = :user_id");
    $stmt->execute(['session_id' => $sessionId, 'user_id' => $user['user_id']]);

    echo json_encode(["success" => true]); // Session active
    exit();
} else {
    echo json_encode(["success" => false]); // Pas de session trouvée
    exit();
}

?>
