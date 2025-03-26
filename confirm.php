<?php
include('db_connect.php');

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Vérifier si le token existe
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE confirm_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Activer le compte
        $stmt = $pdo->prepare("UPDATE utilisateurs SET email_confirmed = 1, confirm_token = NULL WHERE id_utilisateur = ?");
        $stmt->execute([$user['id_utilisateur']]);

        echo "<h3>✅ Votre email a été confirmé avec succès ! Vous pouvez maintenant vous connecter.</h3>";
        echo "<a href='connexion.php'>Se connecter</a>";
        
        // Redirection après 5 secondes
        echo "<script type='text/javascript'>
                setTimeout(function() {
                    window.location.href = 'connexion.php';
                }, 5000); // 5000ms = 5 secondes
              </script>";

    } else {
        echo "<h3>❌ Lien de confirmation invalide ou expiré.</h3>";
    }
} else {
    echo "<h3>❌ Aucun token fourni.</h3>";
}
?>

