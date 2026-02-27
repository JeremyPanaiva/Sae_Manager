<?php

/**
 * User Profile Template
 *
 * Displays and allows editing of the user's profile information.
 *
 * Template variables:
 * @var string $ERRORS_KEY HTML error messages to display (optional)
 * @var string $SUCCESS_KEY HTML success message to display (optional)
 * @var string $date_creation Account creation date
 * @var string $prenom User's first name
 * @var string $nom User's last name
 * @var string $mail User's email address
 *
 * @package SaeManager\Views\User
 * @author JeremyPanaiva & mohamedDriouchi
 */

?>

<link rel="stylesheet" href="/_assets/css/user.css">

<main class="dashboard-page">
    <section class="dashboard-section">
        <h2>Mon profil</h2>

        <?= $ERRORS_KEY ?>
        <?= $SUCCESS_KEY ?>

        <p><strong>Date de création du compte : </strong> <?= $date_creation ?></p>

        <form action="/user/profile" method="POST" class="profile-form">
            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" value="<?= $prenom ?>" required readonly
                class="form-control-locked">

            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" value="<?= $nom ?>" required readonly class="form-control-locked">

            <label for="mail">Email :</label>
            <input type="email" id="mail" name="mail" value="<?= $mail ?>" required readonly class="form-control-locked"
                data-original-email="<?= $mail ?>">

            <div class="profile-actions"
                style="margin-top: 20px; display: flex; flex-direction: column; align-items: center; gap: 15px;">

                <!-- Bouton pour activer l'édition -->
                <button type="button" id="btn-enable-edit" class="btn btn-outline" style="min-width: 200px;">
                    Modifier les informations
                </button>

                <!-- Bouton de soumission (caché par défaut) -->
                <input type="submit" id="btn-save" value="Mettre à jour" class="btn btn-primary"
                    style="min-width: 200px; display: none;">

                <a href="/user/change-password" id="btn-change-password" class="btn btn-outline"
                    style="min-width: 200px; text-align: center; display: none;">Modifier le mot de passe</a>
            </div>
        </form>

        <style>
            /* Style pour les champs verrouillés (gris + curseur interdit) */
            .form-control-locked {
                background-color: #e9ecef !important;
                color: #495057;
                cursor: not-allowed;
                border-color: #ced4da;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const btnData = document.getElementById('btn-enable-edit');
                const btnSave = document.getElementById('btn-save');
                const btnChangePassword = document.getElementById('btn-change-password');
                const inputs = document.querySelectorAll('.profile-form input[readonly]');

                btnData.addEventListener('click', function () {
                    // Unlock fields
                    inputs.forEach(input => {
                        input.removeAttribute('readonly');
                        input.classList.remove('form-control-locked');
                    });

                    // Toggle buttons
                    btnData.style.display = 'none';
                    btnSave.style.display = 'block';
                    btnChangePassword.style.display = 'block'; // Reveal password button

                    // Focus on first field
                    document.getElementById('prenom').focus();
                });
            });

            document.querySelector('.profile-form').addEventListener('submit', function (e) {
                const mailInput = document.getElementById('mail');
                const originalMail = mailInput.dataset.originalEmail;

                if (mailInput.value !== originalMail) {
                    const confirmMessage = "⚠️ ATTENTION : CHANGEMENT D'EMAIL\n\n" +
                        "Vous êtes sur le point de modifier votre adresse email.\n" +
                        "Si vous confirmez :\n\n" +
                        "1. Vous serez immédiatement DÉCONNECTÉ.\n" +
                        "2. Un email de vérification sera envoyé à la nouvelle adresse (" + mailInput.value + ").\n" +
                        "3. Si vous avez fait une erreur de saisie, " +
                        "vous risquez de PERDRE L'ACCÈS À VOTRE COMPTE.\n\n" +
                        "Êtes-vous sûr que la nouvelle adresse est correcte ?";

                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                    }
                }
            });
        </script>

        <!-- Zone de suppression du compte -->
        <div class="danger-zone">
            <h3>Supprimer votre compte SAE Manager</h3>
            <p>La suppression de votre compte est <strong>définitive et irréversible</strong>.
                Toutes vos données seront supprimées.</p>

            <div style="display: flex; justify-content: center; margin-top: 15px;">
                <button type="button" class="btn btn-danger" style="min-width: 200px;" onclick="openDeleteModal()">
                    Supprimer mon compte
                </button>
            </div>
        </div>
    </section>

    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3 style="color: var(--danger); margin-top: 0; display: flex; align-items: center; gap: 8px;">
                ⚠️ Confirmation de suppression
            </h3>

            <p style="margin-bottom: 20px;">
                Êtes-vous absolument certain de vouloir supprimer votre compte définitivement ?<br>
                <strong>Toutes vos SAE, to-do lists, avis et données personnelles seront
                    perdues.</strong>
            </p>

            <form action="/user/profile/delete" method="POST" id="deleteForm">
                <div style="margin-bottom: 20px;">
                    <label for="delete_password" style="font-weight: bold; margin-bottom: 8px; display: block;">
                        Mot de passe actuel :
                    </label>
                    <input type="password" id="delete_password" name="delete_password" required
                        placeholder="Saisissez votre mot de passe pour confirmer" class="form-control" style="width: 100%; box-sizing: border-box; padding: 10px; border-radius: 6px; 
                                        border: 1px solid #ccc;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Annuler</button>
                    <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function openDeleteModal() {
        document.getElementById('deleteModal').style.display = 'flex';
        // Clear previous input if any
        document.getElementById('delete_password').value = '';
        setTimeout(() => document.getElementById('delete_password').focus(), 100);
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modal if user clicks outside of it
    window.onclick = function (event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    }
</script>