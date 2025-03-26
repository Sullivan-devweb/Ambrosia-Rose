document.addEventListener('DOMContentLoaded', function () {
    // ✅ Gestion de l'image de profil
    const profileInput = document.getElementById('image_profil');
    const profilePreview = document.getElementById('profile-preview');

    if (profileInput) {
        profileInput.addEventListener('change', function (event) {
            const reader = new FileReader();
            reader.onload = function () {
                profilePreview.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        });
    }

    // ✅ Gestion des centres d'intérêt
    document.querySelectorAll('.interest-item').forEach(item => {
        item.addEventListener('click', function () {
            this.classList.toggle('selected');
            let checkbox = this.nextElementSibling;
            if (checkbox && checkbox.type === 'checkbox') {
                checkbox.checked = !checkbox.checked;
            }
        });
    });

    // ✅ Validation du formulaire avant soumission
    document.querySelector('form').addEventListener('submit', function (event) {
        const requiredFields = document.querySelectorAll('input[required]');
        let allFilled = true;
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                allFilled = false;
            }
        });
        if (!allFilled) {
            event.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Veuillez remplir tous les champs obligatoires !',
            });
        }
    });

    // ✅ Gestion de la galerie d'images
    const galleryInput = document.getElementById('galerie_images');
    const galleryPreview = document.getElementById('gallery-preview');

    if (galleryInput) {
        galleryInput.addEventListener('change', function (event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const imgWrapper = document.createElement('div');
                        imgWrapper.classList.add('gallery-item');

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('gallery-image');

                        const deleteBtn = document.createElement('button');
                        deleteBtn.classList.add('delete-image-btn');
                        deleteBtn.innerHTML = "&times;";
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
                                    Swal.fire('Supprimé !', 'Votre image a été supprimée.', 'success');
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
        });
    }

    // ✅ Suppression des images existantes via AJAX
    document.querySelectorAll('.delete-image-btn').forEach(button => {
        button.addEventListener('click', function () {
            const imageName = this.getAttribute('data-image');
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
                    fetch('delete_image.php', {
                        method: 'POST',
                        body: JSON.stringify({ image: imageName }),
                        headers: { 'Content-Type': 'application/json' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.parentElement.remove();
                            Swal.fire('Supprimé !', 'Votre image a été supprimée.', 'success');
                        } else {
                            Swal.fire('Erreur !', 'Impossible de supprimer l\'image.', 'error');
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
                }
            });
        });
    });
});


