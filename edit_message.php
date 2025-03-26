<?php
require_once 'db_connect.php';

// 🔁 Réponse au format JSON
header('Content-Type: application/json');

// ✅ Vérifie que la méthode est bien POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 🔍 Récupère les données JSON envoyées dans le corps de la requête
    $data = json_decode(file_get_contents("php://input"), true);

    // 📌 Récupère l'ID du message depuis l'URL (GET)
    $messageId = intval($_GET['id']);

    // ✏️ Nouveau contenu du message
    $newContent = $data['contenu'];

    // 🔄 Préparation de la requête de mise à jour
    $sql = "UPDATE messages SET contenu = :contenu WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':contenu', $newContent);
    $stmt->bindParam(':id', $messageId);

    // ✅ Exécution de la requête
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour du message']);
    }

} else {
    // ❌ Mauvaise méthode HTTP
    echo json_encode(['success' => false, 'error' => 'Méthode de requête invalide']);
}
?>
