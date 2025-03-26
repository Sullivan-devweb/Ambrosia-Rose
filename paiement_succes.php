<?php
require_once 'session_handler.php'; // Gestion de session et inactivité

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php"); // Redirige vers la connexion si non connecté
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Réussi - Ambrosia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Styles spécifiques à cette page */
        body {
            font-family: 'Poppins', sans-serif;
            color: #001F54;
            background: linear-gradient(to right, #f8f9fa, #eef2f3);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            margin-top: 20px;
            text-align: center;
        }

        .success-message {
            color: #4caf50;
            font-size: 1.5em;
            margin-top: 20px;
        }

        .icon {
            font-size: 4em;
            color: #4caf50;
        }
    </style>
</head>
<body>
    <!-- Inclure la navigation -->
    <?php include 'nav.php'; ?>

    <!-- Contenu principal -->
    <main class="flex-grow-1 content-wrapper">
        <div class="container-lg">
            <h1>Paiement Réussi</h1>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <p class="success-message">Merci pour votre achat ! Vous êtes maintenant membre premium.</p>
            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
        </div>
    </main>

    <!-- Inclure le footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>