/**
 * @file Gestion du rafraîchissement automatique de session
 * @module SessionRefresh
 * @version 1.0.0
 * @requires fetch
 * @description Ce module assure le rafraîchissement automatique de la session utilisateur
 * et redirige en cas d'expiration.
 */

const SESSION_REFRESH_URL = "session_refresh.php";
const SESSION_CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutes
const TIMEOUT_REDIRECT_URL = "https://ambrosiarose.404cahorsfound.fr/?timeout=1";

/**
 * Vérifie et rafraîchit la session utilisateur
 * @async
 * @function refreshSession
 * @returns {Promise<void>}
 */
async function refreshSession() {
    try {
        const response = await fetch(SESSION_REFRESH_URL);
        const data = await response.json();
        
        if (!data.success) {
            console.warn("⏳ Session expirée, redirection en cours...");
            window.location.href = TIMEOUT_REDIRECT_URL;
        }
    } catch (error) {
        console.error("❌ Erreur de rafraîchissement de session :", error);
    }
}

// Exécuter la fonction périodiquement
setInterval(refreshSession, SESSION_CHECK_INTERVAL);

// Export du module
export { refreshSession };
