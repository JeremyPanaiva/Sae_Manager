document.addEventListener("DOMContentLoaded", function() {
    const toggles = document.querySelectorAll('.toggle-password');

    toggles.forEach(toggle => {
        const wrapper = toggle.closest('.password-wrapper');
        const passwordInput = wrapper.querySelector('input[type="password"]');

        toggle.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
        });
    });
});
