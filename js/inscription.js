/**
 * @file Gestion du formulaire d'inscription multi-étapes
 * @module InscriptionForm
 * @version 1.0.0
 * @requires sweetalert2
 * @description Ce module gère :
 * - La navigation entre les étapes du formulaire
 * - La validation des champs requis
 * - Les règles de validation spécifiques (mot de passe, âge)
 * - La prévisualisation des images (profil et galerie)
 * - La barre de progression
 */

document.addEventListener('DOMContentLoaded', function () {
    // Sélection des éléments
    const steps = document.querySelectorAll('.form-step');
    const nextButtons = document.querySelectorAll('.next');
    const prevButtons = document.querySelectorAll('.prev');
    const form = document.getElementById('inscriptionForm');
    const progressBar = document.getElementById('progress-bar-inner');
    let currentStep = 0;

    /**
     * Affiche une étape spécifique du formulaire
     * @function showStep
     * @param {number} stepIndex - Index de l'étape à afficher
     */
    function showStep(stepIndex) {
        steps.forEach((step, index) => {
            step.classList.toggle('active', index === stepIndex);
        });
        updateProgressBar();
    }

    /**
     * Valide les champs de l'étape actuelle
     * @function validateStep
     * @param {number} stepIndex - Index de l'étape à valider
     * @returns {boolean} True si la validation réussit, false sinon
     */
    function validateStep(stepIndex) {
        const step = steps[stepIndex];
        const inputs = step.querySelectorAll('input[required], textarea[required], select[required]');
        let allValid = true;

        // Validation des champs obligatoires
        inputs.forEach(input => {
            input.classList.remove('error');
            if (!input.value.trim()) {
                allValid = false;
                input.classList.add('error');
                showValidationError(`Veuillez remplir le champ ${input.name} !`);
            }
        });

        // Validation spécifique à l'étape du mot de passe
        if (stepIndex === 0 && !validatePasswordStep()) {
            allValid = false;
        }

        // Validation spécifique à l'étape de la date de naissance
        if (stepIndex === 1 && !validateBirthDate()) {
            allValid = false;
        }

        return allValid;
    }

    /**
     * Valide les règles du mot de passe
     * @function validatePasswordStep
     * @returns {boolean} True si le mot de passe est valide
     */
    function validatePasswordStep() {
        const password = document.querySelector("input[name='mot_de_passe']").value;
        const confirmPassword = document.querySelector("input[name='confirmPassword']").value;
        let isValid = true;

        if (password !== confirmPassword) {
            showValidationError('Les mots de passe ne correspondent pas !');
            isValid = false;
        }

        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!password.match(passwordRegex)) {
            let errorMessage = 'Le mot de passe doit contenir :';
            if (!/(?=.*[a-z])/.test(password)) errorMessage += '\n- Une minuscule';
            if (!/(?=.*[A-Z])/.test(password)) errorMessage += '\n- Une majuscule';
            if (!/(?=.*\d)/.test(password)) errorMessage += '\n- Un chiffre';
            if (!/(?=.*[@$!%*?&])/.test(password)) errorMessage += '\n- Un caractère spécial';
            if (password.length < 8) errorMessage += '\n- Au moins 8 caractères';

            showValidationError(errorMessage);
            isValid = false;
        }

        return isValid;
    }

    /**
     * Valide la date de naissance (âge minimum 30 ans)
     * @function validateBirthDate
     * @returns {boolean} True si l'âge est valide
     */
    function validateBirthDate() {
        const birthDate = new Date(document.querySelector("input[name='date_naissance']").value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDifference = today.getMonth() - birthDate.getMonth();
        
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        if (age < 30) {
            showValidationError('Vous devez avoir au moins 30 ans pour vous inscrire.');
            return false;
        }

        return true;
    }

    /**
     * Affiche une erreur de validation avec SweetAlert
     * @function showValidationError
     * @param {string} message - Message d'erreur à afficher
     */
    function showValidationError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur de validation',
            text: message
        }).then(() => {
            showStep(currentStep);
        });
    }

    /**
     * Met à jour la barre de progression
     * @function updateProgressBar
     */
    function updateProgressBar() {
        const progress = ((currentStep + 1) / steps.length) * 100;
        progressBar.style.width = `${progress}%`;
    }

    // Gestion des boutons Suivant
    nextButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            if (validateStep(currentStep)) {
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    showStep(currentStep);
                } else {
                    form.submit();
                }
            }
        });
    });

    // Gestion des boutons Précédent
    prevButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });

    // Initialisation de l'image de profil
    const profileInput = document.getElementById('image_profil');
    const profilePreview = document.getElementById('photoPreview');
    const profileButton = document.getElementById('profileButton');

    profileButton.addEventListener('click', () => profileInput.click());

    profileInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = () => {
                profilePreview.src = reader.result;
                profilePreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    // Initialisation de la galerie d'images
    const galleryInput = document.getElementById('galleryInput');
    const galleryButton = document.getElementById('galleryButton');
    const galleryPreview = document.getElementById('galleryPreview');
    let selectedFiles = [];

    galleryButton.addEventListener('click', () => galleryInput.click());

    galleryInput.addEventListener('change', function (event) {
        selectedFiles = selectedFiles.concat(Array.from(event.target.files));
        updateGalleryDisplay();
        updateGalleryInput();
    });

    /**
     * Met à jour l'affichage de la galerie
     * @function updateGalleryDisplay
     */
    function updateGalleryDisplay() {
        galleryPreview.innerHTML = "";
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
                    deleteBtn.addEventListener('click', () => confirmDeleteImage(index));

                    imgWrapper.appendChild(img);
                    imgWrapper.appendChild(deleteBtn);
                    galleryPreview.appendChild(imgWrapper);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    /**
     * Confirme la suppression d'une image
     * @function confirmDeleteImage
     * @param {number} index - Index de l'image à supprimer
     */
    function confirmDeleteImage(index) {
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
                selectedFiles.splice(index, 1);
                updateGalleryDisplay();
                updateGalleryInput();
                Swal.fire('Supprimé !', 'Votre image a été supprimée.', 'success');
            }
        });
    }

    /**
     * Met à jour l'input file avec les fichiers sélectionnés
     * @function updateGalleryInput
     */
    function updateGalleryInput() {
        const newFileList = new DataTransfer();
        selectedFiles.forEach(file => newFileList.items.add(file));
        galleryInput.files = newFileList.files;
    }

    // Afficher la première étape au chargement
    showStep(currentStep);
});