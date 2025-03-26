<?php
require_once 'session_handler.php'; // 🔐 Gestion de session et inactivité
require 'db_connect.php';

// ✅ Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Vérifier que toutes les informations nécessaires sont stockées en session
if (
    isset($_SESSION['prenom'], $_SESSION['nom'], $_SESSION['email'], $_SESSION['pays_residence'], 
          $_SESSION['ville'], $_SESSION['recherche'], $_SESSION['date_naissance'], 
          $_SESSION['image_profil'], $_SESSION['description'], $_SESSION['galerie_images'])
) {
    // ✅ Récupération des données de session
    $prenom = $_SESSION['prenom'];
    $nom = $_SESSION['nom'];
    $email = $_SESSION['email'];
    $pays_residence = $_SESSION['pays_residence'];
    $ville = $_SESSION['ville'];
    $recherche = $_SESSION['recherche'];
    setlocale(LC_TIME, 'fr_FR.UTF-8');
    $date_naissance = strftime("%d %B %Y", strtotime($_SESSION['date_naissance']));
    $image_profil = $_SESSION['image_profil'];
    $description = $_SESSION['description'];
    $galerie_images = !empty($_SESSION['galerie_images']) ? explode(',', $_SESSION['galerie_images']) : [];

    // 🧩 Infos supplémentaires
    $situation_amoureuse = $_SESSION['situation_amoureuse'] ?? '';
    $enfants = $_SESSION['enfants'] ?? '';
    $souhaite_enfants = $_SESSION['souhaite_enfants'] ?? '';
    $localisation_importante = $_SESSION['localisation_importante'] ?? '';

    $traits_personnalite = $_SESSION['traits_personnalite'] ?? '';
    $approche_relation = $_SESSION['approche_relation'] ?? '';
    $valeurs_relation = $_SESSION['valeurs_relation'] ?? '';
    $passions = $_SESSION['passions'] ?? '';

    $sortie_ou_maison = $_SESSION['sortie_ou_maison'] ?? '';
    $activite_rdv = $_SESSION['activite_rdv'] ?? '';
    $relation_avec_sport = $_SESSION['relation_avec_sport'] ?? '';
    $relation_avec_technologie = $_SESSION['relation_avec_technologie'] ?? '';

    $type_relation = $_SESSION['type_relation'] ?? '';
    $importance_engagement = $_SESSION['importance_engagement'] ?? '';
    $rythme_relation = $_SESSION['rythme_relation'] ?? '';
    $relation_distance = $_SESSION['relation_distance'] ?? '';

    $moyen_communication = $_SESSION['moyen_communication'] ?? '';
    $frequence_communication = $_SESSION['frequence_communication'] ?? '';
} else {
    // ❌ Rediriger si les données nécessaires sont absentes
    header('Location: connexion.php');
    exit;
}
?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer le Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="editprofil.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
</head>
<?php include("nav.php"); ?>
<body class="edit-profile-page">
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success text-center">
        ✅ Profil mis à jour avec succès !
    </div>
    <?php endif; ?>
    <section class="container py-5">
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            
            <!--Image de profil-->
    <h2 class="text-center mb-4">Votre photo de profil</h2>
<div class="mb-4 text-center">
    <!-- Image cliquable qui ouvre la modale Bootstrap -->
    <a href="#" data-bs-toggle="modal" data-bs-target="#profileImageModal">
        <img id="profile-preview" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; cursor: pointer;"
             src="<?php echo !empty($image_profil) ? $image_profil : 'uploads/images/default_profile.png'; ?>" 
             alt="Aperçu photo">
    </a>

    <div class="d-flex justify-content-center">
        <input type="file" id="image_profil" name="image_profil" accept="image/*" hidden>
        <label for="image_profil" class="btn btn-primary">Choisir une photo</label>
    </div>

    <input type="hidden" name="current_image" value="<?php echo $image_profil; ?>">
</div>

<!-- Modale Bootstrap pour afficher l'image en grand -->
<div class="modal fade" id="profileImageModal" tabindex="-1" aria-labelledby="profileImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileImageModalLabel">Photo de Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center">
                <img id="large-profile-img" src="<?php echo !empty($image_profil) ? $image_profil : 'uploads/images/default_profile.png'; ?>" 
                     alt="Image de profil" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>


<!--Informations personnelles-->

<!-- Informations personnelles -->
<h2 class="text-center mb-4">Informations personnelles</h2>
<div class="accordion" id="accordionPersonalInfo">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingPersonalInfo">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePersonalInfo" aria-expanded="false" aria-controls="collapsePersonalInfo">
                Afficher les informations personnelles
            </button>
        </h2>
        <div id="collapsePersonalInfo" class="accordion-collapse collapse" aria-labelledby="headingPersonalInfo" data-bs-parent="#accordionPersonalInfo">
            <div class="accordion-body">
                <!-- Contenu des informations personnelles -->
            </div>

                <div class="mb-3">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" id="prenom" name="prenom" class="form-control small-input" value="<?php echo htmlspecialchars($prenom); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" id="nom" name="nom" class="form-control small-input" value="<?php echo htmlspecialchars($nom); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control small-input" value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="date_naissance" class="form-label">Date de naissance</label>
                    <input type="text" id="date_naissance" name="date_naissance" class="form-control small-input" value="<?php echo htmlspecialchars($date_naissance); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="recherche" class="form-label">Recherche</label>
                    <select id="recherche" name="recherche" class="form-select small-input" required>
                        <option value="Homme" <?php echo ($recherche == 'Homme') ? 'selected' : ''; ?>>Homme</option>
                        <option value="Femme" <?php echo ($recherche == 'Femme') ? 'selected' : ''; ?>>Femme</option>
                        <option value="Homme et Femme" <?php echo ($recherche == 'Homme et Femme') ? 'selected' : ''; ?>>Homme et Femme</option>
                    </select>

                </div>
                <div class="mb-3">
                    <label for="pays_residence" class="form-label">Pays de résidence</label>
                    <select id="pays_residence" name="pays_residence" class="form-select small-input" required>
                        <option value="">Sélectionner un pays</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="ville" class="form-label">Ville</label>
                    <select id="ville" name="ville" class="form-select small-input" required>
                        <option value="">Sélectionner une ville</option>
                    </select>
                </div>
                <div class="mb-3 text-center">
                    <label for="description" class="form-label">Votre description</label>
                    <textarea id="description" name="description" class="form-control description-box" rows="3" placeholder="Décrivez-vous ici..."><?php echo htmlspecialchars($description); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<h2 class="text-center mb-4">Informations Supplémentaires</h2>

<div class="accordion" id="accordionAdditionalInfo">

    <!-- 🟢 1. Informations Générales -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingGeneral">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral" aria-expanded="false" aria-controls="collapseGeneral">
                Informations Générales
            </button>
        </h2>
        <div id="collapseGeneral" class="accordion-collapse collapse" aria-labelledby="headingGeneral" data-bs-parent="#accordionAdditionalInfo">
            <div class="accordion-body">
                <div class="mb-3">
                    <label class="form-label">Situation amoureuse actuelle</label>
                    <select name="situation_amoureuse" class="form-select"> 
                        <option value=""selected>Choisissez une option</option>
                        <?php 
                        $options = ["Célibataire", "Divorcé(e)", "Veuf(ve)", "Séparé(e)", "Autre"];
                        $selected = $_SESSION['situation_amoureuse'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Avez-vous des enfants ?</label>
                    <select name="enfants" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = ["Oui, vivant avec moi", "Oui, vivant ailleurs", "Non"];
                        $selected = $_SESSION['enfants'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Souhaitez-vous avoir des enfants ?</label>
                    <select name="souhaite_enfants" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = ["Oui", "Non", "Peut-être"];
                        $selected = $_SESSION['souhaite_enfants'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Votre localisation est-elle importante ?</label>
                    <select name="localisation_importante" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = [
                            "Oui, je préfère rencontrer quelqu’un proche de moi",
                            "Peu importe la distance",
                            "Je suis ouvert(e) aux relations à distance"
                        ];
                        $selected = $_SESSION['localisation_importante'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

        <!-- 🟢 2. Personnalité et Valeurs -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingPersonality">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePersonality" aria-expanded="false" aria-controls="collapsePersonality">
                Personnalité et Valeurs
            </button>
        </h2>
        <div id="collapsePersonality" class="accordion-collapse collapse" aria-labelledby="headingPersonality" data-bs-parent="#accordionAdditionalInfo">
            <div class="accordion-body">
                
                <!-- 🔹 Quel mot vous décrit le mieux ? (Max 3 choix) -->
                <div class="mb-3">
                    <label class="form-label">Quel mot vous décrit le mieux ? (choix multiple, max 3)</label>
                    <div class="row">
                        <?php 
                        $traits = ["Aventureux(se)", "Calme", "Créatif(ve)", "Drôle", "Empathique", "Extraverti(e)", "Introverti(e)", "Passionné(e)", "Réfléchi(e)"];
                        $selected_traits = isset($_SESSION['traits_personnalite']) ? explode(',', $_SESSION['traits_personnalite']) : [];

                        foreach ($traits as $trait) {
                            $checked = in_array($trait, $selected_traits) ? "checked" : "";
                            echo "
                            <div class='col-md-4'>
                                <div class='form-check'>
                                    <input class='form-check-input personality-checkbox' type='checkbox' name='traits_personnalite[]' value='$trait' id='trait-$trait' $checked>
                                    <label class='form-check-label' for='trait-$trait'>$trait</label>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- 🔹 Approche des relations -->
                <div class="mb-3">
                    <label class="form-label">Comment décririez-vous votre approche des relations ?</label>
                    <select name="approche_relation" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = [
                            "Je prends mon temps avant de m’engager",
                            "Je préfère les relations spontanées",
                            "Je suis très réfléchi(e) et analytique",
                            "Je suis émotionnel(le) et me laisse guider par mes sentiments"
                        ];
                        $selected = $_SESSION['approche_relation'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- 🔹 Valeurs importantes en relation (Max 3 choix) -->
                <div class="mb-3">
                    <label class="form-label">Quelles sont les valeurs les plus importantes pour vous dans une relation ? (choix multiple, max 3)</label>
                    <div class="row">
                        <?php 
                        $valeurs = ["La fidélité", "La communication", "L’honnêteté", "L’humour", "Le partage d’intérêts communs", "La tolérance", "L’indépendance"];
                        $selected_values = isset($_SESSION['valeurs_relation']) ? explode(',', $_SESSION['valeurs_relation']) : [];

                        foreach ($valeurs as $valeur) {
                            $checked = in_array($valeur, $selected_values) ? "checked" : "";
                            echo "
                            <div class='col-md-4'>
                                <div class='form-check'>
                                    <input class='form-check-input valeurs-checkbox' type='checkbox' name='valeurs_relation[]' value='$valeur' id='valeur-$valeur' $checked>
                                    <label class='form-check-label' for='valeur-$valeur'>$valeur</label>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </div>

            </div>
        </div>
    </div>



   <!-- 🟢 3. Centres d’Intérêt et Style de Vie -->
<div class="accordion-item">
    <h2 class="accordion-header" id="headingInterests">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInterests" aria-expanded="false" aria-controls="collapseInterests">
            Centres d’Intérêt et Style de Vie
        </button>
    </h2>
    <div id="collapseInterests" class="accordion-collapse collapse" aria-labelledby="headingInterests" data-bs-parent="#accordionAdditionalInfo">
        <div class="accordion-body">
            
            <!-- 🔹 Quelles sont vos passions ? (choix multiple, max 5) -->
            <div class="mb-3">
                <label class="form-label">Quelles sont vos passions ? (choix multiple, max 5)</label>
                <div class="row">
                    <?php 
                    $passions = ["Musique", "Cinéma/Séries", "Voyages", "Cuisine", "Sport", "Lecture", "Technologie", "Jeux vidéo", "Jardinage", "Arts et culture"];
                    $selected_passions = isset($_SESSION['passions']) ? explode(',', $_SESSION['passions']) : [];

                    foreach ($passions as $passion) {
                        $checked = in_array($passion, $selected_passions) ? "checked" : "";
                        echo "
                        <div class='col-md-4'>
                            <div class='form-check'>
                                <input class='form-check-input passions-checkbox' type='checkbox' name='passions[]' value='$passion' id='passion-$passion' $checked>
                                <label class='form-check-label' for='passion-$passion'>$passion</label>
                            </div>
                        </div>";
                    }
                    ?>
                </div>
            </div>

            <!-- 🔹 Préférez-vous les sorties ou rester à la maison ? -->
            <div class="mb-3">
                <label class="form-label">Préférez-vous les sorties ou rester à la maison ?</label>
                <select name="sortie_ou_maison" class="form-select">
                    <option value="" disabled selected>Choisissez une option</option>
                    <?php 
                    $sortie_options = [
                        "Je suis plutôt casanier(ère)",
                        "J’aime sortir de temps en temps",
                        "J’adore être dehors et découvrir de nouveaux lieux"
                    ];
                    $selected_sortie = $_SESSION['sortie_ou_maison'] ?? '';
                    foreach ($sortie_options as $option) {
                        echo "<option value='$option' " . ($selected_sortie == $option ? "selected" : "") . ">$option</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- 🔹 Quelle est votre activité idéale lors d’un premier rendez-vous ? -->
            <div class="mb-3">
                <label class="form-label">Quelle est votre activité idéale lors d’un premier rendez-vous ?</label>
                <select name="activite_rdv" class="form-select">
                    <option value="" disabled selected>Choisissez une option</option>
                    <?php 
                    $rdv_activities = [
                        "Un dîner romantique",
                        "Une promenade en nature",
                        "Un café/discussion",
                        "Une activité culturelle (musée, concert, théâtre)",
                        "Une sortie sportive"
                    ];
                    $selected_rdv = $_SESSION['activite_rdv'] ?? '';
                    foreach ($rdv_activities as $activity) {
                        echo "<option value='$activity' " . ($selected_rdv == $activity ? "selected" : "") . ">$activity</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- 🔹 Quelle est votre relation avec le sport ? -->
            <div class="mb-3">
                <label class="form-label">Quelle est votre relation avec le sport ?</label>
                <select name="relation_avec_sport" class="form-select">
                    <option value="" disabled selected>Choisissez une option</option>
                    <?php 
                    $sport_options = [
                        "Je pratique régulièrement",
                        "J’aime regarder le sport mais je ne pratique pas",
                        "Le sport ne m’intéresse pas"
                    ];
                    $selected_sport = $_SESSION['relation_avec_sport'] ?? '';
                    foreach ($sport_options as $option) {
                        echo "<option value='$option' " . ($selected_sport == $option ? "selected" : "") . ">$option</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- 🔹 Votre relation avec la technologie ? -->
            <div class="mb-3">
                <label class="form-label">Votre relation avec la technologie ?</label>
                <select name="relation_avec_technologie" class="form-select">
                    <option value="" disabled selected>Choisissez une option</option>
                    <?php 
                    $tech_options = [
                        "Je suis toujours à jour avec les nouvelles technologies",
                        "J’utilise la technologie seulement pour le nécessaire",
                        "Je préfère les interactions humaines aux écrans"
                    ];
                    $selected_tech = $_SESSION['relation_avec_technologie'] ?? '';
                    foreach ($tech_options as $option) {
                        echo "<option value='$option' " . ($selected_tech == $option ? "selected" : "") . ">$option</option>";
                    }
                    ?>
                </select>
            </div>

        </div>
    </div>
</div>

    <!-- 🟣 4. Objectifs Relationnels -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingRelationshipGoals">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRelationshipGoals" aria-expanded="false" aria-controls="collapseRelationshipGoals">
                Objectifs Relationnels
            </button>
        </h2>
        <div id="collapseRelationshipGoals" class="accordion-collapse collapse" aria-labelledby="headingRelationshipGoals" data-bs-parent="#accordionAdditionalInfo">
            <div class="accordion-body">
                
                <div class="mb-3">
                    <label class="form-label">Quel type de relation recherchez-vous ?</label>
                    <select name="type_relation" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = ["Sérieuse et à long terme", "Amitié qui peut évoluer", "Rencontre sans engagement", "Je ne sais pas encore"];
                        $selected = $_SESSION['type_relation'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">À quel point l’engagement est-il important pour vous ?</label>
                    <select name="importance_engagement" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = [
                            "Très important, je cherche quelque chose de stable",
                            "Moyen, je veux apprendre à connaître la personne d’abord",
                            "Peu important, je préfère voir où cela mène naturellement"
                        ];
                        $selected = $_SESSION['importance_engagement'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Quel est votre rythme idéal pour une relation ?</label>
                    <select name="rythme_relation" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = [
                            "Prendre son temps, sans précipitation",
                            "Avancer rapidement si le feeling est là",
                            "Suivre le rythme de l’autre sans pression"
                        ];
                        $selected = $_SESSION['rythme_relation'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Êtes-vous ouvert(e) aux relations à distance ?</label>
                    <select name="relation_distance" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = ["Oui", "Non", "Seulement si cela peut évoluer vers une proximité physique"];
                        $selected = $_SESSION['relation_distance'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <!-- 🔵 5. Mode de Communication et Interactions -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingCommunication">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCommunication" aria-expanded="false" aria-controls="collapseCommunication">
                Mode de Communication et Interactions
            </button>
        </h2>
        <div id="collapseCommunication" class="accordion-collapse collapse" aria-labelledby="headingCommunication" data-bs-parent="#accordionAdditionalInfo">
            <div class="accordion-body">
                
                <div class="mb-3">
                    <label class="form-label">Quel est votre moyen de communication préféré ?</label>
                    <select name="moyen_communication" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = ["Messages écrits", "Appels téléphoniques", "Messages vocaux", "Vidéo appels"];
                        $selected = $_SESSION['moyen_communication'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">À quelle fréquence aimez-vous communiquer avec votre partenaire ?</label>
                    <select name="frequence_communication" class="form-select">
                        <option value="" disabled selected>Choisissez une option</option>
                        <?php 
                        $options = ["Tous les jours", "Plusieurs fois par semaine", "Une fois par semaine", "Cela dépend de la dynamique de la relation"];
                        $selected = $_SESSION['frequence_communication'] ?? '';
                        foreach ($options as $option) {
                            echo "<option value='$option' " . ($selected == $option ? "selected" : "") . ">$option</option>";
                        }
                        ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

</div>






<!-- Galerie d'images -->
<h2 class="text-center mb-4">Galerie d'images</h2>
<div class="accordion" id="accordionGallery">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingGallery">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGallery" aria-expanded="false" aria-controls="collapseGallery">
                Voir la galerie d'images
            </button>
        </h2>
        <div id="collapseGallery" class="accordion-collapse collapse" aria-labelledby="headingGallery" data-bs-parent="#accordionGallery">
            <div class="accordion-body">
                <div class="gallery-container" id="gallery-preview">
                    <?php if (!empty($galerie_images)): ?>
                        <?php foreach ($galerie_images as $image): ?>
                            <div class="gallery-item">
                                <a href="#" class="open-image-modal" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="uploads/gallery/<?php echo htmlspecialchars($image); ?>">
                                    <img src="uploads/gallery/<?php echo htmlspecialchars($image); ?>" class="gallery-image" alt="Image utilisateur">
                                </a>
                                <button type="button" class="delete-image-btn" data-image="<?php echo htmlspecialchars($image); ?>">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-images-message">Aucune image ajoutée pour le moment.</p>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label for="galerie_images" class="form-label">Ajouter des images</label>
                    <input type="file" id="galerie_images" name="galerie_images[]" class="form-control" multiple accept="image/*">
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modifier le mot de passe -->
<h2 class="text-center mb-4">Modifier le mot de passe</h2>
<div class="accordion" id="accordionPassword">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingPassword">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePassword" aria-expanded="false" aria-controls="collapsePassword">
                Modifier votre mot de passe
            </button>
        </h2>
        <div id="collapsePassword" class="accordion-collapse collapse" aria-labelledby="headingPassword" data-bs-parent="#accordionPassword">
            <div class="accordion-body">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" class="form-control small-password-input" placeholder="Entrez votre mot de passe actuel">
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" class="form-control small-password-input" placeholder="Entrez votre nouveau mot de passe">
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control small-password-input" placeholder="Confirmer votre nouveau mot de passe">
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Bouton "Mettre à jour" -->
<button type="submit" class="btn btn-update">Mettre à jour</button>
</form>

<!-- ✅ Formulaire de suppression du compte -->
<div class="delete-account-section">
    <form action="delete_account.php" method="POST"
        onsubmit="return confirm('❌ Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible !');">
        <button type="submit" class="btn btn-danger" id="delete-account-btn">Supprimer mon compte</button>
    </form>
</div>

</section>


    <?php include("footer.php"); ?>

   <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function () {
    // Gestion de l'image modale
    $(".open-image-modal").on("click", function () {
        let imageUrl = $(this).data("image"); 
        console.log("🖼️ Image sélectionnée :", imageUrl);
        
        if ($("#modal-image").length) {
            $("#modal-image").attr("src", imageUrl);
        } else {
            console.warn("⚠️ Élément #modal-image introuvable.");
        }
    });
});

// Charger les plugins nécessaires
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="editprofil.js"></script>

<script>
$(document).ready(function () {
    let username = "faykas"; 

    // Récupérer les valeurs PHP pour le pays et la ville
    let selectedCountry = "<?php echo addslashes($_SESSION['pays_residence'] ?? ''); ?>";
    let selectedCity = "<?php echo addslashes($_SESSION['ville'] ?? ''); ?>";

    console.log("🌍 Pays enregistré :", selectedCountry);
    console.log("🏙️ Ville enregistrée :", selectedCity);

    // Charger la liste des pays
    $.get(`https://secure.geonames.org/countryInfoJSON?username=${username}&lang=fr`, function (data) {
        let paysDropdown = $("#pays_residence");
        paysDropdown.empty().append('<option value="">Sélectionner un pays</option>');

        if (data.geonames && Array.isArray(data.geonames)) {
            data.geonames.forEach(pays => {
                let isSelected = (pays.countryCode === selectedCountry) ? "selected" : "";
                paysDropdown.append(`<option value="${pays.countryCode}" ${isSelected}>${pays.countryName}</option>`);
            });

            // Si un pays est pré-sélectionné, charger ses villes
            if (selectedCountry) {
                loadCities(selectedCountry, selectedCity);
            }
        } else {
            console.warn("⚠️ Aucune donnée reçue depuis Geonames !");
        }
    }).fail(function(xhr, status, error) {
        console.error("❌ Erreur AJAX pour les pays :", status, error);
    });

    // Changer la liste des villes quand le pays change
    $("#pays_residence").on("change", function () {
        let countryCode = $(this).val();
        if (countryCode) {
            loadCities(countryCode, "");
        } else {
            $("#ville").empty().append('<option value="">Sélectionner une ville</option>');
        }
    });

    // Fonction pour charger les villes et pré-sélectionner la ville enregistrée
    function loadCities(countryCode, citySelected) {
        let villeDropdown = $("#ville");
        villeDropdown.empty().append('<option value="">Chargement...</option>');

        $.get(`https://secure.geonames.org/searchJSON?country=${countryCode}&featureClass=P&maxRows=1000&username=${username}`, function (data) {
            villeDropdown.empty().append('<option value="">Sélectionner une ville</option>');

            if (data.geonames && Array.isArray(data.geonames)) {
                data.geonames.forEach(ville => {
                    let isSelected = (ville.name === citySelected) ? "selected" : "";
                    villeDropdown.append(`<option value="${ville.name}" ${isSelected}>${ville.name}</option>`);
                });
            } else {
                console.warn("⚠️ Aucune ville trouvée pour ce pays.");
            }
        }).fail(function(xhr, status, error) {
            console.error("❌ Erreur AJAX pour les villes :", status, error);
        });
    }
});

// ✅ Supprimer le compte en toute sécurité
document.addEventListener("DOMContentLoaded", function () {
    const deleteBtn = document.getElementById("delete-account-btn");
    if (deleteBtn) {
        deleteBtn.addEventListener("click", function (event) {
            event.preventDefault();

            Swal.fire({
                title: "Êtes-vous sûr ?",
                text: "Cette action est irréversible ! Votre compte sera définitivement supprimé.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Oui, supprimer",
                cancelButtonText: "Annuler"
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteBtn.closest("form").submit();
                }
            });
        });
    } else {
        console.warn("⚠️ Bouton 'Supprimer mon compte' introuvable dans le DOM.");
    }
});

// ✅ Fermer tous les accordéons au chargement
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.accordion-collapse').forEach(accordion => {
        accordion.classList.remove('show');
    });

    console.log("✅ Accordéons fermés par défaut !");
});
</script>
</body>
</html>
