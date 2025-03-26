<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté']);
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'] ?? '';

try {
    if ($type === 'likes') {
        $stmt = $pdo->prepare("UPDATE likes SET vu = 1 WHERE id_cible = :user_id AND vu = 0");
        $stmt->execute(['user_id' => $user_id]);
        echo json_encode(['status' => 'success']);
    } elseif ($type === 'visites') {
        // ✅ Met à jour uniquement les nouvelles visites non vues
        $stmt = $pdo->prepare("
            UPDATE visites 
            SET vu = 1 
            WHERE visite_id = :user_id 
            AND vu = 0
        ");
        $stmt->execute(['user_id' => $user_id]);
        echo json_encode(['status' => 'success']);
    } elseif ($type === 'messages') {
        $stmt = $pdo->prepare("UPDATE messages SET vu = 1 WHERE destinataire_id = :user_id AND vu = 0");
        $stmt->execute(['user_id' => $user_id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Type inconnu']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur SQL : ' . $e->getMessage()]);
}
exit();
