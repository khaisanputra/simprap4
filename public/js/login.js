document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('password-toggle');

    if (passwordInput && toggleIcon) {
        toggleIcon.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    const confirmInput = document.getElementById('password_confirmation');
    const confirmToggle = document.getElementById('password-confirm-toggle');

    if (confirmInput && confirmToggle) {
        confirmToggle.addEventListener('click', function () {
            const isPassword = confirmInput.type === 'password';
            confirmInput.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
});
