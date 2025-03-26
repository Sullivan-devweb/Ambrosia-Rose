<?php
require_once 'session_handler.php'; // ✅ Toujours en premier

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'not_logged_in',
        'session_data' => $_SESSION
    ]);
    exit();
}

// ✅ Vérification de l'inactivité
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'session_expired']);
    exit();
}

// ✅ Mise à jour de la dernière activité
$_SESSION['last_activity'] = time();
echo json_encode(['status' => 'active', 'session_data' => $_SESSION]);
?>
