<link rel="stylesheet" href="/_assets/css/inscription.css">
<script src="/_assets/script/showPassword.js"></script>

<section class="main" aria-label="Contenu principal">
  <form action="/user/register" method="post">
    <fieldset>
      <?= $ERRORS_KEY ?? '' ?>
      <legend>Inscription</legend>

      <label for="nom">Nom :</label>
      <input type="text" id="nom" name="nom" required placeholder="Votre nom">

      <label for="prenom">Prénom :</label>
      <input type="text" id="prenom" name="prenom" required placeholder="Votre prénom">

      <label for="mail">Email :</label>
      <input type="email" id="mail" name="mail" required placeholder="exemple@etu.univ-amu.fr">

      <label for="mdp">Mot de passe :</label>
      <div class="password-wrapper">
        <input type="password" id="mdp" name="mdp" required placeholder="Votre mot de passe">
        <span class="toggle-password" aria-label="Afficher/masquer le mot de passe">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#000"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
        </span>
      </div>

      <label for="role">Rôle :</label>
      <select id="role" name="role" required>
        <option value="">-- Sélectionnez votre rôle --</option>
        <option value="etudiant">Étudiant</option>
        <option value="responsable">Responsable</option>
        <option value="client">Client</option>
      </select>

      <input type="submit" value="S'inscrire" name="ok">
    </fieldset>

    <!-- Redirection -->
    <p class="form-footer">
      Déjà membre ? <a href="/user/login">Se connecter</a>
    </p>
  </form>
</section>