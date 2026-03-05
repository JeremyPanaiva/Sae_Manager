document.addEventListener("DOMContentLoaded", function() {
    const expString = document.body.getAttribute('data-session-exp');

    if (!expString || expString === '0') return;

    const expTime = parseInt(expString, 10);
    const toast = document.getElementById('session-warning-toast');
    if (!toast) return;

    // Création de clés uniques basées sur l'expiration (évite les bugs si l'utilisateur se reconnecte)
    const key5min = 'warn5_' + expTime;
    const key1min = 'warn1_' + expTime;

    // On vérifie dans la mémoire du navigateur si on a DÉJÀ affiché ces alertes
    let warning5MinShown = sessionStorage.getItem(key5min) === 'true';
    let warning1MinShown = sessionStorage.getItem(key1min) === 'true';
    let hideTimeout;

    function showWarning(message, isUrgent = false) {
        toast.innerHTML = '<strong>⚠️ Attention :</strong> ' + message;

        if (isUrgent) {
            toast.classList.add('toast-urgent');
        } else {
            toast.classList.remove('toast-urgent');
        }

        toast.classList.add('show');

        if (hideTimeout) clearTimeout(hideTimeout);

        if (!isUrgent) {
            hideTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 10000);
        }
    }

    const interval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        const timeLeft = expTime - now;

        if (timeLeft <= -1) {
            clearInterval(interval);
            // On nettoie la mémoire avant de déconnecter l'utilisateur
            sessionStorage.removeItem(key5min);
            sessionStorage.removeItem(key1min);
            window.location.href = '/user/profile';

        } else if (timeLeft <= 60 && !warning1MinShown) {
            warning1MinShown = true;
            sessionStorage.setItem(key1min, 'true'); // On mémorise qu'on l'a affiché
            showWarning("Votre session expire dans 1 minute ! Pensez à sauvegarder immédiatement.", true);

        } else if (timeLeft <= 300 && timeLeft > 60 && !warning5MinShown) {
            warning5MinShown = true;
            sessionStorage.setItem(key5min, 'true'); // On mémorise qu'on l'a affiché
            showWarning("Votre session expirera dans 5 minutes. Pensez à sauvegarder.", false);
        }
    }, 1000);
});
