/**
 * Message Modal for Responsable
 * Allows sending predefined or custom messages to students
 * SAE Manager - Harmonisé avec la charte AMU
 */

// Predefined message templates
const messageTemplates = {
    reminder: {
        subject: "Rappel : Date limite de rendu",
        message: "Bonjour,\n\nJe vous rappelle que la date limite de rendu de votre SAE approche.\n\nMerci de vous assurer que vous êtes à jour dans votre travail et n'hésitez pas à me contacter si vous avez des questions.\n\nBon courage !"
    },
    meeting: {
        subject: "Convocation : Réunion de suivi",
        message: "Bonjour,\n\nJe souhaite organiser une réunion de suivi concernant votre SAE.\n\nMerci de me proposer vos disponibilités pour la semaine prochaine.\n\nCordialement."
    },
    feedback: {
        subject: "Retour sur votre travail",
        message: "Bonjour,\n\nJ'ai examiné votre dernier rendu et je souhaite vous faire un retour.\n\n[Ajoutez vos commentaires ici]\n\nN'hésitez pas à me contacter si vous avez des questions.\n\nBon travail !"
    },
    congratulations: {
        subject: "Félicitations pour votre travail",
        message: "Bonjour,\n\nJe tiens à vous féliciter pour la qualité de votre travail sur votre SAE.\n\nContinuez ainsi !\n\nCordialement."
    },
    urgent: {
        subject: "URGENT : Action requise",
        message: "Bonjour,\n\nJ'ai besoin de votre attention concernant un point urgent sur votre SAE.\n\nMerci de me contacter dès que possible.\n\nCordialement."
    }
};

/**
 * Opens the message modal
 */
function openMessageModal() {
    const modal = document.getElementById('messageModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.classList.add('modal-open');

        // Animation d'entrée
        setTimeout(() => {
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.opacity = '1';
                modalContent.style.transform = 'translateY(0)';
            }
        }, 10);
    }
}

/**
 * Closes the message modal
 */
function closeMessageModal() {
    const modal = document.getElementById('messageModal');
    if (modal) {
        const modalContent = modal.querySelector('.modal-content');

        // Animation de sortie
        if (modalContent) {
            modalContent.style.opacity = '0';
            modalContent.style.transform = 'translateY(-50px)';
        }

        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');

            // Reset form
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        }, 300);
    }
}

/**
 * Loads a predefined template into the form
 */
function loadTemplate() {
    const templateSelect = document.getElementById('messageTemplate');
    const subjectInput = document.getElementById('messageSubject');
    const messageTextarea = document.getElementById('messageContent');

    if (!templateSelect || !subjectInput || !messageTextarea) {
        console.error('Form elements not found');
        return;
    }

    const selectedTemplate = templateSelect.value;

    if (selectedTemplate && messageTemplates[selectedTemplate]) {
        subjectInput.value = messageTemplates[selectedTemplate].subject;
        messageTextarea.value = messageTemplates[selectedTemplate].message;

        // Animation subtile pour indiquer le changement
        subjectInput.style.backgroundColor = '#e3f2fd';
        messageTextarea.style.backgroundColor = '#e3f2fd';

        setTimeout(() => {
            subjectInput.style.backgroundColor = '';
            messageTextarea.style.backgroundColor = '';
        }, 500);
    } else {
        // Si aucun template sélectionné, vider les champs
        subjectInput.value = '';
        messageTextarea.value = '';
    }
}

/**
 * Validates the message form before submission
 */
function validateMessageForm() {
    const studentId = document.getElementById('studentSelect').value;
    const subject = document.getElementById('messageSubject').value.trim();
    const message = document.getElementById('messageContent').value.trim();

    if (!studentId) {
        showFormError('Veuillez sélectionner un étudiant.');
        document.getElementById('studentSelect').focus();
        return false;
    }

    if (!subject) {
        showFormError('Veuillez saisir un objet pour le message.');
        document.getElementById('messageSubject').focus();
        return false;
    }

    if (subject.length > 200) {
        showFormError('L\'objet ne doit pas dépasser 200 caractères.');
        document.getElementById('messageSubject').focus();
        return false;
    }

    if (!message) {
        showFormError('Veuillez saisir un message.');
        document.getElementById('messageContent').focus();
        return false;
    }

    if (message.length < 10) {
        showFormError('Le message est trop court (minimum 10 caractères).');
        document.getElementById('messageContent').focus();
        return false;
    }

    return true;
}

/**
 * Shows an error message
 */
function showFormError(errorMessage) {
    // Créer ou réutiliser un élément d'erreur
    let errorDiv = document.querySelector('.form-validation-error');

    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'form-validation-error message-error';
        errorDiv.style.cssText = 'margin: 10px 0; animation: slideIn 0.3s ease;';

        const form = document.querySelector('#messageModal form');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
        }
    }

    errorDiv.textContent = '⚠️ ' + errorMessage;

    // Faire disparaître après 5 secondes
    setTimeout(() => {
        if (errorDiv && errorDiv.parentNode) {
            errorDiv.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                if (errorDiv && errorDiv.parentNode) {
                    errorDiv.parentNode.removeChild(errorDiv);
                }
            }, 300);
        }
    }, 5000);
}

// Close modal when clicking outside of it
window.addEventListener('click', function(event) {
    const modal = document.getElementById('messageModal');
    if (event.target === modal) {
        closeMessageModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('messageModal');
        if (modal && modal.style.display === 'block') {
            closeMessageModal();
        }
    }
});

// Animation CSS pour fadeOut
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(-20px);
        }
    }
`;
document.head.appendChild(style);

console.log('Message modal script loaded successfully ✅');