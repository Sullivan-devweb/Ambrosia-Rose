<?php
// Démarrer la session
session_start();

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le fichier de connexion à la base de données
include 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Si la requête est AJAX, renvoyer une réponse JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(["success" => false, "message" => "Session expirée. Veuillez vous reconnecter."]);
        exit;
    } else {
        // Rediriger vers la page de connexion
        header('Location: connexion.php');
        exit;
    }
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

try {
    // Vérifier que la méthode de requête est POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Requête invalide.");
    }

    // Changement de mot de passe
    if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Vérifier que les nouveaux mots de passe correspondent
        if ($new_password !== $confirm_password) {
            throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
        }

        // Vérifier le mot de passe actuel
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['mot_de_passe'])) {
            throw new Exception("L'ancien mot de passe est incorrect.");
        }

        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id");
        $stmt->execute([
            'mot_de_passe' => password_hash($new_password, PASSWORD_DEFAULT),
            'id' => $user_id
        ]);

        // Répondre en JSON pour les requêtes AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(["success" => true, "message" => "Mot de passe modifié avec succès."]);
            exit;
        } else {
            // Rediriger vers la page de profil avec un message de succès
            header('Location: editprofil.php?success=password');
            exit;
        }
    }

    // Récupérer les données POST
    $description = isset($_POST['description']) ? trim($_POST['description']) : "";
    $pays = trim($_POST['pays_residence'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $recherche = trim($_POST['recherche'] ?? '');
    $new_password = !empty($_POST['new_password']) ? password_hash($_POST['new_password'], PASSWORD_BCRYPT) : null;

    // Récupérer les nouvelles données
    $situation_amoureuse = $_POST['situation_amoureuse'] ?? '';
    $enfants = $_POST['enfants'] ?? '';
    $souhaite_enfants = $_POST['souhaite_enfants'] ?? '';
    $localisation_importante = $_POST['localisation_importante'] ?? '';

    // Personnalité et valeurs
    $traits_personnalite = isset($_POST['traits_personnalite']) ? implode(',', $_POST['traits_personnalite']) : '';
    $approche_relation = $_POST['approche_relation'] ?? '';
    $valeurs_relation = isset($_POST['valeurs_relation']) ? implode(',', $_POST['valeurs_relation']) : '';

    // Centres d'intérêt et style de vie
    $passions = isset($_POST['passions']) ? implode(',', $_POST['passions']) : '';
    $sortie_ou_maison = $_POST['sortie_ou_maison'] ?? '';
    $activite_rdv = $_POST['activite_rdv'] ?? '';
    $relation_avec_sport = $_POST['relation_avec_sport'] ?? '';
    $relation_avec_technologie = $_POST['relation_avec_technologie'] ?? '';

    // Objectifs relationnels
    $type_relation = $_POST['type_relation'] ?? '';
    $importance_engagement = $_POST['importance_engagement'] ?? '';
    $rythme_relation = $_POST['rythme_relation'] ?? '';
    $relation_distance = $_POST['relation_distance'] ?? '';

    // Mode de communication
    $moyen_communication = $_POST['moyen_communication'] ?? '';
    $frequence_communication = $_POST['frequence_communication'] ?? '';

    // Vérification des champs obligatoires
    if (empty($pays) || empty($ville) || empty($recherche)) {
        throw new Exception("Tous les champs obligatoires doivent être remplis.");
    }

    // Gestion de l'image de profil
    $image_profil = $_POST['current_image'];
    $target_dir_profile = "uploads/images/";
    if (!is_dir($target_dir_profile)) mkdir($target_dir_profile, 0775, true);

    if (!empty($_FILES['image_profil']['name'])) {
        $file_name = uniqid() . '_' . basename($_FILES['image_profil']['name']);
        $target_file = $target_dir_profile . $file_name;

        if (move_uploaded_file($_FILES['image_profil']['tmp_name'], $target_file)) {
            $image_profil = $target_file;
        }
    }

    // Gestion de la galerie d'images
    $stmt = $pdo->prepare("SELECT galerie_images FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $existing_images = !empty($row['galerie_images']) ? explode(',', $row['galerie_images']) : [];

    $target_dir_gallery = "uploads/gallery/";
    if (!is_dir($target_dir_gallery)) mkdir($target_dir_gallery, 0775, true);

    $new_gallery_images = [];
    if (!empty($_FILES['galerie_images']['name'][0])) {
        foreach ($_FILES['galerie_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['galerie_images']['error'][$key] === UPLOAD_ERR_OK) {
                $gallery_file_name = uniqid() . '_' . basename($_FILES['galerie_images']['name'][$key]);
                $gallery_target_file = $target_dir_gallery . $gallery_file_name;

                if (move_uploaded_file($tmp_name, $gallery_target_file)) {
                    $new_gallery_images[] = $gallery_file_name;
                }
            }
        }
    }

    $all_images = array_merge($existing_images, $new_gallery_images);
    $galerie_images_str = implode(",", array_filter($all_images));

    // Mise à jour des informations de l'utilisateur
    $query = $pdo->prepare("
        UPDATE utilisateurs 
        SET pays_residence = :pays, 
            ville = :ville, 
            recherche = :recherche, 
            description = :description, 
            image_profil = :image_profil, 
            galerie_images = :galerie_images,
            situation_amoureuse = :situation_amoureuse,
            enfants = :enfants,
            souhaite_enfants = :souhaite_enfants,
            localisation_importante = :localisation_importante,
            traits_personnalite = :traits_personnalite,
            approche_relation = :approche_relation,
            valeurs_relation = :valeurs_relation,
            passions = :passions,
            sortie_ou_maison = :sortie_ou_maison,
            activite_rdv = :activite_rdv,
            relation_avec_sport = :relation_avec_sport,
            relation_avec_technologie = :relation_avec_technologie,
            type_relation = :type_relation,
            importance_engagement = :importance_engagement,
            rythme_relation = :rythme_relation,
            relation_distance = :relation_distance,
            moyen_communication = :moyen_communication,
            frequence_communication = :frequence_communication
        WHERE id_utilisateur = :id
    ");

    $params = [
        'pays' => $pays,
        'ville' => $ville,
        'recherche' => $recherche,
        'description' => $description,
        'image_profil' => $image_profil,
        'galerie_images' => $galerie_images_str,
        'situation_amoureuse' => $situation_amoureuse,
        'enfants' => $enfants,
        'souhaite_enfants' => $souhaite_enfants,
        'localisation_importante' => $localisation_importante,
        'traits_personnalite' => $traits_personnalite,
        'approche_relation' => $approche_relation,
        'valeurs_relation' => $valeurs_relation,
        'passions' => $passions,
        'sortie_ou_maison' => $sortie_ou_maison,
        'activite_rdv' => $activite_rdv,
        'relation_avec_sport' => $relation_avec_sport,
        'relation_avec_technologie' => $relation_avec_technologie,
        'type_relation' => $type_relation,
        'importance_engagement' => $importance_engagement,
        'rythme_relation' => $rythme_relation,
        'relation_distance' => $relation_distance,
        'moyen_communication' => $moyen_communication,
        'frequence_communication' => $frequence_communication,
        'id' => $user_id
    ];

    // Exécuter la requête de mise à jour
    $query->execute($params);

    // Mettre à jour la session avec les nouvelles valeurs
    $_SESSION['pays_residence'] = $pays;
    $_SESSION['ville'] = $ville;
    $_SESSION['recherche'] = $recherche;
    $_SESSION['description'] = $description;
    $_SESSION['image_profil'] = $image_profil;
    $_SESSION['galerie_images'] = $galerie_images_str;
    $_SESSION['situation_amoureuse'] = $situation_amoureuse;
    $_SESSION['enfants'] = $enfants;
    $_SESSION['souhaite_enfants'] = $souhaite_enfants;
    $_SESSION['localisation_importante'] = $localisation_importante;
    $_SESSION['traits_personnalite'] = $traits_personnalite;
    $_SESSION['approche_relation'] = $approche_relation;
    $_SESSION['valeurs_relation'] = $valeurs_relation;
    $_SESSION['passions'] = $passions;
    $_SESSION['sortie_ou_maison'] = $sortie_ou_maison;
    $_SESSION['activite_rdv'] = $activite_rdv;
    $_SESSION['relation_avec_sport'] = $relation_avec_sport;
    $_SESSION['relation_avec_technologie'] = $relation_avec_technologie;
    $_SESSION['type_relation'] = $type_relation;
    $_SESSION['importance_engagement'] = $importance_engagement;
    $_SESSION['rythme_relation'] = $rythme_relation;
    $_SESSION['relation_distance'] = $relation_distance;
    $_SESSION['moyen_communication'] = $moyen_communication;
    $_SESSION['frequence_communication'] = $frequence_communication;

    // Réponse AJAX ou redirection
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(["success" => true, "message" => "Profil mis à jour avec succès."]);
    } else {
        header('Location: editprofil.php?success=1');
    }
    exit;

} catch (Exception $e) {
    // Gestion des erreurs
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    } else {
        header('Location: editprofil.php?error=' . urlencode($e->getMessage()));
    }
    exit;
}
?>