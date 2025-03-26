/**
 * @file Gestion complète de l'édition de profil
 * @module EditProfil
 * @version 1.0.0
 * @requires sweetalert2
 * @description Ce module gère toutes les interactions de la page d'édition de profil :
 * - Prévisualisation de l'image de profil
 * - Gestion des centres d'intérêt
 * - Validation du formulaire
 * - Gestion de la galerie d'images
 * - Suppression d'images via AJAX
 */

document.addEventListener('DOMContentLoaded', function () {
    /**
     * Gestion de la prévisualisation de l'image de profil
     * @function handleProfileImage
     */
    function handleProfileImage() {
        const profileInput = document.getElementById('image_profil');
        const profilePreview = document.getElementById('profile-preview');

        if (profileInput && profilePreview) {
            profileInput.addEventListener('change', function (event) {
                const file = event.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function () {
                    profilePreview.src = reader.result;
                };
                reader.readAsDataURL(file);
            });
        }
    }

    /**
     * Gestion des centres d'intérêt (sélection/désélection)
     * @function handleInterests
     */
    function handleInterests() {
        document.querySelectorAll('.interest-item').forEach(item => {
            item.addEventListener('click', function () {
                this.classList.toggle('selected');
                const checkbox = this.nextElementSibling;
                if (checkbox && checkbox.type === 'checkbox') {
                    checkbox.checked = !checkbox.checked;
                }
            });
        });
    }

    /**
     * Validation du formulaire avant soumission
     * @function validateForm
     */
    function validateForm() {
        const form = document.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            const requiredFields = document.querySelectorAll('input[required]');
            let allFilled = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allFilled = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!allFilled) {
                event.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Champs requis',
                    text: 'Veuillez remplir tous les champs obligatoires !',
                });
            }
        });
    }

    /**
     * Gestion de la galerie d'images (prévisualisation et suppression)
     * @function handleImageGallery
     */
    function handleImageGallery() {
        const galleryInput = document.getElementById('galerie_images');
        const galleryPreview = document.getElementById('gallery-preview');

        if (galleryInput && galleryPreview) {
            galleryInput.addEventListener('change', function (event) {
                const files = Array.from(event.target.files);
                
                files.forEach(file => {
                    if (!file.type.startsWith('image/')) return;

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        createGalleryImage(e.target.result, galleryPreview);
                    };
                    reader.readAsDataURL(file);
                });
            });
        }
    }

    /**
     * Crée un élément image pour la galerie avec bouton de suppression
     * @function createGalleryImage
     * @param {string} imageSrc - Source de l'image
     * @param {HTMLElement} container - Conteneur de la galerie
     */
    function createGalleryImage(imageSrc, container) {
        const imgWrapper = document.createElement('div');
        imgWrapper.classList.add('gallery-item');

        const img = document.createElement('img');
        img.src = imageSrc;
        img.classList.add('gallery-image');

        const deleteBtn = document.createElement('button');
        deleteBtn.classList.add('delete-image-btn');
        deleteBtn.innerHTML = "&times;";
        deleteBtn.addEventListener('click', confirmImageDeletion.bind(null, imgWrapper));

        imgWrapper.appendChild(img);
        imgWrapper.appendChild(deleteBtn);
        container.appendChild(imgWrapper);
    }

    /**
     * Confirme la suppression d'une image avec SweetAlert
     * @function confirmImageDeletion
     * @param {HTMLElement} imageElement - Élément image à supprimer
     */
    function confirmImageDeletion(imageElement) {
        Swal.fire({
            title: "Confirmation",
            text: "Êtes-vous sûr de vouloir supprimer cette image ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#EEC4C9",
            cancelButtonColor: "#001F54",
            confirmButtonText: "Oui, supprimer",
            cancelButtonText: "Annuler"
        }).then((result) => {
            if (result.isConfirmed) {
                imageElement.remove();
                Swal.fire('Supprimé !', 'L\'image a été supprimée.', 'success');
            }
        });
    }

    /**
     * Gestion de la suppression des images existantes via AJAX
     * @function handleExistingImageDeletion
     */
    function handleExistingImageDeletion() {
        document.querySelectorAll('.delete-image-btn[data-image]').forEach(button => {
            button.addEventListener('click', function () {
                const imageName = this.getAttribute('data-image');
                const imageElement = this.closest('.gallery-item');

                Swal.fire({
                    title: "Confirmation",
                    text: "Êtes-vous sûr de vouloir supprimer définitivement cette image ?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#EEC4C9",
                    cancelButtonColor: "#001F54",
                    confirmButtonText: "Oui, supprimer",
                    cancelButtonText: "Annuler"
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteImage(imageName, imageElement);
                    }
                });
            });
        });
    }

    /**
     * Supprime une image via AJAX
     * @async
     * @function deleteImage
     * @param {string} imageName - Nom de l'image à supprimer
     * @param {HTMLElement} imageElement - Élément DOM à supprimer
     */
    async function deleteImage(imageName, imageElement) {
        try {
            const response = await fetch('delete_image.php', {
                method: 'POST',
                body: JSON.stringify({ image: imageName }),
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await response.json();

            if (data.success) {
                imageElement.remove();
                Swal.fire('Succès !', 'Image supprimée avec succès.', 'success');
            } else {
                throw new Error(data.message || 'Erreur lors de la suppression');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur !', 'La suppression a échoué.', 'error');
        }
    }

    // Initialisation des fonctions
    handleProfileImage();
    handleInterests();
    validateForm();
    handleImageGallery();
    handleExistingImageDeletion();
});