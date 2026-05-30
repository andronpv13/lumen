/**
 * assets/js/profile.js
 * Отвечает ТОЛЬКО за переключение видимости паролей
 * Вся валидация — в main.js (initProfilePasswordValidation)
 */
document.addEventListener('DOMContentLoaded', function () {
    const toggleButtons = document.querySelectorAll('.password-toggle-btn');
    
    toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const iconOpen = this.querySelector('.icon-eye-open');
            const iconClosed = this.querySelector('.icon-eye-closed');
            
            if (!input) return;
            
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            
            if (iconOpen && iconClosed) {
                iconOpen.classList.toggle('hidden', !isPassword);
                iconClosed.classList.toggle('hidden', isPassword);
            }
        });
    });
});