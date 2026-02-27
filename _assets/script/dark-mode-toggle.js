(function () {
    // Key used in localStorage
    var STORAGE_KEY = 'sae_manager_theme';
    var BUTTON_ID = 'darkModeToggle';
    var DARK_ATTR = 'data-theme';
    var DARK_VALUE = 'dark';

    var root = document.documentElement;
    var btn = document.getElementById(BUTTON_ID);
    if (!btn) return;

    function applyDark(shouldDark) {
        if (shouldDark) {
            root.setAttribute(DARK_ATTR, DARK_VALUE);
            btn.setAttribute('aria-pressed', 'true');
        } else {
            root.removeAttribute(DARK_ATTR);
            btn.setAttribute('aria-pressed', 'false');
        }
    }

    // Read stored preference
    function readStored() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    // Save preference
    function saveStored(value) {
        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch (e) {
            // ignore
        }
    }

    // Initial preference: localStorage -> system preference -> light
    var stored = readStored();
    if (stored === 'dark') {
        applyDark(true);
    } else if (stored === 'light') {
        applyDark(false);
    } else {
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyDark(prefersDark);
    }

    // Toggle on click
    btn.addEventListener('click', function () {
        var isDark = root.getAttribute(DARK_ATTR) === DARK_VALUE;
        var next = !isDark;
        applyDark(next);
        saveStored(next ? 'dark' : 'light');
    });

    // React to system changes if user hasn't set a manual preference
    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var mqHandler = function (e) {
            var storedNow = readStored();
            if (storedNow === null) {
                // e.matches when using addEventListener, but for addListener it's the mq object
                var matches = (typeof e.matches === 'boolean') ? e.matches : mq.matches;
                applyDark(matches);
            }
        };
        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', mqHandler);
        } else if (typeof mq.addListener === 'function') {
            mq.addListener(mqHandler);
        }
    }
})();
