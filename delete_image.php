<?php
session_start();
include 'db_connect.php';

// Vérification de la session utilisateur
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Récupération des données envoyées
$data = json_decode(file_get_contents("php://input"), true);
$imageToDelete = $data['image'] ?? '';

if ($imageToDelete) {
    $user_id = $_SESSION['user_id'];

    try {
        // Récupérer les images actuelles de l'utilisateur
        $stmt = $pdo->prepare("SELECT galerie_images FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && !empty($row['galerie_images'])) {
            $existing_images = explode(',', $row['galerie_images']);
        } else {
            $existing_images = [];
        }

        if (in_array($imageToDelete, $existing_images)) {
            // Supprimer l'image du tableau
            $updated_images = array_diff($existing_images, [$imageToDelete]);
            $new_images_str = implode(',', $updated_images);

            // Mettre à jour la base de données
            $updateStmt = $pdo->prepare("UPDATE utilisateurs SET galerie_images = :galerie_images WHERE id_utilisateur = :id");
            $updateStmt->execute([
                'galerie_images' => $new_images_str,
                'id' => $user_id
            ]);

            // Supprimer physiquement le fichier
            $file_path = "uploads/gallery/" . basename($imageToDelete);
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    $_SESSION['galerie_images'] = $new_images_str;
                    echo json_encode(['success' => true, 'message' => 'Image supprimée avec succès']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du fichier']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Fichier introuvable']);
            }
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Image non trouvée dans la base de données']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aucune image spécifiée']);
}

exit;
?>   