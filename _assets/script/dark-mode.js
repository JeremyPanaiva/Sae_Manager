/**
 * Dark Mode Toggle
 *
 * Gère le basculement entre le mode clair et le mode sombre.
 * Sauvegarde la préférence dans le localStorage.
 * Respecte la préférence système si aucun choix n'a été fait.
 *
 * @package SaeManager
 * @author JeremyPanaiva
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'sae-manager-theme';
    const DARK_CLASS = 'dark-mode';

    /**
     * Applique le thème sans transition (pour le chargement initial)
     */
    function applyThemeInstant(isDark) {
        document.documentElement.classList.toggle(DARK_CLASS, isDark);
    }

    /**
     * Applique le thème avec transition douce
     */
    function applyThemeAnimated(isDark) {
        document.documentElement.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        document.documentElement.classList.toggle(DARK_CLASS, isDark);

        setTimeout(() => {
            document.documentElement.style.transition = '';
        }, 400);
    }

    /**
     * Met à jour l'icône et l'aria-label du bouton
     */
    function updateToggleButton(button, isDark) {
        const sunIcon = button.querySelector('.icon-sun');
        const moonIcon = button.querySelector('.icon-moon');

        if (sunIcon && moonIcon) {
            sunIcon.style.display = isDark ? 'none' : 'block';
            moonIcon.style.display = isDark ? 'block' : 'none';
        }

        button.setAttribute('aria-label', isDark ? 'Passer en mode clair' : 'Passer en mode sombre');
        button.setAttribute('title', isDark ? 'Mode clair' : 'Mode sombre');
    }

    /**
     * Détermine le thème initial
     */
    function getInitialTheme() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored !== null) {
            return stored === 'dark';
        }
        // Respecte la préférence système
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /**
     * Initialisation au chargement du DOM
     */
    document.addEventListener('DOMContentLoaded', function () {
        const isDark = getInitialTheme();
        applyThemeInstant(isDark);

        // Met à jour tous les boutons toggle (desktop + mobile)
        const toggleButtons = document.querySelectorAll('.dark-mode-toggle');
        toggleButtons.forEach(function (btn) {
            updateToggleButton(btn, isDark);

            btn.addEventListener('click', function () {
                const currentlyDark = document.documentElement.classList.contains(DARK_CLASS);
                const newIsDark = !currentlyDark;

                applyThemeAnimated(newIsDark);
                localStorage.setItem(STORAGE_KEY, newIsDark ? 'dark' : 'light');

                // Met à jour tous les boutons
                document.querySelectorAll('.dark-mode-toggle').forEach(function (b) {
                    updateToggleButton(b, newIsDark);
                });
            });
        });

        // Écoute les changements de préférence système
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                if (localStorage.getItem(STORAGE_KEY) === null) {
                    const isDark = e.matches;
                    applyThemeAnimated(isDark);
                    document.querySelectorAll('.dark-mode-toggle').forEach(function (b) {
                        updateToggleButton(b, isDark);
                    });
                }
            });
        }
    });

    // Appliquer immédiatement pour éviter le flash blanc
    const isDark = getInitialTheme();
    applyThemeInstant(isDark);
})();


