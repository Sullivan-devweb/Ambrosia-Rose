<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="inscription.css">
    <style>
        .hidden { display: none; }
        .error { border: 1px solid red; }
        .gallery-image { max-width: 100px; margin: 5px; }
        body, html {
            height: 100%;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .form-container {
            background: #FFF;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
        }
        .btn-custom {
            background-color: #001F54;
            color: #FFF;
        }
        .btn-custom:hover {
            background-color: #001F54;
            opacity: 0.8;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div id="progress-bar" class="progress mb-4">
                <div id="progress-bar-inner" class="progress-bar" role="progressbar" style="width: 0;"></div>
            </div>

            <form id="inscriptionForm" method="POST" action="inscription_process.php" enctype="multipart/form-data">
                <!-- Étape 1 -->
                <div id="step1" class="form-step active">
                    <h1>Identité</h1>
                    <div class="form-group">
                        <label>Nom :</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom :</label>
                        <input type="text" class="form-control" name="prenom" required>
                    </div>
                    <div class="form-group">
                        <label>Email :</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe :</label>
                        <input type="password" class="form-control" name="mot_de_passe" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirmer le mot de passe :</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div class="form-group">
                        <label>Pays de résidence :</label>
                        <select id="pays_residence" class="form-select" name="pays_residence" required>
                            <option value="">Sélectionner un pays</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ville :</label>
                        <select id="ville" class="form-select" name="ville" required>
                            <option value="">Sélectionner une ville</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-custom next">Suivant</button>
                </div>

                <!-- Étape 2 -->
                <div id="step2" class="form-step">
                    <h1>Quand êtes-vous né ?</h1>
                    <div class="form-group">
                        <label>Date de naissance :</label>
                        <input type="date" class="form-control" name="date_naissance" required>
                    </div>
                    <button type="button" class="btn btn-secondary prev">Précédent</button>
                    <button type="button" class="btn btn-custom next">Suivant</button>
                </div>

                <!-- Étape 3 -->
                <div id="step3" class="form-step">
                    <h1>Quel est votre genre ?</h1>
                    <div class="form-group">
                        <label>Genre :</label>
                        <select class="form-control" name="genre" required>
                            <option value="Homme">Homme</option>
                            <option value="Femme">Femme</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Recherche :</label>
                        <select class="form-control" name="recherche" required>
                            <option value="Homme">Homme</option>
                            <option value="Femme">Femme</option>
                            <option value="Homme et Femme">Homme et Femme</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-secondary prev">Précédent</button>
                    <button type="button" class="btn btn-custom next">Suivant</button>
                </div>

                <!-- Étape 4 -->
                <div id="step4" class="form-step">
                    <h1>Votre photo de profil</h1>
                    <div class="photo-container">
                        <input type="file" class="form-control-file hidden" name="image_profil" accept="image/*" id="image_profil">
                        <button type="button" id="profileButton" class="btn btn-secondary">Choisir une photo</button>
                        <img id="photoPreview" src="#" alt="Aperçu photo" class="hidden mt-3">
                    </div>
                    <button type="button" class="btn btn-secondary prev">Précédent</button>
                    <button type="button" class="btn btn-custom next">Suivant</button>
                </div>

                <!-- Étape 5 -->
                <div id="step5" class="form-step">
                    <h1>Description</h1>
                    <div class="form-group">
                        <label for="description">Parlez-nous de vous :</label>
                        <textarea class="form-control" name="description" id="description" rows="5" required></textarea>
                    </div>
                    <button type="button" class="btn btn-secondary prev">Précédent</button>
                    <button type="button" class="btn btn-custom next">Suivant</button>
                </div>

                <!-- Étape 6 -->
                <div id="step6" class="form-step">
                    <h1>Galerie d'images</h1>
                    <p>Ajoutez jusqu'à 5 photos :</p>
                    <div class="gallery-container">
                        <input type="file" class="form-control-file hidden" name="gallery_images[]" accept="image/*" id="galleryInput" multiple>
                        <button type="button" id="galleryButton" class="btn btn-secondary">Ajouter des images</button>
                        <div id="galleryPreview" class="gallery-preview"></div>
                    </div>
                    <button type="button" class="btn btn-secondary prev">Précédent</button>
                    <button type="button" class="btn btn-custom next">Suivant</button>
                </div>

                <!-- Étape 7 -->
                <div id="step7" class="form-step">
                    <h1>Finalisation</h1>
                    <button type="button" class="btn btn-secondary prev">Précédent</button>
                    <button type="submit" class="btn btn-success btn-custom" name="inscription">S'inscrire</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inscription.js"></script>
    <script>
        $(document).ready(function () {
            let currentStep = 1;
            const totalSteps = 7;

            // Fonction pour mettre à jour la barre de progression
            function updateProgressBar() {
                const progress = (currentStep / totalSteps) * 100;
                $("#progress-bar-inner").css("width", `${progress}%`);
            }

            // Fonction pour afficher l'étape actuelle
            function showStep(step) {
                $(".form-step").removeClass("active");
                $(`#step${step}`).addClass("active");
                updateProgressBar();
            }

            // Bouton "Suivant"
            $(".next").on("click", function () {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            });

            // Bouton "Précédent"
            $(".prev").on("click", function () {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });

            // Charger la liste des pays
            let username = "faykas"; // Remplacez par votre propre nom d'utilisateur GeoNames
            $.get(`https://secure.geonames.org/countryInfoJSON?username=${username}&lang=fr`, function (data) {
                let paysDropdown = $("#pays_residence");
                paysDropdown.empty().append(`<option value="">Sélectionner un pays</option>`);

                if (data.geonames) {
                    data.geonames.forEach(pays => {
                        paysDropdown.append(`<option value="${pays.countryCode}">${pays.countryName}</option>`);
                    });
                }
            }).fail(function(xhr, status, error) {
                console.error("Erreur AJAX pour les pays :", status, error);
            });

            // Changer la liste des villes quand le pays change
            $("#pays_residence").on("change", function () {
                let countryCode = $(this).val();
                if (countryCode) {
                    loadCities(countryCode);
                } else {
                    $("#ville").empty().append(`<option value="">Sélectionner une ville</option>`);
                }
            });

            // Fonction pour charger les villes
            function loadCities(countryCode) {
                let villeDropdown = $("#ville");
                villeDropdown.empty().append(`<option value="">Chargement...</option>`);

                $.get(`https://secure.geonames.org/searchJSON?country=${countryCode}&featureClass=P&maxRows=1000&username=${username}`, function (data) {
                    villeDropdown.empty().append(`<option value="">Sélectionner une ville</option>`);

                    if (data.geonames.length > 0) {
                        data.geonames.forEach(ville => {
                            villeDropdown.append(`<option value="${ville.name}">${ville.name}</option>`);
                        });
                    } else {
                        console.warn("Aucune ville trouvée pour ce pays.");
                    }
                }).fail(function(xhr, status, error) {
                    console.error("Erreur AJAX pour les villes :", status, error);
                });
            }

            // Gestion de l'aperçu de la photo de profil
            $("#profileButton").on("click", function () {
                $("#image_profil").click();
            });

            $("#image_profil").on("change", function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $("#photoPreview").attr("src", e.target.result).removeClass("hidden");
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Gestion de la galerie d'images
            $("#galleryButton").on("click", function () {
                $("#galleryInput").click();
            });

            $("#galleryInput").on("change", function (e) {
                const files = e.target.files;
                const galleryPreview = $("#galleryPreview");
                galleryPreview.empty();

                for (let i = 0; i < files.length && i < 5; i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        galleryPreview.append(`<img src="${e.target.result}" class="gallery-image">`);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>