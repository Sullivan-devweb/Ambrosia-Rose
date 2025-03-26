/**
 * Gestionnaire principal de la messagerie
 * @module Messagerie
 * @description Ce module gère les fonctionnalités complètes d'une messagerie :
 * - Enregistrement vocal
 * - Upload de fichiers (images/vidéos)
 * - Suppression de messages
 * - Modification de messages
 * - Conversion des dates UTC en locales
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded and parsed');

    /**
     * @type {MediaRecorder}
     * @description Instance du MediaRecorder pour l'enregistrement audio
     */
    let mediaRecorder;
    
    /**
     * @type {Blob[]}
     * @description Tableau des chunks audio enregistrés
     */
    let audioChunks = [];
    
    /**
     * @type {boolean}
     * @description État de l'enregistrement (true = en cours)
     */
    let isRecording = false;

    const voiceBtn = document.querySelector('.voice-btn');
    const messageInput = document.getElementById('message-input');
    const form = document.querySelector('form');
    let audioPreview;

    // Section Enregistrement Vocal
    if (voiceBtn) {
        /**
         * Gère l'enregistrement vocal
         * @function handleVoiceRecording
         * @async
         */
        voiceBtn.addEventListener('click', async () => {
            if (isRecording) {
                stopRecording();
            } else {
                await startRecording();
            }
        });

        /**
         * Démarre l'enregistrement vocal
         * @async
         * @function startRecording
         */
        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = handleRecordingStop;

                mediaRecorder.start();
                voiceBtn.style.backgroundColor = "#ff4c4c";
                isRecording = true;
            } catch (err) {
                alert("Impossible d'accéder au micro. Assurez-vous que l'autorisation est accordée.");
                console.error('Erreur accès micro:', err);
            }
        }

        /**
         * Arrête l'enregistrement et gère le résultat
         * @function stopRecording
         */
        function stopRecording() {
            mediaRecorder.stop();
            isRecording = false;
            voiceBtn.style.backgroundColor = "";
        }

        /**
         * Gère la fin de l'enregistrement
         * @function handleRecordingStop
         */
        function handleRecordingStop() {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const audioUrl = URL.createObjectURL(audioBlob);

            createAudioPreview(audioUrl);
            prepareAudioFormData(audioBlob);

            audioChunks = [];
        }

        /**
         * Crée un aperçu audio
         * @function createAudioPreview
         * @param {string} audioUrl - URL de l'audio
         */
        function createAudioPreview(audioUrl) {
            audioPreview = document.createElement('audio');
            audioPreview.src = audioUrl;
            audioPreview.controls = true;
            audioPreview.style.width = '100%';
            document.querySelector('.zone-saisie').appendChild(audioPreview);
        }

        /**
         * Prépare les données audio pour l'envoi
         * @function prepareAudioFormData
         * @param {Blob} audioBlob - Blob audio
         */
        function prepareAudioFormData(audioBlob) {
            const formData = new FormData();
            formData.append('voiceMessage', audioBlob);
            formData.append('message', messageInput.value);

            form.onsubmit = async (e) => {
                e.preventDefault();
                await submitFormData(formData);
            };
        }

        /**
         * Soumet les données du formulaire
         * @async
         * @function submitFormData
         * @param {FormData} formData - Données à envoyer
         */
        async function submitFormData(formData) {
            try {
                await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                window.location.href = form.action;
            } catch (error) {
                console.error('Erreur envoi:', error);
            }
        }
    } else {
        console.error('Voice button not found');
    }

    // Section Upload de Fichiers
    const photoInput = document.getElementById('photo-input');
    if (photoInput) {
        /**
         * Gère l'upload de fichiers
         * @function handleFileUpload
         */
        photoInput.addEventListener('change', handleFileUpload);

        /**
         * Traite le fichier uploadé
         * @function handleFileUpload
         */
        function handleFileUpload() {
            const file = photoInput.files[0];
            if (!file) return;

            const fileReader = new FileReader();
            fileReader.onload = function (event) {
                processUploadedFile(file, event.target.result);
            };
            fileReader.readAsDataURL(file);
        }

        /**
         * Traite le fichier uploadé (image ou vidéo)
         * @function processUploadedFile
         * @param {File} file - Fichier uploadé
         * @param {string} fileUrl - URL du fichier
         */
        function processUploadedFile(file, fileUrl) {
            const inputMessage = document.querySelector('#message-input');
            const zoneSaisie = document.querySelector('.zone-saisie');

            if (!inputMessage || !zoneSaisie) {
                console.error('Zone de saisie non trouvée');
                return;
            }

            if (file.type.startsWith('image/')) {
                handleImageUpload(file, fileUrl, inputMessage, zoneSaisie);
            } else if (file.type.startsWith('video/')) {
                handleVideoUpload(file, fileUrl, inputMessage, zoneSaisie);
            }
        }

        /**
         * Gère l'upload d'image
         * @function handleImageUpload
         * @param {File} file - Fichier image
         * @param {string} fileUrl - URL de l'image
         * @param {HTMLElement} inputMessage - Champ de message
         * @param {HTMLElement} zoneSaisie - Zone d'affichage
         */
        function handleImageUpload(file, fileUrl, inputMessage, zoneSaisie) {
            inputMessage.value += `[Image: ${file.name}]`;
            const imgPreview = document.createElement('img');
            imgPreview.src = fileUrl;
            imgPreview.classList.add('img-preview');
            zoneSaisie.appendChild(imgPreview);
        }

        /**
         * Gère l'upload de vidéo
         * @function handleVideoUpload
         * @param {File} file - Fichier vidéo
         * @param {string} fileUrl - URL de la vidéo
         * @param {HTMLElement} inputMessage - Champ de message
         * @param {HTMLElement} zoneSaisie - Zone d'affichage
         */
        function handleVideoUpload(file, fileUrl, inputMessage, zoneSaisie) {
            inputMessage.value += `[Video: ${file.name}]`;
            const videoPreview = document.createElement('video');
            videoPreview.src = fileUrl;
            videoPreview.controls = true;
            videoPreview.classList.add('video-preview');
            zoneSaisie.appendChild(videoPreview);
        }
    } else {
        console.error('Photo input not found');
    }

    // Section Suppression de Messages
    document.querySelectorAll('.delete').forEach(button => {
        /**
         * Gère la suppression d'un message
         * @function handleMessageDeletion
         */
        button.addEventListener('click', function handleMessageDeletion() {
            const messageId = this.getAttribute('data-message-id');
            if (confirm("Êtes-vous sûr de vouloir supprimer ce message ?")) {
                deleteMessage(messageId);
            }
        });
    });

    /**
     * Supprime un message
     * @async
     * @function deleteMessage
     * @param {string} messageId - ID du message à supprimer
     */
    async function deleteMessage(messageId) {
        try {
            await fetch('messagerie.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `delete_message=1&message_id=${messageId}`
            });
            window.location.reload();
        } catch (error) {
            console.error('Erreur suppression:', error);
        }
    }

    // Section Modification de Messages
    document.addEventListener('click', function (event) {
        if (event.target.closest('.edit')) {
            handleMessageEdit(event);
        }
    });

    /**
     * Gère la modification d'un message
     * @function handleMessageEdit
     * @param {Event} event - Événement de clic
     */
    function handleMessageEdit(event) {
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
        const editInput = createEditInput(oldContent);
        
        messageBubble.innerHTML = '';
        messageBubble.appendChild(editInput);

        setupEditInputHandlers(editInput, messageBubble, oldContent, messageId);
    }

    /**
     * Crée un champ d'édition
     * @function createEditInput
     * @param {string} value - Valeur initiale
     * @returns {HTMLInputElement} Champ d'édition
     */
    function createEditInput(value) {
        const editInput = document.createElement('input');
        editInput.type = 'text';
        editInput.value = value;
        editInput.classList.add('form-control');
        return editInput;
    }

    /**
     * Configure les gestionnaires d'événements pour l'édition
     * @function setupEditInputHandlers
     * @param {HTMLInputElement} input - Champ d'édition
     * @param {HTMLElement} messageBubble - Bulle de message
     * @param {string} oldContent - Contenu original
     * @param {string} messageId - ID du message
     */
    function setupEditInputHandlers(input, messageBubble, oldContent, messageId) {
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                saveMessageEdit(input.value.trim(), oldContent, messageBubble, messageId);
            }
        });
    }

    /**
     * Sauvegarde les modifications d'un message
     * @async
     * @function saveMessageEdit
     * @param {string} newContent - Nouveau contenu
     * @param {string} oldContent - Ancien contenu
     * @param {HTMLElement} messageBubble - Bulle de message
     * @param {string} messageId - ID du message
     */
    async function saveMessageEdit(newContent, oldContent, messageBubble, messageId) {
        if (newContent && newContent !== oldContent) {
            try {
                const response = await fetch(`edit_message.php?id=${messageId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ contenu: newContent })
                });
                const data = await response.json();
                
                if (data.success) {
                    messageBubble.textContent = newContent;
                } else {
                    throw new Error('Échec de la modification');
                }
            } catch (error) {
                console.error('Erreur modification:', error);
                messageBubble.textContent = oldContent;
            }
        } else {
            messageBubble.textContent = oldContent;
        }
    }

    // Section Conversion des Dates
    const dateElements = document.querySelectorAll('[data-date]');
    dateElements.forEach(element => {
        convertUTCDateToLocal(element);
    });

    /**
     * Convertit une date UTC en date locale
     * @function convertUTCDateToLocal
     * @param {HTMLElement} element - Élément contenant la date
     */
    function convertUTCDateToLocal(element) {
        const utcDate = element.getAttribute('data-date');
        const localDate = new Date(utcDate + 'Z');
        const options = { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        };
        element.textContent = localDate.toLocaleString([], options);
    }
});