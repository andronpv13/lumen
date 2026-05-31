<?php
/**
 * Авторизация и регистрация
 * Маршрут: /?route=auth
 */

require_once __DIR__ . '/../includes/functions.php';

// AJAX-проверка занятости email/имени
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
    $check = $_GET['check'] ?? '';
    $value = trim($_GET['value'] ?? '');
    
    // Валидация типа проверки
    if (!in_array($check, ['email', 'name'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Неверный параметр']);
        exit;
    }
    
    // Проверка на пустое значение
    if ($value === '') {
        echo json_encode(['ok' => false, 'exists' => false]);
        exit;
    }
    
    // Проверка на пробелы (исправлено: /\s/ вместо /[st]/)
    if (preg_match('/\s/', $value)) {
        echo json_encode(['ok' => false, 'exists' => false]);
        exit;
    }
    
    // Валидация email если нужно
    if ($check === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'exists' => false]);
        exit;
    }
    
    // Проверка в БД
    $field = ($check === 'email') ? 'email' : 'name';
    $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE $field = ?");
    $stmt->execute([$value]);
    $exists = (bool)$stmt->fetchColumn();
    
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'exists' => $exists]);
    exit;
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $mode = post_param('mode', '');
    
    if ($mode === 'login') {
        // === ВХОД ===
        $identifier = trim(post_param('identifier', ''));
        $password = post_param('password', '');
        
        if ($identifier === '' || $password === '') {
            flash('Заполните все поля', 'error');
        } else {
            // Определяем тип идентификатора
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
            } else {
                $stmt = db()->prepare("SELECT * FROM users WHERE name = ?");
            }
            $stmt->execute([$identifier]);
            $u = $stmt->fetch();
            
            if ($u && password_verify($password, $u['password'])) {
                // Сохраняем в сессию БЕЗ пароля
                $_SESSION['user'] = [
                    'id' => (int)$u['id'],
                    'email' => $u['email'],
                    'name' => $u['name'],
                    'role' => $u['role'],
                    'phone' => $u['phone'] ?? '',
                    'address' => $u['address'] ?? '',
                ];
                flash('Добро пожаловать, ' . e($u['name']) . '!', 'success');
                redirect('/');
            } else {
                flash('Неверный логин/email или пароль', 'error');
            }
        }
        
    } else {
        // === РЕГИСТРАЦИЯ ===
        $email = trim(post_param('email', ''));
        $name = trim(post_param('name', ''));
        $pass = post_param('password', '');
        $passConfirm = post_param('password_confirm', '');
        
        $errors = [];
        
        // Валидация имени
        if ($name === '') {
            $errors[] = 'Логин не может быть пустым';
        } elseif (preg_match('/\s/', $name)) {
            $errors[] = 'Логин не может содержать пробелы';
        } elseif (strlen($name) < 4) {
            $errors[] = 'Логин должен быть не менее 4 символов';
        }
        
        // Валидация email
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Неверный email';
        } elseif (preg_match('/\s/', $email)) {
            $errors[] = 'Email не может содержать пробелы';
        }
        
        // Валидация пароля
        $passValidation = validate_password($pass);
        if (!$passValidation['valid']) {
            $errors = array_merge($errors, $passValidation['errors']);
        }
        
        // Подтверждение пароля
        if ($pass !== $passConfirm) {
            $errors[] = 'Пароли не совпадают';
        }
        
        // Проверка занятости
        if (empty($errors)) {
            $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR name = ?");
            $stmt->execute([$email, $name]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Имя пользователя или email уже заняты';
            }
        }
        
        // Создание пользователя или вывод ошибок
        if ($errors) {
            flash(implode('<br>', $errors), 'error');
        } else {
            $hash = hash_password($pass);
            $stmt = db()->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, 'customer')");
            $stmt->execute([$email, $hash, $name]);
            
            $userId = (int)db()->lastInsertId();
            $_SESSION['user'] = [
                'id' => $userId,
                'email' => $email,
                'name' => $name,
                'role' => 'customer',
                'phone' => '',
                'address' => '',
            ];
            
            flash('Регистрация успешна!', 'success');
            redirect('/');
        }
    }
    
    redirect('/?route=auth');
}

// Если пользователь уже авторизован — редирект
if (current_user()) {
    redirect('/');
}

$pageTitle = 'Вход / Регистрация';
?>

<!-- Форма авторизации (разметка без изменений) -->
<section class="auth-page">
    <h1>Вход / Регистрация</h1>
    
    <div class="auth-tabs">
        <button class="tab-btn active" data-tab="login">Вход</button>
        <button class="tab-btn" data-tab="register">Регистрация</button>
    </div>
    
    <!-- Форма входа -->
    <form method="POST" class="auth-form" id="login-form">
        <input type="hidden" name="mode" value="login">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        
        <p>
            <label>Логин или Email:
                <input type="text" name="identifier" required autocomplete="username">
            </label>
        </p>
        <p>
            <label>Пароль:
                <input type="password" name="password" required autocomplete="current-password">
            </label>
        </p>
        <button type="submit" class="btn btn-primary">Войти</button>
    </form>
    
    <!-- Форма регистрации -->
    <form method="POST" class="auth-form hidden" id="register-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        
        <p>
            <label>Логин *:
                <input type="text" name="name" required minlength="4" 
                       pattern="^[^\s]{4,}$" 
                       data-check="name" 
                       autocomplete="username">
                <span class="check-result"></span>
            </label>
        </p>
        <p>
            <label>Email *:
                <input type="email" name="email" required 
                       data-check="email" 
                       autocomplete="email">
                <span class="check-result"></span>
            </label>
        </p>
        <p>
            <label>Пароль *:
                <input type="password" name="password" required minlength="6" autocomplete="new-password">
            </label>
        </p>
        <p>
            <label>Подтверждение пароля *:
                <input type="password" name="password_confirm" required autocomplete="new-password">
            </label>
        </p>
        <button type="submit" class="btn btn-primary">Создать аккаунт</button>
    </form>
    
    <div class="auth-demo">
        <p><small>Тестовые аккаунты:</small></p>
        <p><small>Админ: admin@lumen.ru / admin123</small></p>
        <p><small>Модератор: moderator@lumen.ru / mod123</small></p>
    </div>
</section>

<script>
// Простая валидация на клиенте (без изменения существующих стилей)
document.addEventListener('DOMContentLoaded', function() {
    // Переключение табов
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.auth-form').forEach(f => f.classList.add('hidden'));
            this.classList.add('active');
            document.getElementById(this.dataset.tab + '-form').classList.remove('hidden');
        });
    });
    
    // AJAX-проверка занятости
    document.querySelectorAll('[data-check]').forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const check = this.dataset.check;
            const value = this.value.trim();
            const result = this.closest('p').querySelector('.check-result');
            
            if (value.length < (check === 'name' ? 4 : 5)) {
                result.textContent = '';
                return;
            }
            
            timeout = setTimeout(() => {
                fetch('/?route=auth&ajax=1&check=' + check + '&value=' + encodeURIComponent(value))
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            result.textContent = data.exists ? '✗ Уже занят' : '✓ Доступно';
                            result.className = 'check-result ' + (data.exists ? 'error' : 'success');
                        }
                    });
            }, 300);
        });
    });
});
</script>