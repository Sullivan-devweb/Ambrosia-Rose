<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Config SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'admin@ambrosiarose.404cahorsfound.fr';
    $mail->Password = 'ChapiChapo@*DWWM24';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Email destinataire
    $mail->setFrom('admin@ambrosiarose.404cahorsfound.fr', 'Ambrosia Rose');
    $mail->addAddress('ton.email@gmail.com'); // Mets ton email ici pour tester

    // Contenu du mail
    $mail->isHTML(true);
    $mail->Subject = '🔧 Test SMTP Hostinger';
    $mail->Body    = '<h3>Ceci est un test d\'envoi via PHPMailer.</h3>';
    $mail->AltBody = 'Ceci est un test d\'envoi via PHPMailer en format texte.';

    // Envoyer
    if ($mail->send()) {
        echo "✅ Email envoyé avec succès !";
    } else {
        echo "❌ Erreur d'envoi : " . $mail->ErrorInfo;
    }
} catch (Exception $e) {
    echo "❌ Exception : " . $mail->ErrorInfo;
}
?>
