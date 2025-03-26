<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];

    // Vérifier si le token est valide
    $stmt = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE reset_token = :token AND token_expiry > NOW()');
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('Lien de réinitialisation invalide ou expiré.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    // Mise à jour du mot de passe
    $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = :password, reset_token = NULL, token_expiry = NULL WHERE reset_token = :token');
    $stmt->execute([
        'password' => $newPassword,
        'token' => $token
    ]);

    echo 'Votre mot de passe a été réinitialisé avec succès.';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Définir un nouveau mot de passe</title>
    <!-- Inclure Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="reset_password.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="form-container p-5 border rounded shadow">
            <h2 class="text-center">Nouveau mot de passe</h2>
            <form action="reset_form.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" class="form-control" name="new_password" id="new_password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Réinitialiser</button>
            </form>
        </div>
    </div>

    <!-- Inclure Bootstrap JS et ses dépendances -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
