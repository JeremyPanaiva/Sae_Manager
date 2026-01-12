/**
 * Ouvre la modal de modification de date
 * @param {number} saeId - ID de la SAE
 * @param {string} currentDate - Date actuelle au format YYYY-MM-DD
 */
function openDateModal(saeId, currentDate) {
    const modal = document.getElementById('modal-date-' + saeId);
    const dateInput = document.getElementById('date-input-' + saeId);

    // Réinitialiser la date à la valeur actuelle
    dateInput. value = currentDate;

    // Afficher la modal
    modal.style.display = 'flex';

    // Focus sur l'input de date
    setTimeout(() => {
        dateInput. focus();
    }, 100);
}

/**
 * Ferme la modal de modification de date
 * @param {number} saeId - ID de la SAE
 */
function closeDateModal(saeId) {
    const modal = document.getElementById('modal-date-' + saeId);
    modal.style.display = 'none';
}

// Fermer la modal si on clique en dehors du contenu
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.date-modal');

    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event. target === modal) {
                const saeId = modal.id.replace('modal-date-', '');
                closeDateModal(saeId);
            }
        });
    });

    // Fermer avec la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.style.display === 'flex') {
                    const saeId = modal.id. replace('modal-date-', '');
                    closeDateModal(saeId);
                }
            });
        }
    });

    // Ajouter une confirmation avant la soumission
    const dateForms = document.querySelectorAll('form[action="/sae/update_date"]');
    dateForms.forEach(form => {
        form. addEventListener('submit', function(e) {
            const dateInput = form.querySelector('input[type="date"]');
            const formattedDate = new Date(dateInput.value).toLocaleDateString('fr-FR');

            if (! confirm(`Voulez-vous vraiment modifier la date de rendu au ${formattedDate} ?`)) {
                e.preventDefault();
            }
        });
    });
});