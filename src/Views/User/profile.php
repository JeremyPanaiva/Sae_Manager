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
            <input type="text" id="nom" name="nom" value="<?= $nom ?>" required readonly
                   class="form-control-locked">

            <label for="mail">Email :</label>
            <input type="email" id="mail" name="mail" value="<?= $mail ?>" required readonly
                   class="form-control-locked" data-original-email="<?= $mail ?>">

            <div class="profile-actions">
                <button type="button" id="btn-enable-edit" class="btn btn-outline">
                    Modifier les informations
                </button>
                <input type="submit" id="btn-save" value="Mettre à jour" class="btn btn-primary"
                       style="display: none;">
                <a href="/user/change-password" id="btn-change-password" class="btn btn-outline"
                   style="display: none;">Modifier le mot de passe</a>
            </div>
        </form>

        <!-- Zone de suppression du compte -->
        <div class="danger-zone">
            <h3>Supprimer votre compte SAE Manager</h3>
            <p>La suppression de votre compte est <strong>définitive et irréversible</strong>.
                Toutes vos données seront supprimées.</p>
            <div style="display: flex; justify-content: center;">
                <button type="button" class="btn btn-danger" style="min-width: 200px;"
                        onclick="openDeleteModal()">
                    Supprimer mon compte
                </button>
            </div>
        </div>

    </section>
</main>

<!-- Modale de confirmation de suppression (en dehors du main pour que le position:fixed fonctionne) -->
<div id="delete-modal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <h3>⚠️ Confirmer la suppression</h3>
        <p>Cette action est <strong>irréversible</strong>.<br>
            Entrez votre mot de passe pour confirmer.</p>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="modal-error" id="modal-error-msg">
                <?= htmlspecialchars(is_string($_SESSION['error_message']) ? $_SESSION['error_message'] : '') ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form action="/user/profile/delete" method="POST">
            <input type="password" name="delete_password" id="modal-password" required
                   placeholder="Votre mot de passe"
                   class="modal-input <?= isset($_SESSION['delete_error']) ? 'input-error' : '' ?>">

            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Annuler</button>
                <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-control-locked {
        background-color: #e9ecef !important;
        color: #495057;
        cursor: not-allowed;
        border-color: #ced4da;
    }

    .profile-actions {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .profile-actions .btn {
        min-width: 200px;
        text-align: center;
    }
</style>

<script>
    // ── Édition du profil ──────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const btnEdit         = document.getElementById('btn-enable-edit');
        const btnSave         = document.getElementById('btn-save');
        const btnPassword     = document.getElementById('btn-change-password');
        const lockedInputs    = document.querySelectorAll('.profile-form input[readonly]');

        btnEdit.addEventListener('click', function () {
            lockedInputs.forEach(input => {
                input.removeAttribute('readonly');
                input.classList.remove('form-control-locked');
            });
            btnEdit.style.display     = 'none';
            btnSave.style.display     = 'block';
            btnPassword.style.display = 'block';
            document.getElementById('prenom').focus();
        });
    });

    // ── Confirmation changement d'email ────────────────────────────────
    document.querySelector('.profile-form').addEventListener('submit', function (e) {
        const mailInput   = document.getElementById('mail');
        const originalMail = mailInput.dataset.originalEmail;

        if (mailInput.value !== originalMail) {
            const msg = "⚠️ ATTENTION : CHANGEMENT D'EMAIL\n\n" +
                "Vous êtes sur le point de modifier votre adresse email.\n" +
                "Si vous confirmez :\n\n" +
                "1. Vous serez immédiatement DÉCONNECTÉ.\n" +
                "2. Un email de vérification sera envoyé à la nouvelle adresse (" + mailInput.value + ").\n" +
                "3. Si vous avez fait une erreur de saisie, " +
                "vous risquez de PERDRE L'ACCÈS À VOTRE COMPTE.\n\n" +
                "Êtes-vous sûr que la nouvelle adresse est correcte ?";

            if (!confirm(msg)) {
                e.preventDefault();
            }
        }
    });

    // ── Modale suppression ─────────────────────────────────────────────
    function openDeleteModal() {
        const modal = document.getElementById('delete-modal');
        modal.style.display = '';
        modal.classList.add('active');
        setTimeout(() => document.getElementById('modal-password').focus(), 100);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('delete-modal');
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.getElementById('modal-password').value = '';
    }

    // Fermer en cliquant sur l'overlay
    document.getElementById('delete-modal').addEventListener('click', function (e) {
        if (e.target === this) closeDeleteModal();
    });

    // Rouvrir automatiquement si erreur de mot de passe
    <?php if (isset($_SESSION['delete_error'])) : ?>
    document.addEventListener('DOMContentLoaded', openDeleteModal);
        <?php unset($_SESSION['delete_error']); ?>
    <?php endif; ?>
</script>
