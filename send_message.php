<?php
session_start();
include 'db_connect.php'; // Assurez-vous que le fichier db_connect.php contient la connexion à votre base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

$id_utilisateur = $_SESSION['user_id'];
$message = $_POST['message'] ?? null;
$id_destinataire = $_POST['id_destinataire'] ?? null;

// Valider les données
if (empty($message) || empty($id_destinataire)) {
    echo json_encode(['success' => false, 'message' => 'Message ou destinataire manquant']);
    exit;
}

// Insérer le message dans la base de données
try {
    $query = "INSERT INTO messages (id_expediteur, id_destinataire, message, date_envoi)
              VALUES (:id_expediteur, :id_destinataire, :message, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'id_expediteur' => $id_utilisateur,
        'id_destinataire' => $id_destinataire,
        'message' => $message
    ]);

    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()]);
}
?>