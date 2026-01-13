/**
 * Ouvre la modal de modification de date
 * @param {number} saeId - ID de la SAE
 * @param {string} currentDateTime - Date et heure actuelles au format YYYY-MM-DD HH:MM:SS
 */
function openDateModal(saeId, currentDateTime) {
    const modal = document.getElementById('modal-date-' + saeId);
    const dateInput = document.getElementById('date-input-' + saeId);
    const timeInput = document.getElementById('time-input-' + saeId);

    if (!modal || !dateInput || !timeInput) {
        console.error('Modal ou inputs non trouvés pour SAE ID:', saeId);
        return;
    }

    // Séparer date et heure
    let datePart = '';
    let timePart = '20:00';

    if (currentDateTime && currentDateTime.trim()) {
        const parts = currentDateTime.trim().split(' ');
        datePart = parts[0] || '';
        if (parts[1]) {
            // Extraire HH:MM des HH:MM:SS
            timePart = parts[1].substring(0, 5);
        }
    }

    // Réinitialiser les valeurs
    dateInput.value = datePart;
    timeInput.value = timePart;

    // Afficher la modal
    modal.style.display = 'flex';

    // Focus sur l'input de date avec un délai pour assurer l'affichage
    setTimeout(() => {
        dateInput.focus();
    }, 150);
}

/**
 * Ferme la modal de modification de date
 * @param {number} saeId - ID de la SAE
 */
function closeDateModal(saeId) {
    const modal = document.getElementById('modal-date-' + saeId);

    if (!modal) {
        console.error('Modal non trouvée pour SAE ID:', saeId);
        return;
    }

    // Masquer la modal
    modal.style.display = 'none';
}

// Initialisation des événements au chargement de la page
document.addEventListener('DOMContentLoaded', function () {
    const modals = document.querySelectorAll('.date-modal');

    // Fermer la modal si on clique en dehors du contenu
    modals.forEach(modal => {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                const saeId = modal.id.replace('modal-date-', '');
                closeDateModal(saeId);
            }
        });
    });

    // Fermer avec le bouton croix (X)
    const closeButtons = document.querySelectorAll('.date-modal-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Trouver la modal parente
            const modal = button.closest('.date-modal');
            if (modal) {
                const saeId = modal.id.replace('modal-date-', '');
                closeDateModal(saeId);
            }
        });
    });

    // Fermer avec la touche Échap
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
            modals.forEach(modal => {
                if (modal.style.display === 'flex') {
                    const saeId = modal.id.replace('modal-date-', '');
                    closeDateModal(saeId);
                }
            });
        }
    });

    // Ajouter une confirmation avant la soumission
    const dateForms = document.querySelectorAll('form[action="/sae/update_date"]');
    dateForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const dateInput = form.querySelector('input[type="date"]');
            const timeInput = form.querySelector('input[type="time"]');

            if (!dateInput || !dateInput.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une date.');
                return;
            }

            if (!timeInput || !timeInput.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une heure.');
                return;
            }

            const formattedDate = new Date(dateInput.value + ' ' + timeInput.value).toLocaleDateString('fr-FR');
            const formattedTime = timeInput.value;

            if (!confirm(`Voulez-vous vraiment modifier la date de rendu au ${formattedDate} à ${formattedTime} ?`)) {
                e.preventDefault();
            }
        });
    });
});