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

<?php require __DIR__ . '/includes/footer.php'; ?>