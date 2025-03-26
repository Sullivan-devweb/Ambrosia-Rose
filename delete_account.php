<?php
session_start();
require_once 'db_connect.php'; // Connexion à la base de données

// Vérifier que l'utilisateur est bien connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Supprimer d'abord les messages liés
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);

    // Maintenant, supprimer l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);

    // Détruire la session
    session_destroy();

    // Rediriger vers la page d'accueil après suppression
    header("Location: index.php?success=compte_supprime");
    exit();
} catch (PDOException $e) {
    die("Erreur lors de la suppression du compte : " . $e->getMessage());
}
?>
