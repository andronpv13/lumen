<?php
/**
 * Страница профиля пользователя
 * Маршрут: /?route=users&tab=profile
 * Совместимо с assets/js/main.js (initProfilePasswordValidation)
 */

if (!defined('STANDALONE')) {
    define('STANDALONE', false);
}

require_login();
$user = current_user();

if (!$user) {
    header('Location: /?route=auth');
    exit;
}

// Получаем актуальный адрес из БД
$stmt = db()->prepare("SELECT address FROM users WHERE id=?");
$stmt->execute([$user['id']]);
$dbUser = $stmt->fetch();
if ($dbUser && isset($dbUser['address'])) {
    $user['address'] = $dbUser['address'];
    $_SESSION['user']['address'] = $dbUser['address'];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// AJAX-проверка текущего пароля
if ($action === 'check_password' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $checkPass = $_POST['check_password'] ?? '';
    
    if (empty($checkPass) || /\s/.test($checkPass)) {
        echo json_encode(['ok' => false, 'valid' => false, 'message' => 'Пробелы запрещены']);
        exit;
    }
    
    $stmt = db()->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$user['id']]);
    $dbUserCheck = $stmt->fetch();
    
    if ($dbUserCheck && password_verify($checkPass, $dbUserCheck['password'])) {
        echo json_encode(['ok' => true, 'valid' => true, 'message' => 'Пароль подтверждён']);
    } else {
        echo json_encode(['ok' => false, 'valid' => false, 'message' => 'Неверный текущий пароль']);
    }
    exit;
}

// Загрузка профиля доставки
$stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
$stmt->execute([$user['id']]);
$deliveryProfile = $stmt->fetch() ?: null;

// Обработка POST-запросов (сохранение профиля)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_profile') {
    csrf_check();

    $isDeliveryForm = isset($_POST['last_name']) || isset($_POST['first_name']) || isset($_POST['city']);

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $apartment = trim($_POST['apartment'] ?? '');

    if ($isDeliveryForm) {
        // Сохранение данных доставки
        if (!$firstName || !$lastName || !$city || !$street || !$building) {
            flash('Заполните обязательные поля доставки', 'error');
        } else {
            try {
                $fullAddress = trim("$lastName $firstName $middleName $postalCode $region $district $city $street $building $apartment");
                $fullAddress = preg_replace('/\s+/', ' ', $fullAddress);

                if ($deliveryProfile) {
                    db()->prepare("UPDATE user_delivery_profiles SET first_name=?, last_name=?, middle_name=?, postal_code=?, region=?, district=?, city=?, street=?, building=?, apartment=? WHERE user_id=?")
                        ->execute([$firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment, $user['id']]);
                } else {
                    db()->prepare("INSERT INTO user_delivery_profiles (user_id, first_name, last_name, middle_name, postal_code=?, region=?, district=?, city=?, street=?, building=?, apartment=?) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([$user['id'], $firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment]);
                }

                db()->prepare("UPDATE users SET address=? WHERE id=?")->execute([$fullAddress, $user['id']]);
                $_SESSION['user']['address'] = $fullAddress;
                $user = current_user();

                $stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
                $stmt->execute([$user['id']]);
                $deliveryProfile = $stmt->fetch() ?: null;

                flash('Адрес доставки сохранён', 'success');
            } catch (PDOException $e) {
                flash('Ошибка сохранения адреса доставки', 'error');
            }
        }
    } else {
        // Сохранение профиля пользователя
        $hasPasswordChange = ($password !== '');
        
        if (!$name || !$email) {
            flash('Заполните имя и email', 'error');
        } elseif ($hasPasswordChange) {
            if (strlen($password) < 6) {
                flash('Пароль должен быть не менее 6 символов', 'error');
            } elseif ($password !== $confirmPassword) {
                flash('Пароли не совпадают', 'error');
            } else {
                $stmt = db()->prepare("SELECT password FROM users WHERE id=?");
                $stmt->execute([$user['id']]);
                $dbUserCheck = $stmt->fetch();
                
                if (!$dbUserCheck || !password_verify($currentPassword, $dbUserCheck['password'])) {
                    flash('Неверный текущий пароль', 'error');
                } elseif ($password === $currentPassword) {
                    flash('Новый пароль должен отличаться от текущего', 'error');
                } else {
                    try {
                        $params = [$name, $email, $phone, password_hash($password, PASSWORD_BCRYPT), $user['id']];
                        db()->prepare("UPDATE users SET name=?, email=?, phone=?, password=? WHERE id=?")->execute($params);
                        
                        $_SESSION['user'] = [
                            'id' => $user['id'],
                            'name' => $name,
                            'email' => $email,
                            'role' => $user['role'],
                            'phone' => $phone,
                            'address' => $_SESSION['user']['address'] ?? '',
                        ];
                        $user = current_user();
                        flash('Профиль и пароль обновлены', 'success');
                    } catch (PDOException $e) {
                        flash('Ошибка сохранения профиля', 'error');
                    }
                }
            }
        } else {
            // Сохранение без смены пароля
            try {
                db()->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?")
                    ->execute([$name, $email, $phone, $user['id']]);
                
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $name,
                    'email' => $email,
                    'role' => $user['role'],
                    'phone' => $phone,
                    'address' => $_SESSION['user']['address'] ?? '',
                ];
                $user = current_user();
                flash('Профиль сохранён', 'success');
            } catch (PDOException $e) {
                flash('Ошибка сохранения профиля', 'error');
            }
        }
    }

    if (empty($deliveryProfile)) {
        $stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
        $stmt->execute([$user['id']]);
        $deliveryProfile = $stmt->fetch() ?: null;
    }
}
?>

<div class="account-panel">
    <h1 class="page-title">Данные профиля</h1>

    <?php if ($f = flash()): ?>
        <p class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></p>
    <?php endif; ?>

    <form method="post" class="auth-form form-section" id="password-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_profile">

        <label class="field-label">
            Логин
            <div class="field-input-wrap">
                <input type="text" name="name" value="<?= e($user['name']) ?>" required>
            </div>
        </label>

        <label class="field-label">
            Email
            <div class="field-input-wrap">
                <input type="email" name="email" value="<?= e($user['email']) ?>" required>
            </div>
        </label>

        <label class="field-label">
            Телефон
            <div class="field-input-wrap">
                <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+7 (___) ___-__-__">
            </div>
        </label>

        <label class="field-label">
            Адрес доставки
            <div class="field-input-wrap">
                <input type="text" class="input-readonly" value="<?= e($user['address'] ?? '') ?>" readonly>
            </div>
            <span class="field-hint">Адрес формируется автоматически из данных доставки ниже</span>
        </label>

        <hr class="divider">

        <h3 class="section-title">Смена пароля</h3>

        <label class="field-label">
            Текущий пароль
            <div class="field-input-wrap">
                <input type="password" name="current_password" id="profile-current-password" autocomplete="current-password">
                <button type="button" class="password-toggle-btn" data-target="profile-current-password" aria-label="Показать/скрыть пароль">
                    <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <span id="hint-current-password" class="field-hint"></span>
        </label>

        <label class="field-label">
            Новый пароль
            <div class="field-input-wrap">
                <input type="password" name="password" id="profile-password" autocomplete="new-password" minlength="6">
                <button type="button" class="password-toggle-btn" data-target="profile-password" aria-label="Показать/скрыть пароль">
                    <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <span id="hint-password" class="field-hint"></span>
        </label>

        <label class="field-label">
            Подтвердите новый пароль
            <div class="field-input-wrap">
                <input type="password" name="confirm_password" id="profile-confirm-password" autocomplete="new-password">
            </div>
            <span id="hint-confirm-password" class="field-hint"></span>
        </label>

        <button type="submit" class="btn btn-lg btn-primary" id="profile-submit">Сохранить профиль</button>
    </form>

    <hr class="divider">

    <h2 class="section-title">Данные для доставки</h2>
    <form method="post" class="auth-form form-section delivery-grid">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_profile">

        <label>Фамилия * <input type="text" name="last_name" value="<?= e($deliveryProfile['last_name'] ?? '') ?>" required></label>
        <label>Имя * <input type="text" name="first_name" value="<?= e($deliveryProfile['first_name'] ?? '') ?>" required></label>
        <label>Отчество <input type="text" name="middle_name" value="<?= e($deliveryProfile['middle_name'] ?? '') ?>"></label>
        <label>Почтовый индекс <input type="text" name="postal_code" value="<?= e($deliveryProfile['postal_code'] ?? '') ?>" pattern="[0-9]{6}"></label>
        <label>Область/Регион <input type="text" name="region" value="<?= e($deliveryProfile['region'] ?? '') ?>"></label>
        <label>Район <input type="text" name="district" value="<?= e($deliveryProfile['district'] ?? '') ?>"></label>
        <label>Город/Село * <input type="text" name="city" value="<?= e($deliveryProfile['city'] ?? '') ?>" required></label>
        <label>Улица * <input type="text" name="street" value="<?= e($deliveryProfile['street'] ?? '') ?>" required></label>
        <label>Дом * <input type="text" name="building" value="<?= e($deliveryProfile['building'] ?? '') ?>" required></label>
        <label>Квартира <input type="text" name="apartment" value="<?= e($deliveryProfile['apartment'] ?? '') ?>"></label>

        <button type="submit" class="btn btn-lg btn-primary">Сохранить данные</button>
    </form>
</div>

<script src="/assets/js/profile.js" defer></script>