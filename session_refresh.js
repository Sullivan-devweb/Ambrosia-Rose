// Rafraîchissement automatique de la session
function refreshSession() {
    fetch("session_refresh.php")
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // Rediriger l'utilisateur si la session a expiré
                window.location.href = "https://ambrosiarose.404cahorsfound.fr/?timeout=1";
            }
        })
        .catch(error => console.error("Erreur de rafraîchissement de session :", error));
}

// Exécuter la fonction toutes les 5 minutes (ajuste selon besoin)
setInterval(refreshSession, 5 * 60 * 1000);
