<?php
// ✅ Activer le mode débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db_connect.php';

$message = ""; // Variable pour stocker le message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $resetLink = "https://ambrosiarose.404cahorsfound.fr/reset_form.php?token=$token";

            // ✅ Enregistrer le token et expiration dans la base de données
            $stmt = $pdo->prepare('
                UPDATE utilisateurs 
                SET reset_token = :token, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                WHERE id_utilisateur = :id
            ');
            $stmt->execute(['token' => $token, 'id' => $user['id_utilisateur']]);

            $mail = new PHPMailer(true);

            try {
                // ✅ Configuration SMTP avec Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ambrosiaroseadm@gmail.com';
                $mail->Password = 'ooro xsmu rnef bsdf'; // Remplacez par le mot de passe d'application
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // ✅ Assurer l'encodage correct des caractères spéciaux
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

                // ✅ Configuration de l'expéditeur
                $mail->setFrom('ambrosiaroseadm@gmail.com', 'Ambrosia Rose');
                $mail->addReplyTo('support@ambrosiarose.404cahorsfound.fr', 'Support Ambrosia Rose');
                $mail->addAddress($email);

                // ✅ Configuration du contenu de l'email (HTML)
                $mail->isHTML(true);
                $mail->Subject = '🔑 Réinitialisation de votre mot de passe';
                $mail->Body = "
                    <h2>Demande de réinitialisation de mot de passe</h2>
                    <p>Bonjour,</p>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                    <p><a href='$resetLink' style='background:#007BFF;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>Réinitialiser mon mot de passe</a></p>
                    <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                    <p>Cordialement,<br>L'équipe Ambrosia Rose.</p>
                ";

                $mail->AltBody = "Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe : $resetLink";

                // ✅ Envoi de l'email et affichage du message
                if ($mail->send()) {
                    $message = "<p style='color: green;'>Un email de réinitialisation a été envoyé, vérifier vos spams!.</p>";
                } else {
                    $message = "<p style='color: red;'>Erreur lors de l'envoi de l'email.</p>";
                }
            } catch (Exception $e) {
                $message = "<p style='color: red;'>Erreur SMTP : " . $mail->ErrorInfo . "</p>";
            }
        } else {
            $message = "<p style='color: red;'>Cet email n'existe pas dans notre base.</p>";
        }
    } else {
        $message = "<p style='color: red;'>Veuillez entrer une adresse email valide.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation de mot de passe</title>

    <script type="text/javascript">
        // Redirection après 5 secondes
        setTimeout(function() {
            window.location.href = "https://ambrosiarose.404cahorsfound.fr/connexion.php"; // Redirection vers la page de connexion
        }, 5000); // 5000ms = 5 secondes
    </script>

</head>
<body>
    <?php 
    if (!empty($message)) {
        echo $message;
    }
    ?>
</body>
</html>

