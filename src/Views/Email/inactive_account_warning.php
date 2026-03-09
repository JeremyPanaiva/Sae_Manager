<h2>Avis de suppression de compte</h2>
<p>Bonjour <strong><?= htmlspecialchars((string) ($data['USER_NAME'] ?? 'Utilisateur')) ?></strong>,</p>

<p>Conformément aux recommandations de la CNIL et au Règlement Général sur la Protection des Données (RGPD), nous vous informons que votre compte SAE Manager est inactif depuis près de 3 ans.</p>

<p style="color: #d9534f; font-weight: bold;">
    Afin de protéger vos données personnelles, votre compte et toutes les données associées seront définitivement supprimés dans 30 jours.
</p>

<p>Si vous souhaitez conserver votre compte et votre historique, il vous suffit de vous y connecter avant ce délai en cliquant sur le bouton ci-dessous :</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?= htmlspecialchars((string) ($data['LOGIN_LINK'] ?? '#')) ?>" style="background-color: #0275d8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
        Me connecter et conserver mon compte
    </a>
</p>

<p>Si vous ne souhaitez plus utiliser nos services, aucune action de votre part n'est requise. La suppression sera automatique.</p>

<p>Cordialement,<br>L'équipe SAE Manager</p>
