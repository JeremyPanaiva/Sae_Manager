document.addEventListener("DOMContentLoaded", function() {
    // 1. Récupération de la date d'expiration depuis l'attribut data du body
    const expString = document.body.getAttribute('data-session-exp');

    // Si l'attribut n'existe pas ou vaut 0 (utilisateur non connecté), on arrête le script
    if (!expString || expString === '0') return;

    const expTime = parseInt(expString, 10);
    let warning5MinShown = false;
    let warning1MinShown = false;
    const toast = document.getElementById('session-warning-toast');

    if (!toast) return;

    // Fonction pour afficher le message
    function showWarning(message) {
        toast.innerHTML = '<strong>⚠️ Attention :</strong> ' + message;
        toast.classList.add('show');

        // Masquer la notification après 10 secondes
        setTimeout(() => {
            toast.classList.remove('show');
        }, 10000);
    }

    // Lancement du compte à rebours
    const interval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        const timeLeft = expTime - now;

        if (timeLeft <= 0) {
            clearInterval(interval);
            // Le temps est écoulé : on rafraîchit la page
            window.location.reload();
        } else if (timeLeft <= 60 && !warning1MinShown) {
            warning1MinShown = true;
            showWarning("Votre session expire dans 1 minute !");
        } else if (timeLeft <= 300 && !warning5MinShown) {
            warning5MinShown = true;
            showWarning("Votre session expirera dans 5 minutes. Pensez à sauvegarder.");
        }
    }, 1000);
});

