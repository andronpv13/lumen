/**
 * assets/js/main.js
 * Основной JavaScript файл приложения Lumen (SPA)
 * Содержит инициализацию интерфейса, валидацию форм и AJAX-взаимодействие.
 */
(function() {
    'use strict';

    // =========================================================
    // Модуль валидации смены пароля в личном кабинете
    // =========================================================
    function initProfilePasswordValidation() {
        const currentPasswordInput = document.getElementById('profile-current-password');
        const passwordInput = document.getElementById('profile-password');
        const confirmInput = document.getElementById('profile-confirm-password');
        const submitButton = document.getElementById('profile-submit');

        // Если хотя бы одного элемента нет на странице, выходим
        if (!currentPasswordInput || !passwordInput || !submitButton) return;

        const hints = {
            current: document.getElementById('hint-current-password'),
            password: document.getElementById('hint-password'),
            confirm: document.getElementById('hint-confirm-password')
        };

        const state = {
            currentChecked: false,
            currentValid: false,
            passwordValid: false,
            confirmValid: false
        };

        // Установка состояния поля (подсветка + подсказка)
        function setFieldState(input, isValid, message = '') {
            if (!input) return;
            const wrap = input.closest('.field-input-wrap');
            const label = input.closest('.field-label');

            if (wrap) {
                wrap.classList.toggle('input-valid', isValid);
                wrap.classList.toggle('input-invalid', !isValid && input.value.trim() !== '');
            }
            if (label) {
                label.classList.toggle('input-valid', isValid);
                label.classList.toggle('input-invalid', !isValid && input.value.trim() !== '');
            }
            input.classList.toggle('input-valid', isValid);
            input.classList.toggle('input-invalid', !isValid && input.value.trim() !== '');

            let hintEl;
            if (input === currentPasswordInput) hintEl = hints.current;
            else if (input === passwordInput) hintEl = hints.password;
            else if (input === confirmInput) hintEl = hints.confirm;

            if (hintEl) {
                hintEl.textContent = message;
                hintEl.style.color = isValid ? 'var(--success, #12e40b)' : 'var(--error, #ee3f0a)';
            }
        }

        // Утилиты валидации
        const hasSpaces = val => /\s/.test(val);
        const isLongEnough = val => val.length >= 6;
        const differsFromOld = (newVal, oldVal) => newVal !== oldVal;

        // AJAX проверка текущего пароля
        let checkTimeout = null;
        function checkCurrentPassword() {
            const val = currentPasswordInput.value;
            if (!val || hasSpaces(val)) {
                if (hasSpaces(val)) {
                    setFieldState(currentPasswordInput, false, 'Пробелы запрещены');
                    passwordInput.disabled = true;
                    if (confirmInput) confirmInput.disabled = true;
                } else {
                    setFieldState(currentPasswordInput, false, '');
                    passwordInput.disabled = true;
                    if (confirmInput) confirmInput.disabled = true;
                }
                state.currentChecked = false;
                state.currentValid = false;
                return;
            }

            // Убираем подсветку во время запроса
            const wrap = currentPasswordInput.closest('.field-input-wrap');
            const label = currentPasswordInput.closest('.field-label');
            if (wrap) wrap.classList.remove('input-valid', 'input-invalid');
            if (label) label.classList.remove('input-valid', 'input-invalid');
            currentPasswordInput.classList.remove('input-valid', 'input-invalid');

            const formData = new URLSearchParams();
            formData.append('check_password', val);

            fetch('/?route=users&action=check_password&ajax=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.valid) {
                    setFieldState(currentPasswordInput, true, data.message || 'Пароль подтверждён');
                    state.currentChecked = true;
                    state.currentValid = true;
                    passwordInput.disabled = false;
                    if (confirmInput) confirmInput.disabled = false;
                    passwordInput.focus();
                } else {
                    setFieldState(currentPasswordInput, false, data.message || 'Неверный текущий пароль');
                    state.currentChecked = true;
                    state.currentValid = false;
                    passwordInput.disabled = true;
                    if (confirmInput) confirmInput.disabled = true;
                    // Сброс подсветки зависимых полей
                    if (passwordInput) {
                        const pw = passwordInput.closest('.field-input-wrap');
                        if (pw) pw.classList.remove('input-valid', 'input-invalid');
                    }
                    if (confirmInput) {
                        const cw = confirmInput.closest('.field-input-wrap');
                        if (cw) cw.classList.remove('input-valid', 'input-invalid');
                    }
                }
            })
            .catch(() => {
                setFieldState(currentPasswordInput, false, 'Ошибка соединения');
                state.currentChecked = true;
                state.currentValid = false;
                passwordInput.disabled = true;
                if (confirmInput) confirmInput.disabled = true;
            });
        }

        // Debounce для ввода
        const debounce = (fn, delay = 300) => {
            let timer;
            return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
        };

        // Обработчики ввода
        currentPasswordInput.addEventListener('input', debounce(() => {
            if (hasSpaces(currentPasswordInput.value)) {
                setFieldState(currentPasswordInput, false, 'Пробелы запрещены');
                passwordInput.disabled = true;
                if (confirmInput) confirmInput.disabled = true;
                state.currentValid = false;
                return;
            }
            if (currentPasswordInput.value.trim() === '') {
                setFieldState(currentPasswordInput, false, '');
                passwordInput.disabled = true;
                if (confirmInput) confirmInput.disabled = true;
                state.currentValid = false;
                return;
            }
            checkCurrentPassword();
        }));

        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                const val = passwordInput.value;
                if (!val) {
                    setFieldState(passwordInput, false, '');
                    state.passwordValid = false;
                    return;
                }
                if (hasSpaces(val)) {
                    setFieldState(passwordInput, false, 'Без пробелов');
                    state.passwordValid = false;
                    return;
                }
                if (!isLongEnough(val)) {
                    setFieldState(passwordInput, false, 'Минимум 6 символов');
                    state.passwordValid = false;
                    return;
                }
                if (state.currentValid && currentPasswordInput.value.trim() && !differsFromOld(val, currentPasswordInput.value.trim())) {
                    setFieldState(passwordInput, false, 'Должен отличаться от текущего');
                    state.passwordValid = false;
                    return;
                }
                setFieldState(passwordInput, true, '✓');
                state.passwordValid = true;
                if (confirmInput && confirmInput.value) validateConfirm();
            });
        }

        function validateConfirm() {
            if (!confirmInput) return;
            const val = confirmInput.value;
            if (!val) {
                setFieldState(confirmInput, false, '');
                state.confirmValid = false;
                return;
            }
            if (val !== passwordInput.value) {
                setFieldState(confirmInput, false, 'Пароли не совпадают');
                state.confirmValid = false;
            } else {
                setFieldState(confirmInput, true, '✓');
                state.confirmValid = true;
            }
        }

        if (confirmInput) {
            confirmInput.addEventListener('input', validateConfirm);
        }

        // Изначальная блокировка новых полей
        passwordInput.disabled = true;
        if (confirmInput) confirmInput.disabled = true;
        submitButton.disabled = false; // Кнопка ВСЕГДА активна по ТЗ

        // Предотвращение отправки при ошибках
        const form = passwordInput.closest('form') || document.getElementById('password-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                let hasError = false;
                // Проверяем только если пользователь ввёл новый пароль
                if (passwordInput.value.trim()) {
                    if (!state.currentValid || !state.currentChecked) hasError = true;
                    if (!state.passwordValid) hasError = true;
                    if (!state.confirmValid) hasError = true;
                }
                if (hasError) e.preventDefault();
            });
        }
    }

    // =========================================================
    // Инициализация при загрузке DOM
    // =========================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initProfilePasswordValidation();
        });
    } else {
        initProfilePasswordValidation();
    }

})();
/**
 * Отвечает ТОЛЬКО за переключение видимости паролей
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