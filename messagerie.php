<?php
// Activer le rapport d'erreurs et rediriger vers un fichier log
ini_set('log_errors', 1);
ini_set('error_log', 'log.txt');
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once 'session_handler.php'; // Gestion de session et inactivité
require_once 'db_connect.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer le rôle de l'utilisateur
$role_sql = "SELECT role FROM utilisateurs WHERE id_utilisateur = :user_id";
$role_stmt = $pdo->prepare($role_sql);
$role_stmt->bindParam(':user_id', $user_id);
$role_stmt->execute();
$user_role = $role_stmt->fetchColumn(); // Récupère le rôle (premium ou user)

// Définir la limite de messages pour les utilisateurs standard
$message_limit = 5; // Par exemple, 5 messages par jour

// Compter le nombre de messages envoyés aujourd'hui par l'utilisateur
$count_sql = "SELECT COUNT(*) FROM messages 
              WHERE id_utilisateur = :user_id 
              AND DATE(date_envoi) = CURDATE() 
              AND is_deleted = 0"; // Ignorer les messages supprimés
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindParam(':user_id', $user_id);
$count_stmt->execute();
$message_count = $count_stmt->fetchColumn();

// Vérifier si l'utilisateur a atteint la limite
$can_send_message = !($user_role === 'user' && $message_count >= $message_limit);

// Récupérer le contact_id depuis l'URL
$contact_id = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : 0;

// Récupérer les informations du contact sélectionné
$selected_contact = null;
if ($contact_id) {
    try {
        $contact_sql = "SELECT id_utilisateur, prenom, image_profil, is_online, derniere_connexion 
                        FROM utilisateurs 
                        WHERE id_utilisateur = :contact_id";
        $contact_stmt = $pdo->prepare($contact_sql);
        $contact_stmt->bindParam(':contact_id', $contact_id);
        $contact_stmt->execute();
        $selected_contact = $contact_stmt->fetch(PDO::FETCH_ASSOC);

        // Si le contact n'existe pas, rediriger
        if (!$selected_contact) {
            header("Location: contacts.php");
            exit;
        }

        // Formater la dernière connexion
        if ($selected_contact['is_online'] == 0) {
            $last_connection = new DateTime($selected_contact['derniere_connexion']);
            $now = new DateTime();
            $interval = $now->diff($last_connection);
            if ($interval->y > 0) {
                $last_connection_text = $interval->y . ' an(s)';
            } elseif ($interval->m > 0) {
                $last_connection_text = $interval->m . ' mois';
            } elseif ($interval->d > 0) {
                $last_connection_text = $interval->d . ' jour(s)';
            } elseif ($interval->h > 0) {
                $last_connection_text = $interval->h . ' heure(s)';
            } elseif ($interval->i > 0) {
                $last_connection_text = 'Il y a ' . $interval->i . ' minute(s)';
            } else {
                $last_connection_text = 'Il y a ' . $interval->s . ' seconde(s)';
            }
        } else {
            $last_connection_text = 'En ligne';
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du contact : " . $e->getMessage());
    }
}

// Gestion de l'envoi de message
if ($_SERVER["REQUEST_METHOD"] == "POST" && $can_send_message) {
    $contenu = $_POST['message'];
    $type = 'texte';

    // Gestion de l'upload de fichiers
    if (!empty($_FILES['media']['name'])) {
        $fileName = $_FILES['media']['name'];
        $fileTmpName = $_FILES['media']['tmp_name'];
        $fileType = $_FILES['media']['type'];
        $filePath = 'uploads/' . basename($fileName);

        if (move_uploaded_file($fileTmpName, $filePath)) {
            if (strpos($fileType, 'image') !== false) {
                $type = 'image';
            } elseif (strpos($fileType, 'video') !== false) {
                $type = 'video';
            } elseif (strpos($fileType, 'audio') !== false) {
                $type = 'audio';
            }
            $contenu = $filePath;
        }
    }

    // Gestion des messages vocaux
    if (isset($_FILES['voiceMessage'])) {
        $fileTmpName = $_FILES['voiceMessage']['tmp_name'];
        $filePath = 'uploads/' . 'voice_' . time() . '.mp3';

        if (move_uploaded_file($fileTmpName, $filePath)) {
            $type = 'audio';
            $contenu = $filePath;
        }
    }

    // Insérer le message dans la base de données
    if (!empty($contenu)) {
        try {
            $sql = "INSERT INTO messages (id_utilisateur, contenu, type, destinataire_id) 
                    VALUES (:user_id, :contenu, :type, :contact_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':contact_id', $contact_id);
            $stmt->execute();

            // Redirection pour éviter l'envoi multiple des messages lors du rafraîchissement
            header("Location: messagerie.php?contact_id=$contact_id");
            exit;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'envoi du message : " . $e->getMessage());
        }
    }
}

// Gestion de la suppression d'un message
if (isset($_POST['delete_message'])) {
    $message_id = intval($_POST['message_id']);
    try {
        $sql = "UPDATE messages SET is_deleted = 1 WHERE id = :message_id AND id_utilisateur = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':message_id', $message_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        // Rediriger pour éviter la soumission multiple
        header("Location: messagerie.php?contact_id=$contact_id");
        exit;
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression du message : " . $e->getMessage());
    }
}

// Récupérer les conversations existantes
$contacts = [];
try {
    $contacts_sql = "SELECT u.id_utilisateur, u.prenom, u.image_profil, MAX(m.date_envoi) as last_message_date, 
                     (SELECT contenu FROM messages WHERE (id_utilisateur = u.id_utilisateur AND destinataire_id = :user_id) 
                      OR (id_utilisateur = :user_id AND destinataire_id = u.id_utilisateur) 
                      ORDER BY date_envoi DESC LIMIT 1) as contenu,
                     (SELECT id_utilisateur FROM messages WHERE (id_utilisateur = u.id_utilisateur AND destinataire_id = :user_id) 
                      OR (id_utilisateur = :user_id AND destinataire_id = u.id_utilisateur) 
                      ORDER BY date_envoi DESC LIMIT 1) as last_sender_id
                     FROM utilisateurs u 
                     LEFT JOIN messages m ON (u.id_utilisateur = m.id_utilisateur AND m.destinataire_id = :user_id) 
                     OR (u.id_utilisateur = m.destinataire_id AND m.id_utilisateur = :user_id) 
                     WHERE u.id_utilisateur != :user_id AND EXISTS (
                         SELECT 1 FROM messages WHERE (id_utilisateur = u.id_utilisateur AND destinataire_id = :user_id) 
                         OR (id_utilisateur = :user_id AND destinataire_id = u.id_utilisateur)
                     )
                     GROUP BY u.id_utilisateur 
                     ORDER BY last_message_date DESC";
    $contacts_stmt = $pdo->prepare($contacts_sql);
    $contacts_stmt->bindParam(':user_id', $user_id);
    $contacts_stmt->execute();
    $contacts = $contacts_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des contacts : " . $e->getMessage());
}

// Récupérer les messages échangés entre l'utilisateur connecté et le contact sélectionné
$messages = [];
if ($contact_id) {
    try {
        $sql = "SELECT m.id, m.contenu, m.type, m.date_envoi, u.prenom, u.image_profil, u.id_utilisateur, u.is_online, u.derniere_connexion
                FROM messages m 
                JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur 
                WHERE (m.id_utilisateur = :user_id AND m.destinataire_id = :contact_id) 
                OR (m.id_utilisateur = :contact_id AND m.destinataire_id = :user_id)
                AND m.is_deleted = 0
                ORDER BY m.date_envoi ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':contact_id', $contact_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formater la dernière connexion pour chaque message
        foreach ($messages as &$message) {
            if ($message['is_online'] == 0) {
                $last_connection = new DateTime($message['derniere_connexion']);
                $now = new DateTime();
                $interval = $now->diff($last_connection);
                if ($interval->y > 0) {
                    $message['last_connection_text'] = $interval->y . ' an(s)';
                } elseif ($interval->m > 0) {
                    $message['last_connection_text'] = $interval->m . ' mois';
                } elseif ($interval->d > 0) {
                    $message['last_connection_text'] = $interval->d . ' jour(s)';
                } elseif ($interval->h > 0) {
                    $message['last_connection_text'] = $interval->h . ' heure(s)';
                } elseif ($interval->i > 0) {
                    $message['last_connection_text'] = 'Il y a ' . $interval->i . ' minute(s)';
                } else {
                    $message['last_connection_text'] = 'Il y a ' . $interval->s . ' seconde(s)';
                }
            } else {
                $message['last_connection_text'] = 'En ligne';
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des messages : " . $e->getMessage());
    }
}

// Marquer les messages comme lus lorsque l'utilisateur consulte la conversation
if ($contact_id) {
    try {
        $stmt = $pdo->prepare("UPDATE messages SET vu = 1 WHERE destinataire_id = :user_id AND id_utilisateur = :contact_id AND vu = 0");
        $stmt->execute(['user_id' => $user_id, 'contact_id' => $contact_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour des messages lus : " . $e->getMessage());
    }
}

$has_messages_with_selected_contact = count($messages) > 0;
$has_contacts = count($contacts) > 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="messagerie.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="nav.css">
</head>

<body>
    <?php include("nav.php"); ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Boîte de réception -->
            <?php if ($has_contacts || $contact_id) : ?>
                <div class="col-md-4 inbox bg-light p-3 border-right">
                    <h4>Boîte de Réception</h4>
                    <ul class="list-group" id="inbox-list">
                        <?php foreach ($contacts as $contact) : ?>
                            <?php
                            $last_sender = $contact['last_sender_id'] == $user_id ? 'Moi' : htmlspecialchars($contact['prenom'], ENT_QUOTES, 'UTF-8');
                            ?>
                            <li class="list-group-item d-flex align-items-center <?= $contact['id_utilisateur'] == $contact_id ? 'selected' : '' ?>">
                                <a href="messagerie.php?contact_id=<?= $contact['id_utilisateur']; ?>" class="d-flex align-items-center w-100 text-dark text-decoration-none">
                                    <img src="<?= htmlspecialchars($contact['image_profil'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profil" class="img-thumbnail">
                                    <div class="ml-3">
                                        <strong><?= htmlspecialchars($contact['prenom'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <p class="mb-0 text-muted"><?= $last_sender; ?>: <?= htmlspecialchars($contact['contenu'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <small class="text-muted" data-date="<?= htmlspecialchars($contact['last_message_date']); ?>">
                                            <?= htmlspecialchars($contact['last_message_date']); ?>
                                        </small>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Zone de messagerie -->
            <div class="col-md-8 p-0">
                <div class="messagerie d-flex flex-column">
                    <!-- En-tête de la messagerie -->
                    <div class="header bg-primary text-white p-3 d-flex align-items-center">
                        <button class="btn btn-light btn-sm" onclick="window.history.back();">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <?php if (isset($selected_contact)) : ?>
                            <div class="d-flex align-items-center ml-2">
                                <img src="<?= htmlspecialchars($selected_contact['image_profil'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profil" class="img-thumbnail rounded-circle" style="width: 40px; height: 40px;">
                                <div class="ml-2">
                                    <h2 class="d-inline-block mb-0"><?= htmlspecialchars($selected_contact['prenom'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <p class="profile-status">
                                        <?php if ($selected_contact['is_online'] == 1): ?>
                                            <span class="online-status"><i class="fas fa-circle"></i> En ligne</span>
                                        <?php else: ?>
                                            <span class="last-connection"><strong>Dernière connexion:</strong> <?= htmlspecialchars($last_connection_text, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php else : ?>
                            <h2 class="d-inline-block ml-2 mb-0">Messagerie</h2>
                        <?php endif; ?>
                    </div>

                    <!-- Messages -->
                    <div class="messages p-3 flex-grow-1 overflow-auto">
                        <?php if ($has_messages_with_selected_contact) : ?>
                            <?php foreach ($messages as $row) : ?>
                                <div class="message-container <?= $row['id_utilisateur'] == $user_id ? 'user' : 'other' ?>">
                                    <div class="message-header">
                                        <span class="name"><?= htmlspecialchars($row['prenom'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($row['id_utilisateur'] != $user_id) : ?>
                                            <a href="detailprofile.php?id=<?= $row['id_utilisateur']; ?>">
                                                <img src="<?= htmlspecialchars($row['image_profil'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profil" class="img-thumbnail ml-2">
                                            </a>
                                        <?php else : ?>
                                            <img src="<?= htmlspecialchars($row['image_profil'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profil" class="img-thumbnail ml-2">
                                        <?php endif; ?>
                                    </div>
                                    <div class="message <?= $row['id_utilisateur'] == $user_id ? 'user' : 'other' ?>" data-id="<?= $row['id']; ?>">
                                        <div class="message-bubble d-flex justify-content-between">
                                            <div class="message-content">
                                                <?php
                                                if ($row['type'] == 'texte') {
                                                    echo nl2br(htmlspecialchars($row['contenu'], ENT_QUOTES, 'UTF-8'));
                                                } elseif ($row['type'] == 'image') {
                                                    echo '<img src="' . htmlspecialchars($row['contenu'], ENT_QUOTES, 'UTF-8') . '" alt="Image" class="img-fluid">';
                                                } elseif ($row['type'] == 'video') {
                                                    echo '<video src="' . htmlspecialchars($row['contenu'], ENT_QUOTES, 'UTF-8') . '" controls class="img-fluid"></video>';
                                                } elseif ($row['type'] == 'audio') {
                                                    echo '<audio src="' . htmlspecialchars($row['contenu'], ENT_QUOTES, 'UTF-8') . '" controls class="w-100"></audio>';
                                                }
                                                ?>
                                            </div>
                                            <?php if ($row['id_utilisateur'] == $user_id && $row['is_deleted'] == 0) : ?>
                                                <div class="actions">
                                                    <button class="edit" title="Modifier"><i class="fas fa-pencil-alt"></i></button>
                                                    <button class="delete" title="Supprimer" data-message-id="<?= $row['id']; ?>"><i class="fas fa-trash"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="aucun-message">Aucun message pour l'instant...</p>
                        <?php endif; ?>
                    </div>

                    <!-- Zone de saisie -->
                    <div class="zone-saisie p-3 bg-white border-top">
                        <form action="messagerie.php?contact_id=<?= $contact_id ?>" method="POST" enctype="multipart/form-data" class="d-flex w-100 align-items-center" <?= $can_send_message ? '' : 'onsubmit="return false;"' ?>>
                            <input type="text" name="message" id="message-input" placeholder="Écrivez un message..." class="form-control" <?= $can_send_message ? '' : 'disabled' ?>>
                            <button type="submit" class="btn btn-primary" <?= $can_send_message ? '' : 'disabled' ?>>Envoyer</button>
                            <div class="options ml-2 d-flex align-items-center position-relative">
                                <label for="photo-input" class="photo-btn btn btn-light mr-2" title="Envoyer une photo ou vidéo" <?= $can_send_message ? '' : 'style="pointer-events: none;"' ?>>
                                    <img src="./img/1375157.png" alt="Photo" />
                                    <input type="file" id="photo-input" name="media" accept="image/*,video/*" hidden <?= $can_send_message ? '' : 'disabled' ?>>
                                </label>
                                <button type="button" class="voice-btn btn btn-light" title="Enregistrer un message vocal" <?= $can_send_message ? '' : 'disabled' ?>>
                                    <img src="./img/88634.png" alt="Message Vocal" />
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pop-up pour la limite de messages -->
    <div id="limit-popup" class="modal fade" tabindex="-1" aria-labelledby="limitPopupLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="limitPopupLabel">Limite de messages atteinte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Vous avez atteint votre limite de messages pour aujourd'hui. Devenez premium pour envoyer des messages illimités !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Plus tard</button>
                    <a href="premium.php" class="btn btn-primary">Devenir premium</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    <audio id="audio-preview" controls class="d-none"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (!$can_send_message) : ?>
                var limitPopup = new bootstrap.Modal(document.getElementById('limit-popup'));
                limitPopup.show();
            <?php endif; ?>
        });
    </script>
    <script src="messagerie.js" defer></script>
</body>

</html>