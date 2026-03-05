document.addEventListener("DOMContentLoaded", function() {
    const expString = document.body.getAttribute('data-session-exp');

    if (!expString || expString === '0') return;

    const expTime = parseInt(expString, 10);
    const toast = document.getElementById('session-warning-toast');
    if (!toast) return;

    const key5min = 'warn5_' + expTime;
    const key1min = 'warn1_' + expTime;

    let warning5MinShown = sessionStorage.getItem(key5min) === 'true';
    let warning1MinShown = sessionStorage.getItem(key1min) === 'true';
    let hideTimeout;

    // --- NOUVEAUTÉ : Faire disparaître le message au clic ---
    toast.addEventListener('click', function() {
        toast.classList.remove('show');
        if (hideTimeout) clearTimeout(hideTimeout);
    });

    function showWarning(message, isUrgent = false) {
        // On ajoute une petite mention pour dire que c'est cliquable
        toast.innerHTML = '<strong>⚠️ Attention :</strong> ' + message +
            '<br><small style="opacity: 0.8; font-size: 0.8em;">(Cliquez pour masquer)</small>';

        if (isUrgent) {
            toast.classList.add('toast-urgent');
        } else {
            toast.classList.remove('toast-urgent');
        }

        toast.classList.add('show');

        // On nettoie l'ancien timer s'il y en a un
        if (hideTimeout) clearTimeout(hideTimeout);

        // --- NOUVEAUTÉ : 30s pour l'urgent, 10s pour le normal ---
        const timeToDisplay = isUrgent ? 30000 : 10000;

        hideTimeout = setTimeout(() => {
            toast.classList.remove('show');
        }, timeToDisplay);
    }

    const interval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        const timeLeft = expTime - now;

        if (timeLeft <= -1) {
            clearInterval(interval);
            sessionStorage.removeItem(key5min);
            sessionStorage.removeItem(key1min);
            window.location.href = '/user/profile';

        } else if (timeLeft <= 60 && !warning1MinShown) {
            warning1MinShown = true;
            sessionStorage.setItem(key1min, 'true');
            showWarning("Votre session expire dans 1 minute ! Pensez à sauvegarder immédiatement.", true);

        } else if (timeLeft <= 300 && timeLeft > 60 && !warning5MinShown) {
            warning5MinShown = true;
            sessionStorage.setItem(key5min, 'true');
            showWarning("Votre session expirera dans 5 minutes. Pensez à sauvegarder.", false);
        }
    }, 1000);
});
