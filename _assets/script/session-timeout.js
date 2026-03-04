document.addEventListener("DOMContentLoaded", function() {
    const expString = document.body.getAttribute('data-session-exp');

    // Si l'attribut n'existe pas ou vaut 0 (utilisateur non connecté), on arrête le script
    if (!expString || expString === '0') return;

    const expTime = parseInt(expString, 10);
    let warning5MinShown = false;
    let warning1MinShown = false;
    let hideTimeout; // Permet de gérer la disparition du message

    const toast = document.getElementById('session-warning-toast');
    if (!toast) return;

    /**
     * Affiche le message d'alerte
     * @param {string} message - Le texte à afficher
     * @param {boolean} isUrgent - Si true, modifie le style et garde le message affiché
     */
    function showWarning(message, isUrgent = false) {
        toast.innerHTML = '<strong>⚠️ Attention :</strong> ' + message;

        // Gestion du style urgent (rouge, sous le header)
        if (isUrgent) {
            toast.classList.add('toast-urgent');
        } else {
            toast.classList.remove('toast-urgent');
        }

        toast.classList.add('show');

        // On nettoie le minuteur précédent s'il y en avait un
        if (hideTimeout) {
            clearTimeout(hideTimeout);
        }

        // Si ce n'est PAS urgent (5 min), on masque le message après 10 secondes.
        // Si c'est urgent (1 min), on ne met pas de timeout : le message reste affiché !
        if (!isUrgent) {
            hideTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 10000);
        }
    }

    const interval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        const timeLeft = expTime - now;

        // On met -1 pour palier aux minuscules décalages d'horloge entre JS et PHP
        if (timeLeft <= -1) {
            clearInterval(interval);

            // On redirige vers une page protégée pour forcer le SessionGuard
            // à détruire la session et à créer le log dans la base de données.
            window.location.href = '/user/profile';

        } else if (timeLeft <= 60 && !warning1MinShown) {
            warning1MinShown = true;
            showWarning("Votre session expire dans 1 minute ! Pensez à sauvegarder immédiatement.", true);

        } else if (timeLeft <= 300 && !warning5MinShown) {
            warning5MinShown = true;
            showWarning("Votre session expirera dans 5 minutes. Pensez à sauvegarder.", false);
        }
    }, 1000);
});
