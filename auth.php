<?php
// auth.php
require_once __DIR__ . '/includes/functions.php';

$tab = $_GET['tab'] ?? 'login';

if ($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['check'])) {
    header('Content-Type: application/json');
    $check = $_GET['check'];
    $value = trim($_GET['value'] ?? '');
    $allowed = ['name','email'];
    if (!in_array($check, $allowed, true) || $value === '') {
        echo json_encode(['ok' => false, 'exists' => false]);
        exit;
    }
    // Проверяем наличие пробелов в значении
    if (preg_match('/\s/', $value)) {
        echo json_encode(['ok' => false, 'exists' => false]);
        exit;
    }
    // Для email дополнительно проверяем формат
    if ($check === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'exists' => false]);
        exit;
    }
    // Проверка занятости в базе данных
    if ($check === 'email') {
        $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE email=?");
    } else {
        $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE name=?");
    }
    $stmt->execute([$value]);
    $exists = $stmt->fetchColumn() > 0;
    echo json_encode(['ok' => true, 'exists' => (bool)$exists]);
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    if (($_POST['mode'] ?? '') === 'login') {
        $identifier = trim($_POST['identifier']);
        // Определяем, что введено: email или логин
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $stmt = db()->prepare("SELECT * FROM users WHERE email=?");
            $stmt->execute([$identifier]);
        } else {
            $stmt = db()->prepare("SELECT * FROM users WHERE name=?");
            $stmt->execute([$identifier]);
        }
        $u = $stmt->fetch();
        if ($u && password_verify($_POST['password'], $u['password'])) {
            unset($u['password']);
            $_SESSION['user'] = $u;
            flash('Добро пожаловать, '. $u['name'] .'!','success');
            redirect('/');
        } else {
            flash('Неверный логин/email или пароль','error');
        }
    } else {
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $pass = $_POST['password'];
        if ($name === '') {
          flash('Логин не может быть пустым','error');
        } elseif (preg_match('/[\s\t]/', $name)) {
          flash('Логин не может содержать пробелы или табы','error');
        } elseif (strlen($name) < 4) {
          flash('Логин должен быть не менее 4 символов','error');
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\s\t]/', $email)) {
            flash('Неверный email или email содержит пробелы','error');
        } elseif (preg_match('/[\s\t]/', $pass)) {
            flash('Пароль не может содержать пробелы или табы','error');
        } elseif (strlen($pass) < 6) {
            flash('Пароль минимум 6 символов','error');
        } else {
            $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE email=? OR name=?");
            $stmt->execute([$email, $name]);
            if ($stmt->fetchColumn() > 0) {
                flash('Имя пользователя или email уже заняты','error');
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = db()->prepare("INSERT INTO users (email,password,name,role) VALUES (?,?,?,'customer')");
                $stmt->execute([$email, $hash, $name]);
                $id = db()->lastInsertId();
                $_SESSION['user'] = ['id'=>(int)$id, 'email'=>$email, 'name'=>$name, 'role'=>'customer'];
                flash('Регистрация успешна!','success');
                redirect('/');
            }
        }
    }
}

$pageTitle = 'Вход / Регистрация';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="tabs">
    <a href="?tab=login" class="<?= $tab==='login'?'active':'' ?>">Вход</a>
    <a href="?tab=register" class="<?= $tab==='register'?'active':'' ?>">Регистрация</a>
  </div>

  <?php if($tab==='login'): ?>
    <form method="post" class="auth-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="mode" value="login">
      <label>Логин или Email <input type="text" name="identifier" required></label>
      <label>Пароль <input type="password" name="password" required></label>
      <button class="btn btn-primary">Войти</button>
      <p class="muted small">Тест: admin@lumen.ru / admin123</p>
    </form>
  <?php else: ?>
    <form method="post" class="auth-form" id="register-form" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="mode" value="register">

      <label class="field-label">Логин
        <div class="field-input-wrap">
          <input type="text" name="name" id="register-name" autocomplete="username" required>
        </div>
        <div class="field-hint" id="hint-name"></div>
      </label>

      <label class="field-label">Email
        <div class="field-input-wrap">
          <input type="email" name="email" id="register-email" autocomplete="email" required>
        </div>
        <div class="field-hint" id="hint-email"></div>
      </label>

      <label class="field-label">Пароль
        <div class="field-input-wrap">
          <input type="password" name="password" id="register-password" minlength="6" autocomplete="new-password" required>
          <button type="button" class="field-toggle" data-target="register-password" aria-label="Показать пароль" data-visible="false" title="Показать пароль">
            <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
          </button>
        </div>
        <div class="field-hint" id="hint-password"></div>
      </label>

      <label class="field-label">Подтверждение пароля
        <div class="field-input-wrap">
          <input type="password" name="confirm_password" id="register-confirm-password" autocomplete="new-password" required>
          <button type="button" class="field-toggle" data-target="register-confirm-password" aria-label="Показать пароль" data-visible="false" title="Показать пароль">
            <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
          </button>
        </div>
        <div class="field-hint" id="hint-confirm-password"></div>
      </label>

      <button class="btn btn-primary" id="register-submit">Создать аккаунт</button>
    </form>
  <?php endif; ?>
</div>

<script>
(function(){
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const nameInput = document.getElementById('register-name');
  const emailInput = document.getElementById('register-email');
  const passwordInput = document.getElementById('register-password');
  const confirmInput = document.getElementById('register-confirm-password');
  const submitButton = document.getElementById('register-submit');
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

  function setState(input, valid, message, forceInvalid = false) {
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

  function checkField(input, showEmptyAsValid = false) {
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

    fetch(`?check=${type}&value=` + encodeURIComponent(value), { credentials: 'same-origin' })
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

  if (emailInput && nameInput && passwordInput && confirmInput) {
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

    const debounced = (fn, delay = 300) => {
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
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>