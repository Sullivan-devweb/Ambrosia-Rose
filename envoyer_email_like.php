<?php
// ✅ Activer le mode débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); // ✅ Assurer la sortie en JSON

// ✅ Création du fichier de logs
$log_file = "debug_log.txt";
file_put_contents($log_file, "✅ Début du script\n", FILE_APPEND);

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db_connect.php';
// ✅ Vérifier si les données POST sont bien envoyées
if (!isset($_POST['user_id']) || !isset($_POST['sender_id'])) {
    file_put_contents($log_file, "❌ Erreur : Données POST manquantes\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
    exit();
}

$user_id = (int) $_POST['user_id']; // ID du destinataire du like
$sender_id = (int) $_POST['sender_id']; // ID de l'expéditeur du like

// ✅ Vérifier si les IDs sont valides (pas vides ni 0)
if ($user_id <= 0 || $sender_id <= 0) {
    file_put_contents($log_file, "❌ Erreur : ID utilisateur ou sender_id invalide. user_id={$user_id}, sender_id={$sender_id}\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'IDs invalides']);
    exit();
}

file_put_contents($log_file, "✅ Données reçues : user_id={$user_id}, sender_id={$sender_id}\n", FILE_APPEND);

// ✅ Vérifier la connexion à la base de données
if (!$pdo) {
    file_put_contents($log_file, "❌ Erreur de connexion à la base de données\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion BDD']);
    exit();
}

// 🔹 Vérifier si le like vient d’être ajouté ou supprimé
$stmtCheckLikeBefore = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE id_utilisateur = :sender_id AND id_cible = :user_id");
$stmtCheckLikeBefore->execute(['sender_id' => $sender_id, 'user_id' => $user_id]);
$likeExistedBefore = $stmtCheckLikeBefore->fetchColumn();

// 🔹 Pause pour éviter des requêtes exécutées trop rapidement
sleep(1);

// 🔹 Vérifier si le like existe après la requête
$stmtCheckLikeAfter = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE id_utilisateur = :sender_id AND id_cible = :user_id");
$stmtCheckLikeAfter->execute(['sender_id' => $sender_id, 'user_id' => $user_id]);
$likeExistsAfter = $stmtCheckLikeAfter->fetchColumn();

// ✅ Vérifier si un like a été ajouté (même si l'utilisateur l'avait supprimé avant)
if ($likeExistsAfter == 1) {
    file_put_contents($log_file, "✅ Nouveau like détecté : Envoi de l'email...\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "❌ Aucun email envoyé : Soit le like existait déjà, soit il a été supprimé.\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Le like n’a pas été ajouté (déjà existant ou supprimé)']);
    exit();
}


// ✅ Vérifier si les données POST sont bien envoyées
if (!isset($_POST['user_id']) || !isset($_POST['sender_id'])) {
    file_put_contents($log_file, "❌ Erreur : Données POST manquantes\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
    exit();
}

$user_id = (int) $_POST['user_id']; // ID du destinataire du like
$sender_id = (int) $_POST['sender_id']; // ID de l'expéditeur du like

// ✅ Vérifier si les IDs sont valides (pas vides ni 0)
if ($user_id <= 0 || $sender_id <= 0) {
    file_put_contents($log_file, "❌ Erreur : ID utilisateur ou sender_id invalide. user_id={$user_id}, sender_id={$sender_id}\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'IDs invalides']);
    exit();
}

file_put_contents($log_file, "✅ Données reçues : user_id={$user_id}, sender_id={$sender_id}\n", FILE_APPEND);

// ✅ Vérifier la connexion à la base de données
if (!$pdo) {
    file_put_contents($log_file, "❌ Erreur de connexion à la base de données\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion BDD']);
    exit();
}

// 🔹 Vérifier si le like existe encore AVANT d'envoyer l'email
$stmtCheckLike = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE id_utilisateur = :sender_id AND id_cible = :user_id");
$stmtCheckLike->execute(['sender_id' => $sender_id, 'user_id' => $user_id]);
$likeExists = $stmtCheckLike->fetchColumn();

if ($likeExists == 0) {
    file_put_contents($log_file, "❌ Annulation de l'email : Le like n'existe plus. user_id={$user_id}, sender_id={$sender_id}\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Le like n’existe plus']);
    exit();
}

file_put_contents($log_file, "✅ Like confirmé, envoi de l'email en cours...\n", FILE_APPEND);


// ✅ Récupérer l'email et le prénom + nom du destinataire
try {
    $stmt = $pdo->prepare("SELECT email, prenom, nom FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        file_put_contents($log_file, "❌ Erreur : Utilisateur ID {$user_id} introuvable\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Utilisateur introuvable']);
        exit();
    }

    // ✅ Récupérer le prénom + nom de l'expéditeur
    $stmtSender = $pdo->prepare("SELECT prenom, nom FROM utilisateurs WHERE id_utilisateur = ?");
    $stmtSender->execute([$sender_id]);
    $sender = $stmtSender->fetch(PDO::FETCH_ASSOC);

    if (!$sender) {
        file_put_contents($log_file, "❌ Erreur : Expéditeur ID {$sender_id} introuvable\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Expéditeur introuvable']);
        exit();
    }

    file_put_contents($log_file, "✅ Utilisateurs trouvés : Destinataire={$user['email']}, Expéditeur={$sender['prenom']} {$sender['nom']}\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents($log_file, "❌ Erreur SQL : " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Erreur SQL']);
    exit();
}

// ✅ Envoi de l'email avec PHPMailer
$mail = new PHPMailer(true);
try {
    file_put_contents($log_file, "✅ Tentative d'envoi d'email à {$user['email']}\n", FILE_APPEND);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // ✅ Serveur SMTP Gmail
    $mail->SMTPAuth = true;
    $mail->Username = 'ambrosiaroseadm@gmail.com'; // ✅ Adresse Gmail utilisée
    $mail->Password = 'ooro xsmu rnef bsdf'; // ⚠️ DANGEREUX !
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ✅ STARTTLS pour Gmail
    $mail->Port = 587; // ✅ Port recommandé pour Gmail (587)

    // ✅ Encodage UTF-8 pour éviter les bugs d'affichage des caractères spéciaux
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $mail->setFrom('ambrosiaroseadm@gmail.com', 'Ambrosia Rose');
    $mail->addAddress($user['email']);
    $mail->addReplyTo('no-reply@ambrosiarose.404cahorsfound.fr', 'Ne pas répondre'); // ✅ Ajouter un reply-to pour éviter d'être bloqué comme spam

    $mail->Subject = "🔥 Vous avez reçu un like !";
    $mail->Body = "
    Bonjour " . htmlspecialchars($user['prenom'], ENT_QUOTES, 'UTF-8') . ",

    💖 " . htmlspecialchars($sender['prenom'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($sender['nom'], ENT_QUOTES, 'UTF-8') . " a aimé votre profil !

    👀 Connectez-vous pour voir qui c'est !
    
    👉 <a href='https://ambrosiarose.404cahorsfound.fr'>Cliquez ici</a>
    ";

    // ✅ Version texte brute pour compatibilité avec tous les clients mail
    $mail->AltBody = "Bonjour " . htmlspecialchars($user['prenom'], ENT_QUOTES, 'UTF-8') . ",\n\n" .
                     "💖 " . htmlspecialchars($sender['prenom'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($sender['nom'], ENT_QUOTES, 'UTF-8') . " a aimé votre profil !\n\n" .
                     "👀 Connectez-vous pour voir qui c'est !\n\n" .
                     "👉 https://ambrosiarose.404cahorsfound.fr";

    if ($mail->send()) {
        file_put_contents($log_file, "✅ Email envoyé avec succès à {$user['email']}\n", FILE_APPEND);
        echo json_encode(['status' => 'success']);
    } else {
        file_put_contents($log_file, "❌ Erreur d'envoi d'email : " . $mail->ErrorInfo . "\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Erreur envoi email']);
    }

} catch (Exception $e) {
    file_put_contents($log_file, "❌ Exception lors de l'envoi d'email : " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Erreur envoi email']);
}
?>