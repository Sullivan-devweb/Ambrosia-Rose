<?php
require_once 'db_connect.php';

// 🔧 Activer le rapport d'erreurs et rediriger les erreurs vers un fichier log
ini_set('log_errors', 1);
ini_set('error_log', 'log.txt');
error_reporting(E_ALL);

// 🔁 Spécifier que la réponse sera en JSON
header('Content-Type: application/json');

// 📩 Vérifie que la requête est bien de type DELETE
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // 🧾 Récupère les données JSON envoyées dans le corps de la requête
    $data = json_decode(file_get_contents("php://input"), true);
    $messageId = isset($data['id']) ? intval($data['id']) : 0;

    // 🐞 Log pour débogage : afficher l’ID reçu
    error_log('ID du message reçu : ' . $messageId);

    // ✅ Vérifie que l’ID est valide (> 0)
    if ($messageId > 0) {
        $sql = "DELETE FROM messages WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $messageId);

        // 🚀 Exécute la requête de suppression
        if ($stmt->execute()) {
            // ✔️ Suppression réussie
            echo json_encode(['success' => true]);
        } else {
            // ❌ Erreur d'exécution SQL, log l'erreur
            $errorInfo = $stmt->errorInfo();
            error_log('Échec de la suppression : ' . print_r($errorInfo, true));
            echo json_encode(['success' => false, 'error' => 'Échec de l\'exécution de la requête de suppression']);
        }

    } else {
        // ❌ ID non valide
        error_log('ID de message invalide : ' . $messageId);
        echo json_encode(['success' => false, 'error' => 'ID de message invalide']);
    }

} else {
    // ❌ Mauvaise méthode HTTP utilisée
    error_log('Méthode non autorisée : ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'error' => 'Méthode de requête invalide']);
}
?>
