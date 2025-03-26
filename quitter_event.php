<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Vous devez être connecté pour annuler votre participation.']);
    exit;
}

$userId = $_SESSION['user_id'];
$eventId = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

if (!$eventId) {
    echo json_encode(['success' => false, 'error' => "ID de l'événement invalide."]);
    exit;
}

require_once 'db_connect.php';

try {
    $sql = "DELETE FROM participants WHERE event_id = :event_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['event_id' => $eventId, 'user_id' => $userId]);
    echo json_encode(['success' => true, 'message' => "Vous avez annulé votre participation à cet événement."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => "Erreur serveur : " . $e->getMessage()]);
}
