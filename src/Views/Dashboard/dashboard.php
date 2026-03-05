<link rel="stylesheet" href="/_assets/css/dashboard.css">
<link rel="stylesheet" href="/_assets/css/message-modal-styles.css">
<script src="/_assets/script/dash.js"></script>
<script src="/_assets/script/date-modal.js"></script>

<?php
/**
 * @var string $ROLE_KEY
 * @var string $USERNAME_KEY
 * @var string $CONTENT_KEY
 * @var array<int, array{
 *     sae_id:int,
 *     sae_name:string,
 *     students:array<int, array{id:int, prenom:string, nom:string}>
 * }> $MESSAGE_RECIPIENTS_KEY
 */
?>

<section class="main dashboard-page" aria-label="Tableau de bord">
    <fieldset class="dashboard-section">
        <legend>Tableau de bord - <?php echo $ROLE_KEY; ?></legend>
        <div class="user-info">
            <p><strong>Nom :</strong> <?php echo $USERNAME_KEY; ?></p>
            <p><strong>Rôle :</strong> <?php echo $ROLE_KEY; ?></p>
        </div>
        <hr>
        <div class="dashboard-content">
            <?php echo $CONTENT_KEY; ?>
        </div>
    </fieldset>
</section>

<div id="messageModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeMessageModal()">&times;</span>
        <h2>📧 Envoyer un message à un étudiant</h2>

        <div class="template-info">
            💡 Astuce : Sélectionnez un message pré-rempli ci-dessous,
            puis modifiez-le selon vos besoins.
        </div>

        <form
                action="/dashboard/send-message"
                method="POST"
                onsubmit="return validateMessageForm()"
        >

            <div class="form-group">
                <label for="messageTemplate">Message pré-rempli :</label>
                <select id="messageTemplate" onchange="loadTemplate()">
                    <option value="">-- Sélectionner un modèle --</option>
                    <option value="reminder">📅 Rappel de deadline</option>
                    <option value="meeting">👥 Convocation réunion</option>
                    <option value="feedback">💬 Retour sur le travail</option>
                    <option value="congratulations">🎉 Félicitations</option>
                    <option value="urgent">⚠️ Message urgent</option>
                </select>
            </div>

            <div class="form-group">
                <label>Étudiants destinataires : *</label>
                <div class="student-selection-info">
                    <small>Sélectionnez les étudiants par SAE :</small>
                </div>

                <div class="checkbox-controls">
                    <button
                            type="button"
                            class="btn-select-all"
                            onclick="selectAllStudents()"
                    >
                        Tout sélectionner
                    </button>
                    <button
                            type="button"
                            class="btn-deselect-all"
                            onclick="deselectAllStudents()"
                    >
                        Tout désélectionner
                    </button>
                </div>

                <div class="student-checkbox-list">
                    <?php
                    $messageRecipients = $MESSAGE_RECIPIENTS_KEY ?? [];

                    if (empty($messageRecipients)) {
                        echo '<p style="color: #666; font-style: italic;">';
                        echo 'Aucune SAE trouvée pour ce responsable.';
                        echo '</p>';
                    } else {
                        foreach ($messageRecipients as $saeData) {
                            $saeId = isset($saeData['sae_id']) ? (int) $saeData['sae_id'] : 0;
                            $saeName = htmlspecialchars((string) ($saeData['sae_name'] ?? 'SAE'));
                            $students = $saeData['students'] ?? [];

                            if ($saeId <= 0 || !is_array($students) || empty($students)) {
                                continue;
                            }

                            $studentCount = count($students);

                            echo '<div class="sae-group">';
                            echo '<div class="sae-group-header" ';
                            echo 'onclick="toggleSaeGroup(' . $saeId . ')">';
                            echo '<span class="sae-toggle-icon" ';
                            echo 'id="toggle-icon-' . $saeId . '">▼</span>';
                            echo '<strong>' . $saeName . '</strong>';
                            echo '<span class="sae-student-count">';
                            echo '(' . $studentCount . ' étudiant';
                            echo ($studentCount > 1 ? 's' : '') . ')';
                            echo '</span>';
                            echo '<button type="button" ';
                            echo 'class="btn-select-sae" ';
                            echo 'onclick="event.stopPropagation(); ';
                            echo 'toggleSaeSelection(' . $saeId . ')">';
                            echo 'Sélectionner tous</button>';
                            echo '</div>';

                            echo '<div class="sae-group-students" ';
                            echo 'id="sae-students-' . $saeId . '" ';
                            echo 'style="display: none;">';

                            foreach ($students as $student) {
                                $studentId = htmlspecialchars((string) ((int) ($student['id'] ?? 0)));
                                $studentName = htmlspecialchars(
                                    (string) ($student['prenom'] ?? '')
                                    . ' '
                                    . (string) ($student['nom'] ?? '')
                                );

                                if ($studentId === '0' || trim($studentName) === '') {
                                    continue;
                                }

                                echo '<label class="student-checkbox-label">';
                                echo '<input type="checkbox" ';
                                echo 'name="student_id[]" ';
                                echo 'value="' . $studentId . '" ';
                                echo 'class="student-checkbox sae-' . $saeId . '-checkbox">';
                                echo '<span>' . $studentName . '</span>';
                                echo '</label>';
                            }

                            echo '</div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label for="messageSubject">Objet : *</label>
                <input
                        type="text"
                        id="messageSubject"
                        name="subject"
                        required
                        maxlength="200"
                >
            </div>

            <div class="form-group">
                <label for="messageContent">Message : *</label>
                <textarea id="messageContent" name="message" required></textarea>
            </div>

            <div class="form-actions">
                <button
                        type="button"
                        class="btn btn-secondary"
                        onclick="closeMessageModal()"
                >
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    Envoyer le message
                </button>
            </div>

        </form>
    </div>
</div>

<link rel="stylesheet" href="/_assets/css/message-modal.css">

<script>
    const messageTemplates = {
        reminder: {
            subject: "Rappel : Date limite de rendu",
            message: "Bonjour,\n\n"
                + "Je vous rappelle que la date limite de rendu de votre SAE approche.\n\n"
                + "Merci de vous assurer que vous êtes à jour dans votre travail "
                + "et n'hésitez pas à me contacter si vous avez des questions.\n\n"
                + "Bon courage !"
        },
        meeting: {
            subject: "Convocation : Réunion de suivi",
            message: "Bonjour,\n\n"
                + "Je souhaite organiser une réunion de suivi concernant votre SAE.\n\n"
                + "Merci de me proposer vos disponibilités pour la semaine prochaine.\n\n"
                + "Cordialement."
        },
        feedback: {
            subject: "Retour sur votre travail",
            message: "Bonjour,\n\n"
                + "J'ai examiné votre dernier rendu et je souhaite vous faire un retour.\n\n"
                + "[Ajoutez vos commentaires ici]\n\n"
                + "N'hésitez pas à me contacter si vous avez des questions.\n\n"
                + "Bon travail !"
        },
        congratulations: {
            subject: "Félicitations pour votre travail",
            message: "Bonjour,\n\n"
                + "Je tiens à vous féliciter pour la qualité de votre travail "
                + "sur votre SAE.\n\n"
                + "Continuez ainsi !\n\n"
                + "Cordialement."
        },
        urgent: {
            subject: "URGENT : Action requise",
            message: "Bonjour,\n\n"
                + "J'ai besoin de votre attention concernant un point urgent "
                + "sur votre SAE.\n\n"
                + "Merci de me contacter dès que possible.\n\n"
                + "Cordialement."
        }
    };

    function openMessageModal() {
        const modal = document.getElementById('messageModal');
        if (modal) {
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    function closeMessageModal() {
        const modal = document.getElementById('messageModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            const form = modal.querySelector('form');
            if (form) form.reset();
        }
    }

    function loadTemplate() {
        const templateSelect = document.getElementById('messageTemplate');
        const subjectInput = document.getElementById('messageSubject');
        const messageTextarea = document.getElementById('messageContent');

        const selectedTemplate = templateSelect.value;

        if (selectedTemplate && messageTemplates[selectedTemplate]) {
            subjectInput.value = messageTemplates[selectedTemplate].subject;
            messageTextarea.value = messageTemplates[selectedTemplate].message;
        }
    }

    function validateMessageForm() {
        const checkedBoxes = document.querySelectorAll(
            'input[name="student_id[]"]:checked'
        );
        const subject = document.getElementById('messageSubject').value.trim();
        const message = document.getElementById('messageContent').value.trim();

        if (checkedBoxes.length === 0) {
            alert('⚠️ Veuillez sélectionner au moins un étudiant.');
            return false;
        }

        if (!subject) {
            alert('⚠️ Veuillez saisir un objet pour le message.');
            return false;
        }

        if (!message || message.length < 10) {
            alert('⚠️ Le message est trop court (minimum 10 caractères).');
            return false;
        }

        if (checkedBoxes.length > 1) {
            const confirmMsg = `Vous allez envoyer ce message à `
                + `${checkedBoxes.length} étudiants. Continuer ?`;
            return confirm(confirmMsg);
        }

        return true;
    }

    function selectAllStudents() {
        document.querySelectorAll('input[name="student_id[]"]')
            .forEach(cb => cb.checked = true);
    }

    function deselectAllStudents() {
        document.querySelectorAll('input[name="student_id[]"]')
            .forEach(cb => cb.checked = false);
    }

    function toggleSaeGroup(saeId) {
        const studentsDiv = document.getElementById('sae-students-' + saeId);
        const icon = document.getElementById('toggle-icon-' + saeId);

        if (studentsDiv && icon) {
            if (studentsDiv.style.display === 'none'
                || studentsDiv.style.display === '') {
                studentsDiv.style.display = 'block';
                icon.textContent = '▲';
            } else {
                studentsDiv.style.display = 'none';
                icon.textContent = '▼';
            }
        }
    }

    function toggleSaeSelection(saeId) {
        const checkboxes = document.querySelectorAll(
            '.sae-' + saeId + '-checkbox'
        );

        if (checkboxes.length === 0) {
            return;
        }

        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        checkboxes.forEach(cb => cb.checked = !allChecked);
    }

    window.addEventListener('click', function (event) {
        const modal = document.getElementById('messageModal');
        if (event.target === modal) closeMessageModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('messageModal');
            if (modal && modal.style.display === 'block') closeMessageModal();
        }
    });
</script>
