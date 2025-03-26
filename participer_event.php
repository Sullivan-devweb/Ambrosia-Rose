<?php
session_start();
header('Content-Type: application/json');

// Activer les erreurs pour débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la connexion à la base de données
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Vous devez être connecté pour participer ou annuler votre participation.']);
    exit;
}

$userId = $_SESSION['user_id'];
$eventId = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

// Vérifier si l'ID de l'événement est valide
if (!$eventId) {
    echo json_encode(['success' => false, 'error' => "L'ID de l'événement est invalide."]);
    exit;
}

try {
    // Vérifier si l'événement existe
    $sql = "SELECT id FROM evenements WHERE id = :event_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['event_id' => $eventId]);
    $eventExists = $stmt->fetch();

    if (!$eventExists) {
        echo json_encode(['success' => false, 'error' => "L'événement avec l'ID $eventId n'existe pas."]);
        exit;
    }

    // Vérifier si l'utilisateur participe déjà
    $sql = "SELECT * FROM participants WHERE event_id = :event_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['event_id' => $eventId, 'user_id' => $userId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Si déjà participant, supprimer la participation
        $sql = "DELETE FROM participants WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId, 'user_id' => $userId]);
        echo json_encode(['success' => true, 'message' => "Votre participation à l'événement a été annulée."]);
    } else {
        // Si pas encore participant, ajouter la participation
        $sql = "INSERT INTO participants (event_id, user_id) VALUES (:event_id, :user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId, 'user_id' => $userId]);
        echo json_encode(['success' => true, 'message' => "Vous avez été ajouté à l'événement."]);
    }
} catch (Exception $e) {
    // Gestion des erreurs
    echo json_encode(['success' => false, 'error' => "Erreur serveur : " . $e->getMessage()]);
}
?>

