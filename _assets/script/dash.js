/**
 * Composant de compte à rebours amélioré pour les SAEs
 * Mise à jour en temps réel avec gestion des états d'urgence
 * Compatible avec le projet SAE Manager
 */

class CountdownTimer {
    constructor(element) {
        this.element = element;

        // Récupération de la date depuis l'attribut data-date
        const dateString = element.dataset.date;
        if (!dateString) {
            console.error('Aucune date fournie pour le countdown', element);
            return;
        }

        this.targetDate = new Date(dateString);

        // Vérification de la validité de la date
        if (isNaN(this.targetDate.getTime())) {
            console.error('Date invalide:', dateString, element);
            this.element.textContent = 'Date invalide';
            return;
        }

        this. init();
    }

    init() {
        // Mise à jour immédiate
        this.update();

        // Mise à jour chaque seconde
        this.interval = setInterval(() => this.update(), 1000);
    }

    update() {
        const now = new Date();
        const difference = this.targetDate - now;

        // Si la date est dépassée
        if (difference <= 0) {
            this. displayExpired();
            clearInterval(this.interval);
            return;
        }

        // Calcul du temps restant
        const time = this.calculateTime(difference);
        this.displayTime(time);
        this.updateUrgencyClass(difference);
    }

    calculateTime(difference) {
        const days = Math.floor(difference / (1000 * 60 * 60 * 24));
        const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);

        return { days, hours, minutes, seconds };
    }

    displayTime(time) {
        const { days, hours, minutes, seconds } = time;

        // Format d'affichage adaptatif
        let displayText = '';

        if (days > 0) {
            displayText = `${days}j ${hours}h ${minutes}m ${seconds}s`;
        } else if (hours > 0) {
            displayText = `${hours}h ${minutes}m ${seconds}s`;
        } else if (minutes > 0) {
            displayText = `${minutes}m ${seconds}s`;
        } else {
            displayText = `${seconds}s`;
        }

        this.element.textContent = displayText;
    }

    updateUrgencyClass(difference) {
        const days = difference / (1000 * 60 * 60 * 24);

        // Retirer toutes les classes d'urgence
        this.element.classList.remove('countdown-safe', 'countdown-warning', 'countdown-danger', 'countdown-expired');

        // Ajouter la classe appropriée selon le temps restant
        if (days > 7) {
            this.element. classList.add('countdown-safe');
        } else if (days > 3) {
            this.element.classList.add('countdown-warning');
        } else {
            this.element.classList. add('countdown-danger');
        }
    }

    displayExpired() {
        this.element.textContent = 'Délai dépassé';
        this.element.classList.remove('countdown-safe', 'countdown-warning', 'countdown-danger');
        this.element.classList. add('countdown-expired');
    }

    destroy() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    }
}

// Initialisation automatique au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les éléments avec la classe 'countdown'
    const countdownElements = document.querySelectorAll('.countdown');

    if (countdownElements.length === 0) {
        console.log('Aucun élément countdown trouvé sur cette page');
        return;
    }

    console.log(`${countdownElements.length} countdown(s) initialisé(s)`);

    // Initialiser chaque countdown
    countdownElements. forEach(element => {
        try {
            new CountdownTimer(element);
        } catch (error) {
            console.error('Erreur lors de l\'initialisation du countdown:', error, element);
        }
    });
});

// Nettoyage des timers lors du déchargement de la page
window.addEventListener('beforeunload', function() {
    const countdowns = document.querySelectorAll('. countdown');
    countdowns.forEach(el => {
        if (el._countdownTimer) {
            el._countdownTimer.destroy();
        }
    });
});