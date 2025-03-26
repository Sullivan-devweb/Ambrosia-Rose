document.addEventListener('DOMContentLoaded', function () {
    const steps = document.querySelectorAll('.form-step'); // Toutes les étapes du formulaire
    const nextButtons = document.querySelectorAll('.next'); // Boutons "Suivant"
    const prevButtons = document.querySelectorAll('.prev'); // Boutons "Précédent"
    const form = document.getElementById('inscriptionForm'); // Formulaire
    const progressBar = document.getElementById('progress-bar-inner'); // Barre de progression
    let currentStep = 0; // Étape actuelle

    // Afficher l'étape actuelle
    const showStep = (stepIndex) => {
        steps.forEach((step, index) => {
            step.classList.toggle('active', index === stepIndex); // Afficher uniquement l'étape actuelle
        });
        updateProgressBar(); // Mettre à jour la barre de progression
    };

    // Valider l'étape actuelle
    const validateStep = (stepIndex) => {
        const step = steps[stepIndex];
        const inputs = step.querySelectorAll('input[required], textarea[required], select[required]');
        let allValid = true;

        // Réinitialiser les erreurs
        inputs.forEach(input => input.classList.remove('error'));

        // Validation des champs obligatoires
        inputs.forEach(input => {
            if (!input.value.trim()) {
                allValid = false;
                input.classList.add('error');
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: `Veuillez remplir le champ ${input.name} !`,
                }).then(() => {
                    // Après avoir cliqué sur "OK", on reste sur l'étape actuelle
                    showStep(currentStep);
                });
            }
        });

        // Validation spécifique à l'étape 1 (mot de passe)
        if (stepIndex === 0) {
            const password = document.querySelector("input[name='mot_de_passe']").value;
            const confirmPassword = document.querySelector("input[name='confirmPassword']").value;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            if (!password.match(passwordRegex)) {
                allValid = false;
                let errorMessage = 'Le mot de passe doit contenir :';
                if (!/(?=.*[a-z])/.test(password)) errorMessage += '\n- Une minuscule';
                if (!/(?=.*[A-Z])/.test(password)) errorMessage += '\n- Une majuscule';
                if (!/(?=.*\d)/.test(password)) errorMessage += '\n- Un chiffre';
                if (!/(?=.*[@$!%*?&])/.test(password)) errorMessage += '\n- Un caractère spécial';
                if (password.length < 8) errorMessage += '\n- Au moins 8 caractères';

                Swal.fire({
                    icon: 'error',
                    title: 'Mot de passe invalide',
                    text: errorMessage,
                }).then(() => {
                    // Après avoir cliqué sur "OK", on reste sur l'étape actuelle
                    showStep(currentStep);
                });
            }

            if (password !== confirmPassword) {
                allValid = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Les mots de passe ne correspondent pas',
                    text: 'Les mots de passe ne correspondent pas !',
                }).then(() => {
                    // Après avoir cliqué sur "OK", on reste sur l'étape actuelle
                    showStep(currentStep);
                });
            }
        }

        // Validation spécifique à l'étape 2 (date de naissance)
        if (stepIndex === 1) {
            const birthDate = new Date(document.querySelector("input[name='date_naissance']").value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDifference = today.getMonth() - birthDate.getMonth();
            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 30) {
                allValid = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Âge invalide',
                    text: 'Vous devez avoir au moins 30 ans pour vous inscrire.',
                }).then(() => {
                    // Après avoir cliqué sur "OK", on reste sur l'étape actuelle
                    showStep(currentStep);
                });
            }
        }

        return allValid; // Retourne true si tout est valide, sinon false
    };

    // Mettre à jour la barre de progression
    const updateProgressBar = () => {
        const progress = ((currentStep + 1) / steps.length) * 100;
        progressBar.style.width = `${progress}%`;
    };

    // Gestion des boutons "Suivant"
    nextButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault(); // Empêcher le comportement par défaut du bouton

            // Valider l'étape actuelle
            if (!validateStep(currentStep)) {
                return; // Bloquer le passage à l'étape suivante si la validation échoue
            }

            // Passer à l'étape suivante uniquement si la validation réussit
            if (currentStep < steps.length - 1) {
                currentStep++; // Passer à l'étape suivante
                showStep(currentStep);
            } else {
                // Si c'est la dernière étape, soumettre le formulaire
                form.submit();
            }
        });
    });

    // Gestion des boutons "Précédent"
    prevButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault(); // Empêcher le comportement par défaut du bouton

            // Revenir à l'étape précédente
            if (currentStep > 0) {
                currentStep--; // Passer à l'étape précédente
                showStep(currentStep);
            }
        });
    });

    // Afficher l'étape initiale
    showStep(currentStep);

    // Gestion de l'image de profil
    const profileInput = document.getElementById('image_profil');
    const profilePreview = document.getElementById('photoPreview');
    const profileButton = document.getElementById('profileButton');

    profileButton.addEventListener('click', function () {
        profileInput.click();
    });

    profileInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function () {
                profilePreview.src = reader.result;
                profilePreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    // Gestion de la galerie d'images
    const galleryInput = document.getElementById('galleryInput');
    const galleryButton = document.getElementById('galleryButton');
    const galleryPreview = document.getElementById('galleryPreview');
    let selectedFiles = [];

    galleryButton.addEventListener('click', function () {
        galleryInput.click();
    });

    galleryInput.addEventListener('change', function (event) {
        const files = Array.from(event.target.files);

        // Ajouter les nouvelles images à la liste des fichiers sélectionnés
        selectedFiles = selectedFiles.concat(files);
        galleryPreview.innerHTML = ""; // Effacer les images existantes

        selectedFiles.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const imgWrapper = document.createElement('div');
                    imgWrapper.classList.add('gallery-item');

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('gallery-image');

                    const deleteBtn = document.createElement('button');
                    deleteBtn.classList.add('delete-btn');
                    deleteBtn.textContent = '×';
                    deleteBtn.addEventListener('click', function () {
                        Swal.fire({
                            title: "Êtes-vous sûr ?",
                            text: "Cette action est irréversible !",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#EEC4C9",
                            cancelButtonColor: "#001F54",
                            confirmButtonText: "Oui, supprimer",
                            cancelButtonText: "Annuler"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                imgWrapper.remove();
                                selectedFiles = selectedFiles.filter((_, i) => i !== index);
                                updateGalleryInput();
                                Swal.fire(
                                    'Supprimé !',
                                    'Votre image a été supprimée.',
                                    'success'
                                );
                            }
                        });
                    });

                    imgWrapper.appendChild(img);
                    imgWrapper.appendChild(deleteBtn);
                    galleryPreview.appendChild(imgWrapper);
                };
                reader.readAsDataURL(file);
            }
        });

        // Mise à jour de l'input file pour conserver les fichiers sélectionnés
        updateGalleryInput();
    });

    function updateGalleryInput() {
        const newFileList = new DataTransfer();
        selectedFiles.forEach(file => newFileList.items.add(file));
        galleryInput.files = newFileList.files;
    }
});