/**
 * main.js - Главный JavaScript файл проекта
 * Включает функционал: burger-меню, flash-сообщения, модальные окна, работа с корзиной
 */

document.addEventListener('DOMContentLoaded', function() {
  // Burger-меню
  const burger = document.querySelector('.burger');
  const mainNav = document.querySelector('.main-nav');

  if (burger && mainNav) {
    burger.addEventListener('click', function() {
      mainNav.classList.toggle('open');
    });
  }

  // Flash-сообщения (авто-скрытие)
  const flashMessages = document.querySelectorAll('.flash');
  flashMessages.forEach(function(flash) {
    setTimeout(function() {
      flash.style.transition = 'opacity 0.5s ease';
      flash.style.opacity = '0';
      setTimeout(function() {
        const parent = flash.parentElement;
        if (parent) {
          parent.remove();
        }
      }, 500);
    }, 3000);
  });

  // Подтверждение удаления
  const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
  deleteForms.forEach(function(form) {
    form.addEventListener('submit', function(e) {
      if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
        e.preventDefault();
      }
    });
  });

  // Увеличение/уменьшение количества в корзине
  const qtyInputs = document.querySelectorAll('.qty-input');
  qtyInputs.forEach(function(input) {
    const minusBtn = input.parentElement.querySelector('.qty-minus');
    const plusBtn = input.parentElement.querySelector('.qty-plus');

    if (minusBtn) {
      minusBtn.addEventListener('click', function() {
        const val = parseInt(input.value) || 1;
        if (val > 1) {
          input.value = val - 1;
          input.dispatchEvent(new Event('change'));
        }
      });
    }

    if (plusBtn) {
      plusBtn.addEventListener('click', function() {
        const val = parseInt(input.value) || 0;
        input.value = val + 1;
        input.dispatchEvent(new Event('change'));
      });
    }
  });

  // Предварительный просмотр изображения при загрузке
  const imageInputs = document.querySelectorAll('input[type="file"][data-preview]');
  imageInputs.forEach(function(input) {
    input.addEventListener('change', function() {
      const previewId = input.getAttribute('data-preview');
      const preview = document.getElementById(previewId);

      if (preview && input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
      }
    });
  });

  // Авто-закрытие alert-сообщений
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(function(alert) {
    const closeBtn = alert.querySelector('.alert-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function() {
        alert.style.transition = 'opacity 0.3s ease';
        alert.style.opacity = '0';
        setTimeout(function() {
          alert.remove();
        }, 300);
      });
    }
  });
});

/**
 * Форматирование цены
 * @param {number} price Цена
 * @returns {string} Отформатированная цена
 */
function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
}

/**
 * Показать/скрыть пароль в поле ввода
 * @param {string} inputId ID поля ввода пароля
 */
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  if (input) {
    input.type = input.type === 'password' ? 'text' : 'password';
  }
}

/**
 * checkout.php - Выбор платёжной карты
 */
document.addEventListener('DOMContentLoaded', function() {
  var cards = document.querySelectorAll('.payment-card');
  cards.forEach(function(card) {
    var input = card.querySelector('input[type="radio"]');
    if (!input) return;

    input.addEventListener('change', function() {
      if (input.checked) {
        cards.forEach(function(c) { c.classList.remove('active'); });
        card.classList.add('active');
      }
    });

    card.addEventListener('click', function() {
      input.checked = true;
      cards.forEach(function(c) { c.classList.remove('active'); });
      card.classList.add('active');
    });
  });
});

/**
 * users/reviews.php - Работа с отзывами
 */
function toggleReviewForm(productId) {
    const form = document.getElementById('review-form-' + productId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

function setRating(productId, rating) {
    // Устанавливаем значение в скрытый input
    const ratingInput = document.getElementById('rating_' + productId);
    if (ratingInput) {
        ratingInput.value = rating;
    }

    // Обновляем визуальное отображение звёзд
    const container = document.getElementById('star-rating-' + productId);
    if (!container) return;

    const stars = container.querySelectorAll('span');

    stars.forEach(function(star) {
        const value = parseInt(star.getAttribute('data-value'));
        if (value <= rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Инициализация: устанавливаем 5 звёзд по умолчанию при открытии формы
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.review-form');
    forms.forEach(function(form) {
        const productId = form.id.replace('review-form-', '');
        setRating(productId, 5);
    });
});

/**
 * auth.php - Валидация формы регистрации
 */
(function(){
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function initRegistrationValidation() {
    const nameInput = document.getElementById('register-name');
    const emailInput = document.getElementById('register-email');
    const passwordInput = document.getElementById('register-password');
    const confirmInput = document.getElementById('register-confirm-password');
    const submitButton = document.getElementById('register-submit');

    if (!nameInput || !emailInput || !passwordInput || !confirmInput || !submitButton) {
      return;
    }

    const hints = {
      name: document.getElementById('hint-name'),
      email: document.getElementById('hint-email'),
      password: document.getElementById('hint-password'),
      confirm: document.getElementById('hint-confirm-password')
    };

    // Состояние валидации для каждого поля
    const validationState = {
      name: { valid: false, checked: false },
      email: { valid: false, checked: false },
      password: { valid: false, checked: false },
      confirm: { valid: false, checked: false }
    };

    function setState(input, valid, message, forceInvalid) {
      forceInvalid = forceInvalid || false;
      const wrap = input.closest('.field-input-wrap');
      const invalid = !valid && forceInvalid;
      if (wrap) {
        wrap.classList.toggle('input-valid', valid);
        wrap.classList.toggle('input-invalid', invalid);
      }
      input.classList.toggle('input-valid', valid);
      input.classList.toggle('input-invalid', invalid);
      const field = input.dataset.field;
      const hint = hints[field];
      if (hint) {
        hint.textContent = message || '';
      }
      // Обновляем состояние валидации
      if (field && validationState[field]) {
        validationState[field].valid = valid;
        validationState[field].checked = true;
      }
    }

    function validateNoSpaces(value) {
      return !/[\s\t]/.test(value);
    }

    function updateSubmitState() {
      // Проверяем, что все поля прошли валидацию и проверку на занятость
      const allValid = Object.values(validationState).every(state => state.valid && state.checked);
      submitButton.disabled = !allValid;
    }

    function checkField(input, showEmptyAsValid) {
      showEmptyAsValid = showEmptyAsValid || false;
      if (!input) return { valid: false };
      const field = input.dataset.field;
      const rawValue = input.value;
      const value = rawValue.trim();
      let valid = true;
      let message = '';
      let forceInvalid = false;

      if (!validateNoSpaces(rawValue)) {
        valid = false;
        message = 'Пробелы и табы запрещены';
        forceInvalid = true;
      } else if (!value) {
        valid = false;
        message = showEmptyAsValid ? '' : 'Поле не может быть пустым';
      } else if (field === 'name') {
        if (value.length < 4) {
          valid = false;
          message = 'Минимум 4 символа';
          forceInvalid = true;
        }
      } else if (field === 'email') {
        if (/\s/.test(rawValue)) {
          valid = false;
          message = 'Пробелы и табы запрещены';
          forceInvalid = true;
        } else if (!emailPattern.test(value)) {
          valid = false;
          message = 'Неверный формат email';
          forceInvalid = true;
        }
      } else if (field === 'password') {
        if (value.length < 6) {
          valid = false;
          message = 'Минимум 6 символов';
          forceInvalid = true;
        }
      } else if (field === 'confirm') {
        if (value !== passwordInput.value) {
          valid = false;
          message = 'Пароли не совпадают';
          forceInvalid = true;
        }
      }

      setState(input, valid, message, forceInvalid);
      return { valid, message };
    }

    function checkAvailability(input, type) {
      const value = input.value.trim();
      if (!value || !validateNoSpaces(input.value)) return;
      if (type === 'email' && !emailPattern.test(value)) return;

      fetch('?check=' + type + '&value=' + encodeURIComponent(value), { credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
          if (!data.ok) {
            // Если сервер вернул ошибку формата или наличия пробелов
            const field = input.dataset.field;
            if (validationState[field]) {
              validationState[field].checked = true;
            }
            updateSubmitState();
            return;
          }
          if (data.exists) {
            // Поле занято - подсвечиваем красным, показываем сообщение
            setState(input, false, type === 'email' ? 'Email уже занят' : 'Имя занято', true);
          } else {
            // Поле свободно - проверяем общую валидность
            const result = checkField(input);
            if (result.valid) {
              setState(input, true, type === 'email' ? 'Email свободен' : 'Имя доступно');
            }
          }
          updateSubmitState();
        })
        .catch(() => {
          // Ошибка запроса - помечаем как проверенное, но невалидное
          const field = input.dataset.field;
          if (validationState[field]) {
            validationState[field].checked = true;
          }
          updateSubmitState();
        });
    }

    emailInput.dataset.field = 'email';
    nameInput.dataset.field = 'name';
    passwordInput.dataset.field = 'password';
    confirmInput.dataset.field = 'confirm';

    // Обработчик кнопок "глаз" для показа/скрытия пароля
    document.querySelectorAll('.field-toggle').forEach(btn => {
      btn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        // Переключаем видимость иконок
        const openIcon = this.querySelector('.icon-eye-open');
        const closedIcon = this.querySelector('.icon-eye-closed');

        if (isPassword) {
          // Показываем пароль - скрываем открытый глаз, показываем зачёркнутый
          if (openIcon) openIcon.style.display = 'none';
          if (closedIcon) closedIcon.style.display = 'block';
          this.setAttribute('aria-label', 'Скрыть пароль');
          this.setAttribute('title', 'Скрыть пароль');
        } else {
          // Скрываем пароль - показываем открытый глаз, скрываем зачёркнутый
          if (openIcon) openIcon.style.display = 'block';
          if (closedIcon) closedIcon.style.display = 'none';
          this.setAttribute('aria-label', 'Показать пароль');
          this.setAttribute('title', 'Показать пароль');
        }
      });
    });

    const debounced = (fn, delay) => {
      delay = delay || 300;
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), delay);
      };
    };

    nameInput.addEventListener('input', debounced(() => {
      const result = checkField(nameInput, true);
      if (result.valid) {
        checkAvailability(nameInput, 'name');
      } else {
        // Если формат невалиден, всё равно помечаем как проверенное
        validationState.name.checked = true;
        updateSubmitState();
      }
    }));

    emailInput.addEventListener('input', debounced(() => {
      const result = checkField(emailInput, true);
      if (result.valid) {
        checkAvailability(emailInput, 'email');
      } else {
        // Если формат невалиден, всё равно помечаем как проверенное
        validationState.email.checked = true;
        updateSubmitState();
      }
    }));

    passwordInput.addEventListener('input', () => {
      checkField(passwordInput, true);
      if (confirmInput.value) checkField(confirmInput, true);
      updateSubmitState();
    });

    confirmInput.addEventListener('input', () => {
      checkField(confirmInput, true);
      updateSubmitState();
    });

    // Изначально кнопка заблокирована
    submitButton.disabled = true;

    submitButton.addEventListener('click', function(event) {
      const fields = [nameInput, emailInput, passwordInput, confirmInput];
      let allValid = true;
      fields.forEach(input => {
        const result = checkField(input, false);
        if (!result.valid) allValid = false;
      });
      if (!allValid) {
        event.preventDefault();
      }
    });
  }

  // Инициализация после загрузки DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRegistrationValidation);
  } else {
    initRegistrationValidation();
  }
})();
/**
 * profile.php - Валидация формы смены пароля в профиле пользователя
 */
(function(){
  function initProfilePasswordValidation() {
    const currentPasswordInput = document.getElementById('profile-current-password');
    const passwordInput = document.getElementById('profile-password');
    const submitButton = document.getElementById('profile-submit');

    if (!currentPasswordInput || !passwordInput || !submitButton) {
      return;
    }

    const hints = {
      current: document.getElementById('hint-current-password'),
      password: document.getElementById('hint-password')
    };

    // Состояние валидации
    const validationState = {
      current: { valid: true, checked: false },
      password: { valid: true, checked: false }
    };

    function setState(input, valid, message, forceInvalid) {
      forceInvalid = forceInvalid || false;
      const wrap = input.closest('.field-input-wrap');
      const label = input.closest('.field-label');
      const invalid = !valid && forceInvalid;

      // Если поле пустое - убираем всю подсветку
      if (!input.value.trim()) {
        if (wrap) {
          wrap.classList.remove('input-valid', 'input-invalid');
        }
        if (label) {
          label.classList.remove('input-valid', 'input-invalid');
        }
        input.classList.remove('input-valid', 'input-invalid');
        const field = input.dataset.field;
        let hint;
        if (field === 'current_password') {
          hint = hints.current;
        } else if (field === 'password') {
          hint = hints.password;
        }
        if (hint) {
          hint.textContent = '';
        }
        return;
      }

      if (wrap) {
        wrap.classList.toggle('input-valid', valid);
        wrap.classList.toggle('input-invalid', invalid);
      }
      if (label) {
        label.classList.toggle('input-valid', valid);
        label.classList.toggle('input-invalid', invalid);
      }
      input.classList.toggle('input-valid', valid);
      input.classList.toggle('input-invalid', invalid);
      const field = input.dataset.field;
      let hint;
      if (field === 'current_password') {
        hint = hints.current;
      } else if (field === 'password') {
        hint = hints.password;
      }
      if (hint) {
        hint.textContent = message || '';
      }
      // Обновляем состояние валидации
      if (field && validationState[field]) {
        validationState[field].valid = valid;
        validationState[field].checked = true;
      }
    }

    function validateNoSpaces(value) {
      return !/[\s\t]/.test(value);
    }

    function updateSubmitState() {
      // Кнопка активна только если:
      // 1. Пароль не заполнен (оставляем пустым = не меняем)
      // 2. ИЛИ пароль заполнен и текущий пароль подтверждён
      const passwordValue = passwordInput.value.trim();

      if (!passwordValue) {
        // Пароль не меняется - кнопка всегда активна (если нет ошибок в других полях)
        submitButton.disabled = false;
        return;
      }

      // Пароль меняется - проверяем валидность
      const passwordValid = validationState.password.valid && validationState.password.checked;
      const currentValid = validationState.current.valid && validationState.current.checked;

      submitButton.disabled = !(passwordValid && currentValid);
    }

    function checkField(input, showEmptyAsValid) {
      showEmptyAsValid = showEmptyAsValid || false;
      if (!input) return { valid: false };
      const field = input.dataset.field;
      const rawValue = input.value;
      const value = rawValue.trim();
      let valid = true;
      let message = '';
      let forceInvalid = false;

      if (!validateNoSpaces(rawValue)) {
        valid = false;
        message = 'Пробелы и табы запрещены';
        forceInvalid = true;
      } else if (!value) {
        valid = showEmptyAsValid;
        message = showEmptyAsValid ? '' : 'Поле не может быть пустым';
        if (!showEmptyAsValid) {
          forceInvalid = true;
        }
      } else if (field === 'current_password') {
        // Текущий пароль - будет проверен через AJAX
        valid = true;
        message = '';
      } else if (field === 'password') {
        if (value.length < 6) {
          valid = false;
          message = 'Минимум 6 символов';
          forceInvalid = true;
        }
      }

      setState(input, valid, message, forceInvalid);
      return { valid, message };
    }

    function checkCurrentPassword(input) {
      const value = input.value.trim();
      if (!value || !validateNoSpaces(input.value)) {
        // Если поле пустое или содержит пробелы - не делаем AJAX запрос
        if (!value) {
          validationState.current.checked = false;
          validationState.current.valid = true;
        } else {
          // Поле содержит пробелы - подсвечиваем красным
          setState(input, false, 'Пробелы запрещены', true);
          validationState.current.checked = true;
          validationState.current.valid = false;
        }
        updateSubmitState();
        return;
      }

      // Снимаем любую предыдущую подсветку перед AJAX-запросом
      const wrap = input.closest('.field-input-wrap');
      const label = input.closest('.field-label');
      if (wrap) {
        wrap.classList.remove('input-valid', 'input-invalid');
      }
      if (label) {
        label.classList.remove('input-valid', 'input-invalid');
      }
      input.classList.remove('input-valid', 'input-invalid');

      fetch('/?route=users&check_password=' + encodeURIComponent(value), { credentials: 'same-origin' })
        .then(res => {
          if (!res.ok) {
            throw new Error('HTTP error ' + res.status);
          }
          return res.json();
        })
        .then(data => {
          if (data.ok && data.valid) {
            // Пароль подтверждён - подсвечиваем зелёным
            setState(input, true, data.message || 'Пароль подтверждён');
            validationState.current.valid = true;
            validationState.current.checked = true;
          } else {
            // Ошибка проверки - неверный пароль, подсвечиваем красным
            setState(input, false, data.message || 'Неверный текущий пароль', true);
            validationState.current.valid = false;
            validationState.current.checked = true;
          }
          updateSubmitState();
        })
        .catch((err) => {
          // Ошибка запроса - помечаем как проверенное, но невалидное
          console.error('Ошибка проверки пароля:', err);
          validationState.current.checked = true;
          validationState.current.valid = false;
          setState(input, false, 'Ошибка проверки пароля', true);
          updateSubmitState();
        });
    }

    currentPasswordInput.dataset.field = 'current_password';
    passwordInput.dataset.field = 'password';

    // Обработчик кнопок "глаз" для показа/скрытия пароля
    document.querySelectorAll('.field-toggle').forEach(btn => {
      btn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        // Переключаем видимость иконок
        const openIcon = this.querySelector('.icon-eye-open');
        const closedIcon = this.querySelector('.icon-eye-closed');

        if (isPassword) {
          // Показываем пароль - скрываем открытый глаз, показываем зачёркнутый
          if (openIcon) openIcon.style.display = 'none';
          if (closedIcon) closedIcon.style.display = 'block';
          this.setAttribute('aria-label', 'Скрыть пароль');
          this.setAttribute('title', 'Скрыть пароль');
        } else {
          // Скрываем пароль - показываем открытый глаз, скрываем зачёркнутый
          if (openIcon) openIcon.style.display = 'block';
          if (closedIcon) closedIcon.style.display = 'none';
          this.setAttribute('aria-label', 'Показать пароль');
          this.setAttribute('title', 'Показать пароль');
        }
      });
    });

    const debounced = (fn, delay) => {
      delay = delay || 300;
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), delay);
      };
    };

    // Проверка текущего пароля при вводе (с задержкой для AJAX)
    currentPasswordInput.addEventListener('input', debounced(() => {
      const result = checkField(currentPasswordInput, true);
      if (result.valid && currentPasswordInput.value.trim()) {
        checkCurrentPassword(currentPasswordInput);
      } else if (!currentPasswordInput.value.trim()) {
        // Поле пустое - сбрасываем состояние
        validationState.current.checked = false;
        validationState.current.valid = true;
        updateSubmitState();
      } else {
        // Поле содержит пробелы - подсвечиваем красным
        validationState.current.checked = true;
        validationState.current.valid = false;
        updateSubmitState();
      }
    }));

    // Проверка нового пароля при вводе
    passwordInput.addEventListener('input', () => {
      const result = checkField(passwordInput, true);
      // Проверяем, что новый пароль отличается от текущего
      const passwordValue = passwordInput.value.trim();
      const currentValue = currentPasswordInput.value.trim();

      if (result.valid && passwordValue) {
        // Если новый пароль совпадает с текущим - это ошибка
        if (currentValue && passwordValue === currentValue) {
          setState(passwordInput, false, 'Новый пароль должен отличаться от текущего', true);
          validationState.password.valid = false;
        } else if (passwordValue.length >= 6) {
          // Новый пароль не менее 6 символов и отличается от текущего (или текущий ещё не введён)
          validationState.password.valid = true;
        }
      }
      updateSubmitState();
    });

    // Потеря фокуса на поле текущего пароля - финальная проверка
    currentPasswordInput.addEventListener('blur', () => {
      const value = currentPasswordInput.value.trim();
      if (value && validateNoSpaces(currentPasswordInput.value)) {
        checkCurrentPassword(currentPasswordInput);
      } else if (!validateNoSpaces(currentPasswordInput.value) && currentPasswordInput.value) {
        // Поле содержит пробелы - подсвечиваем красным
        setState(currentPasswordInput, false, 'Пробелы запрещены', true);
        validationState.current.checked = true;
        validationState.current.valid = false;
        updateSubmitState();
      }
    });

    // Изначально кнопка активна (пароль можно не менять)
    submitButton.disabled = false;

    submitButton.addEventListener('click', function(event) {
      const passwordValue = passwordInput.value.trim();

      if (passwordValue) {
        // Если пароль меняется - проверяем все поля
        const currentResult = checkField(currentPasswordInput, false);
        const passwordResult = checkField(passwordInput, false);

        if (!currentResult.valid || !passwordResult.valid) {
          event.preventDefault();
        } else if (!validationState.current.checked || !validationState.password.checked) {
          // Если проверка ещё не завершена - предотвращаем отправку
          event.preventDefault();
        } else if (!validationState.current.valid || !validationState.password.valid) {
          event.preventDefault();
        }
      }
      // Если пароль пустой - форма отправляется без проверки паролей
    });
  }

  // Инициализация после загрузки DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProfilePasswordValidation);
  } else {
    initProfilePasswordValidation();
  }
})();