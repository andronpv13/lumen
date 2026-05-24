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