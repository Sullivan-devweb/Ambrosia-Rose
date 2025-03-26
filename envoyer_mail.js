// Fonction pour envoyer un email quand un utilisateur reçoit un like
function envoyerEmailLike(userId, senderId) {
    console.log("📤 Tentative d'envoi de l'email...");
    console.log(`📩 Données envoyées -> user_id: ${userId}, sender_id: ${senderId}`);

    // Vérification que les IDs ne sont pas vides
    if (!userId || !senderId) {
        console.error("❌ Erreur : user_id ou sender_id invalide !");
        return;
    }

    // Encodage des données pour éviter les erreurs d'envoi
    let formData = new URLSearchParams();
    formData.append("user_id", userId);
    formData.append("sender_id", senderId);

    fetch('envoyer_email_like.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => {
        console.log("🔄 Réponse brute du serveur :", response);
        if (!response.ok) {
            throw new Error(`❌ Erreur HTTP : ${response.status}`);
        }
        return response.text(); // Lire en texte brut pour détecter d'éventuelles erreurs PHP
    })
    .then(text => {
        console.log("🔍 Contenu brut reçu :", text); // Affiche la réponse brute (pour détecter les erreurs PHP)
        try {
            return JSON.parse(text); // Tenter de parser en JSON
        } catch (error) {
            throw new Error("❌ La réponse n'est pas un JSON valide !");
        }
    })
    .then(data => {
        console.log("📩 Réponse JSON du serveur :", data);
        if (data.status === "success") {
            console.log("✅ Email de like envoyé !");
        } else {
            console.error("❌ Erreur serveur :", data.message);
        }
    })
    .catch(error => console.error("❌ Erreur AJAX :", error));
}

// Exécuter le script après le chargement de la page
document.addEventListener("DOMContentLoaded", function() {
    const likeForms = document.querySelectorAll("form[action='like.php']"); // Sélectionner les formulaires "Like"

    likeForms.forEach(form => {
        form.addEventListener("submit", function(event) {
            event.preventDefault(); // Empêcher l'envoi normal du formulaire

            const userId = form.querySelector("input[name='id_cible']").value; // ID du profil liké
            const senderId = document.body.getAttribute("data-user-id"); // Récupérer l'ID de l'utilisateur connecté

            if (!userId || !senderId) {
                console.error("❌ Erreur : IDs utilisateur/profil non trouvés !");
                return;
            }

            envoyerEmailLike(userId, senderId); // Envoyer l'email après le like

            // ✅ Attendre un peu avant de soumettre le formulaire pour éviter les conflits AJAX
            setTimeout(() => {
                console.log("🔄 Soumission du formulaire après 300ms...");
                form.submit();
            }, 300);
        });
    });
});
