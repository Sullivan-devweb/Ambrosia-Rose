<?php
include('db_connect.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclure PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $recherche = $_POST['recherche'] ?? '';
    $email = $_POST['email'] ?? '';
    $mot_de_passe = password_hash($_POST['mot_de_passe'] ?? '', PASSWORD_DEFAULT);
    $pays_residence = $_POST['pays_residence'] ?? '';
    $ville = $_POST['ville'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_inscription = date('Y-m-d H:i:s');
    $role = 'user'; // Rôle par défaut
    $confirm_token = bin2hex(random_bytes(16)); // Générer un jeton de confirmation

    // Gestion des fichiers
    $uploads_dir = 'uploads';
    $image_profil_path = 'uploads/images/default_profile.png'; // Image par défaut
    $galerie_images_path = [];
    $galerie_images_dir = $uploads_dir . '/gallery/';

    // Créer les dossiers nécessaires
    if (!is_dir("$uploads_dir/images")) mkdir("$uploads_dir/images", 0777, true);
    if (!is_dir("$uploads_dir/gallery")) mkdir("$uploads_dir/gallery", 0777, true);

    try {
        // Vérifier si l'email existe déjà
        $check_email = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $check_email->execute([$email]);
        if ($check_email->rowCount() > 0) {
            $message = "❌ L'email est déjà utilisé.";
        } else {
            // Vérifier si un fichier a été téléchargé et si c'est une image
            if (!empty($_FILES['image_profil']['tmp_name'])) {
                $image_info = getimagesize($_FILES['image_profil']['tmp_name']);
                if ($image_info !== false) {
                    $image_profil_name = basename($_FILES['image_profil']['name']);
                    $image_profil_path = $uploads_dir . '/images/' . $image_profil_name;
                    if (!move_uploaded_file($_FILES['image_profil']['tmp_name'], $image_profil_path)) {
                        $message = "❌ Erreur lors du téléchargement de l'image de profil.";
                    }
                } else {
                    $message = "❌ Le fichier téléchargé n'est pas une image valide.";
                }
            }

            // Traiter les images de galerie
            if (!empty($_FILES['gallery_images']['tmp_name'][0])) {
                $total_files = count($_FILES['gallery_images']['tmp_name']);
                $max_files = min($total_files, 5); // Limite à 5 images

                for ($i = 0; $i < $max_files; $i++) {
                    $tmp_name = $_FILES['gallery_images']['tmp_name'][$i];
                    $image_name = basename($_FILES['gallery_images']['name'][$i]);
                    $image_path = $galerie_images_dir . $image_name;
                    if (move_uploaded_file($tmp_name, $image_path)) {
                        $galerie_images_path[] = $image_name;
                    } else {
                        $message = "❌ Erreur lors du téléchargement de l'image de galerie.";
                    }
                }
            }

            // Créer une chaîne avec les noms des images de la galerie
            $galerie_images_str = implode(",", $galerie_images_path);

            // Insérer les données dans la table utilisateurs
            $insert = $pdo->prepare("
                INSERT INTO utilisateurs 
                (nom, prenom, date_naissance, genre, recherche, image_profil, description, email, mot_de_passe, pays_residence, ville, date_inscription, galerie_images, role, confirm_token, email_confirmed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $result = $insert->execute([ 
                $nom, $prenom, $date_naissance, $genre, $recherche,
                $image_profil_path, $description, $email, $mot_de_passe, $pays_residence, $ville, $date_inscription, $galerie_images_str, $role, $confirm_token
            ]);

            if ($result) {
                // ✅ Envoyer un e-mail de confirmation
                $mail = new PHPMailer(true);

                try {
                    // ✅ Configuration SMTP avec Gmail
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ambrosiaroseadm@gmail.com';
                    $mail->Password = 'ooro xsmu rnef bsdf'; // Mot de passe d'application
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    // ✅ Assurer l'encodage correct des caractères spéciaux
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';

                    // ✅ Configuration de l'expéditeur
                    $mail->setFrom('ambrosiaroseadm@gmail.com', 'Ambrosia Rose');
                    $mail->addAddress($email, "$prenom $nom");

                    // ✅ Contenu de l'e-mail
                    $mail->isHTML(true);
                    $mail->Subject = '🎉 Confirmation de votre inscription';
                    $mail->Body = "
                        <h1>Bienvenue $prenom,</h1>
                        <p>Merci de vous être inscrit sur Ambrosia Rose. Veuillez cliquer sur le lien ci-dessous pour confirmer votre inscription :</p>
                        <p><a href='https://ambrosiarose.404cahorsfound.fr/confirm.php?token=$confirm_token' style='background:#007BFF;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>Confirmer mon inscription</a></p>
                        <p>Si vous n'êtes pas à l'origine de cette inscription, ignorez cet email.</p>
                        <p>À bientôt !<br>L'équipe Ambrosia Rose</p>
                    ";

                    // ✅ Envoi de l'e-mail
                    if ($mail->send()) {
                        $message = "✅ Un email de confirmation a été envoyé à $email. Vous devez valider votre compte avant de vous connecter.";
                    } else {
                        $message = "❌ Erreur lors de l'envoi de l'email.";
                    }
                } catch (Exception $e) {
                    $message = "❌ Exception SMTP : " . $mail->ErrorInfo;
                }
            } else {
                $message = "❌ Une erreur est survenue lors de l'inscription.";
            }
        }
    } catch (PDOException $e) {
        $message = "❌ Erreur BDD : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Ambrosia Rose</title>
    <script type="text/javascript">
        // Fonction de redirection après 5 secondes
        setTimeout(function() {
            window.location.href = "https://ambrosiarose.404cahorsfound.fr/";
        }, 5000); // 5000ms = 5 secondes
    </script>
</head>
<body>

    <?php if (!empty($message)) echo "<p>$message</p>"; ?>

</body>
</html>