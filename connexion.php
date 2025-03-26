<?php
require_once 'db_connect.php';

// ✅ Démarrer la session si ce n’est pas encore fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            // 🔍 Recherche de l'utilisateur par email
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // ✅ Vérification du mot de passe
            if ($user && password_verify($password, $user['mot_de_passe'])) {

                // ❌ Si l'email n’est pas confirmé
                if ($user['email_confirmed'] == 0) {
                    header("Location: connexion.php?error=email_not_confirmed");
                    exit();
                }

                // 🔄 Régénérer l’ID de session pour sécurité
                session_regenerate_id(true);

                // ✅ Mise à jour de la dernière connexion + statut "en ligne"
                $stmt = $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW(), is_online = 1 WHERE id_utilisateur = :user_id");
                $stmt->execute(['user_id' => $user['id_utilisateur']]);

                // ✅ Supprimer ancienne session si elle existe
                $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $user['id_utilisateur']]);

                // ✅ Enregistrer la nouvelle session en BDD
                $sessionId = session_id();
                $stmt = $pdo->prepare("INSERT INTO sessions (session_id, user_id, last_activity) 
                                       VALUES (:session_id, :user_id, NOW())");
                $stmt->execute(['session_id' => $sessionId, 'user_id' => $user['id_utilisateur']]);

                // ✅ Stocker toutes les infos utilisateur dans $_SESSION
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['image_profil'] = $user['image_profil'];
                $_SESSION['pays_residence'] = $user['pays_residence'];
                $_SESSION['ville'] = $user['ville'];
                $_SESSION['recherche'] = $user['recherche'];
                $_SESSION['date_naissance'] = $user['date_naissance'];
                $_SESSION['description'] = html_entity_decode($user['description'], ENT_QUOTES, 'UTF-8');

                // 🧩 Infos supplémentaires de profil
                $_SESSION['situation_amoureuse'] = $user['situation_amoureuse'];
                $_SESSION['enfants'] = $user['enfants'];
                $_SESSION['souhaite_enfants'] = $user['souhaite_enfants'];
                $_SESSION['localisation_importante'] = $user['localisation_importante'];
                $_SESSION['traits_personnalite'] = $user['traits_personnalite'];
                $_SESSION['approche_relation'] = $user['approche_relation'];
                $_SESSION['valeurs_relation'] = $user['valeurs_relation'];
                $_SESSION['passions'] = $user['passions'];
                $_SESSION['sortie_ou_maison'] = $user['sortie_ou_maison'];
                $_SESSION['activite_rdv'] = $user['activite_rdv'];
                $_SESSION['relation_avec_sport'] = $user['relation_avec_sport'];
                $_SESSION['relation_avec_technologie'] = $user['relation_avec_technologie'];
                $_SESSION['type_relation'] = $user['type_relation'];
                $_SESSION['importance_engagement'] = $user['importance_engagement'];
                $_SESSION['rythme_relation'] = $user['rythme_relation'];
                $_SESSION['relation_distance'] = $user['relation_distance'];
                $_SESSION['moyen_communication'] = $user['moyen_communication'];
                $_SESSION['frequence_communication'] = $user['frequence_communication'];
                $_SESSION['galerie_images'] = $user['galerie_images'] ?? '';

                // ✅ Redirection vers la page d’accueil
                header("Location: accueil.php");
                exit();

            } else {
                // ❌ Identifiants invalides
                header("Location: connexion.php?error=invalid_credentials");
                exit();
            }
        } catch (PDOException $e) {
            // ❌ Erreur SQL
            echo "<script>alert('❌ Erreur de base de données : " . $e->getMessage() . "'); window.location.href='connexion.php';</script>";
        }
    } else {
        // ❌ Champs vides
        header("Location: connexion.php?error=empty_fields");
        exit();
    }
}
?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger text-center">
        <?php 
        // 🔔 Messages d’erreur
        switch ($_GET['error']) {
            case 'email_not_confirmed':
                echo "❌ Votre compte n'est pas encore confirmé. Veuillez vérifier vos emails.";
                break;
            case 'invalid_credentials':
                echo "❌ Email ou mot de passe incorrect.";
                break;
            case 'empty_fields':
                echo "❌ Veuillez remplir tous les champs.";
                break;
            default:
                echo "❌ Une erreur est survenue.";
        }
        ?>
    </div>
<?php endif; ?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>

    <!-- ✅ Styles & libs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="connexion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- 👁️ JS pour afficher/cacher le mot de passe -->
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-container p-4 shadow-lg rounded">
            <h1 class="text-center mb-4">Connexion</h1>

            <!-- 📥 Formulaire de connexion -->
            <form class="login-form" method="POST" action="connexion.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Entrez votre email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Entrez votre mot de passe" required>
                        <span class="input-group-text" onclick="togglePasswordVisibility()" style="cursor: pointer;">
                            <i class="fa fa-eye" id="eye-icon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>

            <!-- 🔗 Liens annexes -->
            <div class="text-center mt-3">
                <a href="reset_password.php" class="text-link">Mot de passe oublié ?</a> |
                <a href="inscription.php" class="text-link">Pas de compte ? Inscris-toi</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

