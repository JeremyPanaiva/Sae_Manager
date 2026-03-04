<link rel="stylesheet" href="/_assets/css/dashboard.css">
<link rel="stylesheet" href="/_assets/css/message-modal-styles.css">
<script src="/_assets/script/dash.js"></script>
<script src="/_assets/script/date-modal.js"></script>

<?php
/**
 * @var string $ROLE_KEY
 * @var string $USERNAME_KEY
 * @var string $CONTENT_KEY
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
                    $responsableId = 0;
                    if (
                        isset($_SESSION['user']) &&
                        is_array($_SESSION['user']) &&
                        isset($_SESSION['user']['id'])
                    ) {
                        $sessionId = $_SESSION['user']['id'];
                        $responsableId = is_numeric($sessionId) ? (int)$sessionId : 0;
                    }

                    if ($responsableId === 0) {
                        echo '<p style="color: red;">❌ Erreur: Session invalide</p>';
                    } else {
                        try {
                            $db = \Models\Database::getConnection();

                            $sqlSae = "SELECT DISTINCT s.id as sae_id, s.titre as nom_sae 
                                       FROM sae s
                                       INNER JOIN sae_attributions sa ON s.id = sa.sae_id
                                       WHERE sa.responsable_id = ?
                                       ORDER BY s.titre";

                            $stmtSae = $db->prepare($sqlSae);

                            if (!$stmtSae) {
                                $errorMsg = "Erreur préparation requête SAE: " . $db->error;
                                throw new Exception($errorMsg);
                            }

                            $stmtSae->bind_param("i", $responsableId);

                            if (!$stmtSae->execute()) {
                                $errorMsg = "Erreur exécution requête SAE: "
                                    . $stmtSae->error;
                                throw new Exception($errorMsg);
                            }

                            $resultSae = $stmtSae->get_result();

                            if ($resultSae === false) {
                                $errorMsg = "Erreur récupération résultat SAE: "
                                    . $stmtSae->error;
                                throw new Exception($errorMsg);
                            }

                            $saes = $resultSae->fetch_all(MYSQLI_ASSOC);
                            $stmtSae->close();

                            if (empty($saes)) {
                                $msg = 'Aucune SAE trouvée pour ce responsable.';
                                echo '<p style="color: #666; font-style: italic;">';
                                echo $msg . '</p>';
                            } else {
                                foreach ($saes as $sae) {
                                    $saeId = (int)$sae['sae_id'];
                                    $saeName = htmlspecialchars($sae['nom_sae']);

                                    $sqlStudents = "SELECT DISTINCT u.id, u.prenom, u.nom
                                                    FROM users u
                                                    INNER JOIN sae_attributions sa 
                                                    ON u.id = sa.student_id
                                                    WHERE sa.sae_id = ? 
                                                    AND u.role = 'Etudiant'
                                                    ORDER BY u.nom, u.prenom";

                                    $stmtStudents = $db->prepare($sqlStudents);

                                    if (!$stmtStudents) {
                                        $logMsg = "Erreur préparation requête étudiants "
                                            . "pour SAE {$saeId}: " . $db->error;
                                        error_log($logMsg);
                                        continue;
                                    }

                                    $stmtStudents->bind_param("i", $saeId);

                                    if (!$stmtStudents->execute()) {
                                        $logMsg = "Erreur exécution requête étudiants "
                                            . "pour SAE {$saeId}: " . $stmtStudents->error;
                                        error_log($logMsg);
                                        $stmtStudents->close();
                                        continue;
                                    }

                                    $resultStudents = $stmtStudents->get_result();

                                    if ($resultStudents === false) {
                                        $logMsg = "Erreur récupération étudiants "
                                            . "pour SAE {$saeId}: " . $stmtStudents->error;
                                        error_log($logMsg);
                                        $stmtStudents->close();
                                        continue;
                                    }

                                    $students = $resultStudents->fetch_all(MYSQLI_ASSOC);
                                    $stmtStudents->close();

                                    if (!empty($students)) {
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
                                            $studentId = htmlspecialchars(
                                                (string)$student['id']
                                            );
                                            $studentName = htmlspecialchars(
                                                $student['prenom'] . ' ' . $student['nom']
                                            );

                                            echo '<label class="student-checkbox-label">';
                                            echo '<input type="checkbox" ';
                                            echo 'name="student_id[]" ';
                                            echo 'value="' . $studentId . '" ';
                                            echo 'class="student-checkbox ';
                                            echo 'sae-' . $saeId . '-checkbox">';
                                            echo '<span>' . $studentName . '</span>';
                                            echo '</label>';
                                        }

                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                            }
                        } catch (\Shared\Exceptions\DataBaseException $e) {
                            error_log('DatabaseException in message modal: ' . $e->getMessage());
                            echo '<p style="color: red;">';
                            echo '❌ Erreur de connexion à la base de données.</p>';
                        } catch (\Throwable $e) {
                            error_log('Error loading students by SAE: ' . $e->getMessage());
                            echo '<p style="color: red;">❌ Erreur: ';
                            echo htmlspecialchars($e->getMessage()) . '</p>';
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

<?php
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'message_sent') {
        echo '<div class="message-success">';
        echo '✅ Le message a été envoyé avec succès !</div>';
    } elseif ($_GET['success'] === 'messages_sent') {
        $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
        echo '<div class="message-success">';
        echo '✅ Le message a été envoyé à ' . $count;
        echo ' étudiant(s) avec succès !</div>';
    }
}

if (isset($_GET['error'])) {
    echo '<div class="message-error">';
    echo '❌ Une erreur est survenue lors de l\'envoi du message.</div>';
}
?>