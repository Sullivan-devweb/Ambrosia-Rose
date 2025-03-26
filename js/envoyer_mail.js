/**
 * @file Gestion de l'envoi d'emails lors de la réception d'un like
 * @module EmailLike
 * @version 1.1.0
 * @requires fetch
 * @description Ce module gère l'envoi d'emails lorsqu'un utilisateur reçoit un like,
 * avec gestion des erreurs et logging détaillé.
 */

// Constantes de configuration
const EMAIL_API_URL = 'envoyer_email_like.php';
const FORM_DELAY_MS = 300;

/**
 * Envoie un email de notification pour un like
 * @async
 * @function envoyerEmailLike
 * @param {string} userId - ID de l'utilisateur qui reçoit le like
 * @param {string} senderId - ID de l'utilisateur qui envoie le like
 * @returns {Promise<void>}
 * @throws {TypeError} Si les paramètres sont invalides
 * @throws {Error} Si l'envoi échoue
 * @example
 * // Dans un gestionnaire de like :
 * try {
 *   await envoyerEmailLike('123', '456');
 * } catch (error) {
 *   console.error(error);
 * }
 */
async function envoyerEmailLike(userId, senderId) {
    // Validation des paramètres
    if (typeof userId !== 'string' || !userId.trim() ||
        typeof senderId !== 'string' || !senderId.trim()) {
        throw new TypeError("Les IDs utilisateur doivent être des chaînes non vides");
    }

    console.log("📤 Tentative d'envoi de l'email...");
    console.log(`📩 Données envoyées -> user_id: ${userId}, sender_id: ${senderId}`);

    // Préparation des données
    const formData = new URLSearchParams();
    formData.append("user_id", userId.trim());
    formData.append("sender_id", senderId.trim());

    try {
        const response = await fetch(EMAIL_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        });

        const data = await processResponse(response);
        
        if (data.status !== "success") {
            throw new Error(data.message || "Échec de l'envoi de l'email");
        }
        
        console.log("✅ Email de like envoyé !");
    } catch (error) {
        console.error("❌ Erreur lors de l'envoi:", error);
        throw error; // Propagation de l'erreur
    }
}

/**
 * Traite la réponse du serveur
 * @async
 * @function processResponse
 * @param {Response} response - Réponse fetch
 * @returns {Promise<Object>} Données parsées
 * @throws {Error} Si la réponse est invalide
 */
async function processResponse(response) {
    console.log("🔄 Statut HTTP:", response.status);
    
    if (!response.ok) {
        throw new Error(`Erreur HTTP ${response.status}`);
    }

    const text = await response.text();
    console.log("🔍 Réponse brute:", text);

    try {
        return JSON.parse(text);
    } catch (error) {
        throw new Error("Réponse serveur invalide (non-JSON)");
    }
}

/**
 * Initialise les gestionnaires d'événements pour les likes
 * @function initLikeHandlers
 * @returns {void}
 */
function initLikeHandlers() {
    const likeForms = document.querySelectorAll("form[data-like-form]");

    likeForms.forEach(form => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            await handleLikeFormSubmit(form);
        });
    });
}

/**
 * Gère la soumission d'un formulaire de like
 * @async
 * @function handleLikeFormSubmit
 * @param {HTMLFormElement} form - Formulaire de like
 * @returns {Promise<void>}
 */
async function handleLikeFormSubmit(form) {
    const userIdInput = form.querySelector("[name='id_cible']");
    const senderId = document.body.dataset.userId;

    if (!userIdInput?.value || !senderId) {
        console.error("IDs utilisateur/profil manquants");
        return;
    }

    try {
        await envoyerEmailLike(userIdInput.value, senderId);
        
        // Soumission différée
        await new Promise(resolve => setTimeout(resolve, FORM_DELAY_MS));
        form.submit();
        
    } catch (error) {
        console.error("Échec du traitement du like:", error);
        // Ici vous pourriez afficher un message à l'utilisateur
    }
}

// Initialisation sécurisée
if (document.readyState !== 'loading') {
    initLikeHandlers();
} else {
    document.addEventListener('DOMContentLoaded', initLikeHandlers);
}