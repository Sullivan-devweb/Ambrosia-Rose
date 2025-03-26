document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded and parsed');

    let mediaRecorder;
    let audioChunks = [];
    let isRecording = false;

    const voiceBtn = document.querySelector('.voice-btn');
    const messageInput = document.getElementById('message-input');
    const form = document.querySelector('form');
    let audioPreview;

    // Gestion de l'enregistrement vocal
    if (voiceBtn) {
        voiceBtn.addEventListener('click', async () => {
            if (isRecording) {
                mediaRecorder.stop();
                isRecording = false;
                voiceBtn.style.backgroundColor = "";
            } else {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

                    mediaRecorder.ondataavailable = event => {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        const audioUrl = URL.createObjectURL(audioBlob);

                        audioPreview = document.createElement('audio');
                        audioPreview.src = audioUrl;
                        audioPreview.controls = true;
                        audioPreview.style.width = '100%';

                        const zoneSaisie = document.querySelector('.zone-saisie');
                        zoneSaisie.appendChild(audioPreview);

                        const formData = new FormData();
                        formData.append('voiceMessage', audioBlob);
                        formData.append('message', messageInput.value);

                        form.onsubmit = async (e) => {
                            e.preventDefault();

                            try {
                                await fetch(form.action, {
                                    method: 'POST',
                                    body: formData
                                });

                                // Redirection après envoi réussi
                                window.location.href = form.action;

                            } catch (error) {
                                console.error('Erreur:', error);
                            }
                        };

                        audioChunks = [];
                        voiceBtn.style.backgroundColor = "";
                    };

                    mediaRecorder.start();
                    voiceBtn.style.backgroundColor = "#ff4c4c";
                    isRecording = true;
                } catch (err) {
                    alert("Impossible d'accéder au micro. Assurez-vous que l'autorisation est accordée.");
                }
            }
        });
    } else {
        console.error('Voice button not found');
    }

    // Gestion de l'upload de fichiers (images/vidéos)
    const photoInput = document.getElementById('photo-input');
    if (photoInput) {
        photoInput.addEventListener('change', () => {
            const file = photoInput.files[0];
            if (!file) return;

            const fileReader = new FileReader();
            fileReader.onload = function (event) {
                const fileUrl = event.target.result;
                const inputMessage = document.querySelector('#message-input');
                const zoneSaisie = document.querySelector('.zone-saisie');

                if (file.type.startsWith('image/') && inputMessage && zoneSaisie) {
                    inputMessage.value += `[Image: ${file.name}]`;
                    const imgPreview = document.createElement('img');
                    imgPreview.src = fileUrl;
                    imgPreview.classList.add('img-preview');
                    zoneSaisie.appendChild(imgPreview);
                } else if (file.type.startsWith('video/') && inputMessage && zoneSaisie) {
                    inputMessage.value += `[Video: ${file.name}]`;
                    const videoPreview = document.createElement('video');
                    videoPreview.src = fileUrl;
                    videoPreview.controls = true;
                    videoPreview.classList.add('video-preview');
                    zoneSaisie.appendChild(videoPreview);
                } else {
                    console.error('Zone de saisie ou input message field not found');
                }
            };
            fileReader.readAsDataURL(file);
        });
    } else {
        console.error('Photo input not found');
    }

    // Gestion de la suppression des messages
    document.querySelectorAll('.delete').forEach(button => {
        button.addEventListener('click', function () {
            const messageId = this.getAttribute('data-message-id');
            if (confirm("Êtes-vous sûr de vouloir supprimer ce message ?")) {
                fetch('messagerie.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `delete_message=1&message_id=${messageId}`,
                })
                .then(response => response.text())
                .then(() => {
                    window.location.reload(); // Recharger la page après suppression
                })
                .catch(error => console.error('Erreur:', error));
            }
        });
    });

    // Gestion de la modification des messages
    document.addEventListener('click', function (event) {
        if (event.target.closest('.edit')) {
            const message = event.target.closest('.message');
            if (!message) {
                console.error('Message container not found');
                return;
            }

            const messageId = message.getAttribute('data-id');
            const messageBubble = message.querySelector('.message-content');
            if (!messageId || !messageBubble) {
                console.error('Message ID or message bubble not found');
                return;
            }

            const oldContent = messageBubble.textContent.trim();
            const editInput = document.createElement('input');
            editInput.type = 'text';
            editInput.value = oldContent;
            editInput.classList.add('form-control');

            messageBubble.innerHTML = '';
            messageBubble.appendChild(editInput);

            editInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    saveEdit();
                }
            });

            function saveEdit() {
                const newContent = editInput.value.trim();
                if (newContent && newContent !== oldContent) {
                    fetch(`edit_message.php?id=${messageId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ contenu: newContent })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Message successfully edited:', messageId);
                                messageBubble.textContent = newContent;
                            } else {
                                console.error('Erreur lors de la modification du message.');
                                messageBubble.textContent = oldContent;
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            messageBubble.textContent = oldContent;
                        });
                } else {
                    messageBubble.textContent = oldContent;
                }
            }
        }
    });

    // Convertir les dates UTC en dates locales
    const dateElements = document.querySelectorAll('[data-date]');
    dateElements.forEach(element => {
        const utcDate = element.getAttribute('data-date');
        const localDate = new Date(utcDate + 'Z'); // Ajouter 'Z' pour indiquer le temps UTC
        const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        element.textContent = localDate.toLocaleString([], options);
    });
});