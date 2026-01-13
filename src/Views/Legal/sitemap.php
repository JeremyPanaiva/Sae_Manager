<?php

/**
 * View:  Site map
 *
 * Displays the complete navigation structure and main pages of SAE Manager.
 *
 * @package SaeManager\Views\Legal
 * @author JeremyPanaiva & mohamedDriouchi
 */

?>
<link rel="stylesheet" href="/_assets/css/plan.css">

<main class="plan-main">
    <header class="plan-header">
        <h1 class="plan-title">Plan du site</h1>
    </header>

    <section class="plan-content">

        <article class="plan-section">
            <h2 class="plan-subtitle">1. Pages principales</h2>
            <ul class="plan-list">
                <li><a href="/">Accueil</a></li>
                <li><a href="/user/login">Connexion</a></li>
                <li><a href="/user/register">Inscription</a></li>
                <li><a href="/contact">Contact</a></li>
                <li><a href="/mentions-legales">Mentions légales</a></li>
                <li><a href="/plan-du-site">Plan du site</a></li>
            </ul>
        </article>

        <article class="plan-section">
            <h2 class="plan-subtitle">2. Pages utilisateurs</h2>
            <ul class="plan-list">
                <li><a href="/user/profile">Profil utilisateur</a></li>
                <li><a href="/user/forgot-password">Mot de passe oublié</a></li>
                <li><a href="/user/change-password">Changer mot de passe</a></li>
                <li><a href="/user/list">Liste des utilisateurs</a></li>
            </ul>
        </article>

        <article class="plan-section">
            <h2 class="plan-subtitle">3. Pages SAE et Dashboard</h2>
            <ul class="plan-list">
                <li><a href="/sae">Gestion des SAE</a></li>
                <li><a href="/dashboard">Tableau de bord</a></li>
            </ul>
        </article>
    </section>
</main>