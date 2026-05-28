<?php
// profile.php - модуль для users.php (не должен загружаться автономно)

// Проверка: если файл вызван напрямую (автономно), перенаправляем на users.php
if (!isset($standalone) || $standalone !== false) {
    // Файл вызван напрямую, а не через include из users.php
    // Перенаправляем на страницу пользователей с вкладкой профиля
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../includes/functions.php';
    require_login();

    header('Location: /?route=users&tab=profile');
    exit;
}

// Режим модуля: переменная $user должна быть установлена в users.php
if (!isset($user)) {
    // Это не должно произойти, но на всякий случай
    $user = current_user();
}

// Принудительная загрузка актуального адреса из БД для отображения в форме
$stmt = db()->prepare("SELECT address FROM users WHERE id=?");
$stmt->execute([$user['id']]);
$dbUser = $stmt->fetch();
if ($dbUser && isset($dbUser['address'])) {
    $user['address'] = $dbUser['address'];
    // Обновляем сессию актуальными данными
    $_SESSION['user']['address'] = $dbUser['address'];
}

$action = $_POST['action'] ?? '';

// Загрузка профиля доставки
$deliveryProfile = null;
$stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
$stmt->execute([$user['id']]);
$deliveryProfile = $stmt->fetch() ?: null;

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_profile') {
    csrf_check();

    // Проверяем, какая форма была отправлена (по наличию ключевых полей)
    $isDeliveryForm = isset($_POST['last_name']) || isset($_POST['first_name']) || isset($_POST['city']);

    // Поля профиля
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';

    // Поля доставки
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
        // Сохранение только данных доставки
        if (!$firstName || !$lastName || !$city || !$street || !$building) {
            flash('Заполните обязательные поля доставки (Фамилия, Имя, Город, Улица, Дом)','error');
        } else {
            try {
                // Формирование адреса доставки из профиля
                $fullAddress = trim("$lastName $firstName $middleName $postalCode $region $district $city $street $building $apartment");
                $fullAddress = preg_replace('/\s+/', ' ', $fullAddress);

                // Сохранение профиля доставки и обновление адреса в users
                if ($deliveryProfile) {
                    db()->prepare("UPDATE user_delivery_profiles SET first_name=?, last_name=?, middle_name=?, postal_code=?, region=?, district=?, city=?, street=?, building=?, apartment=? WHERE user_id=?")
                        ->execute([$firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment, $user['id']]);
                } else {
                    db()->prepare("INSERT INTO user_delivery_profiles (user_id, first_name, last_name, middle_name, postal_code, region, district, city, street, building, apartment) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([$user['id'], $firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment]);
                }

                // Обновление поля address в таблице users на основе данных профиля доставки
                db()->prepare("UPDATE users SET address=? WHERE id=?")->execute([$fullAddress, $user['id']]);

                // Обновление сессии
                $_SESSION['user']['address'] = $fullAddress;
                $user = current_user();

                // Перезагрузка профиля доставки
                $stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
                $stmt->execute([$user['id']]);
                $deliveryProfile = $stmt->fetch() ?: null;

                flash('Адрес доставки сохранён','success');

            } catch (PDOException $e) {
                flash('Ошибка сохранения адреса доставки: ' . $e->getMessage(),'error');
            }
        }
    } else {
        // Сохранение профиля пользователя
        if (!$name || !$email) {
            flash('Заполните имя и email','error');
        } elseif ($password !== '' && strlen($password) < 6) {
            flash('Пароль должен быть не менее 6 символов','error');
        } else {
            // Проверка текущего пароля при смене пароля
            if ($password !== '') {
                if (!password_verify($currentPassword, $user['password'])) {
                    flash('Неверный текущий пароль','error');
                } else {
                    $stmt = db()->prepare("SELECT password FROM users WHERE id=?");
                    $stmt->execute([$user['id']]);
                    $dbUser = $stmt->fetch();
                    if (!password_verify($currentPassword, $dbUser['password'])) {
                        flash('Неверный текущий пароль','error');
                    }
                }
            }

            if (empty($_SESSION['flash'])) {
                try {
                    $params = [$name, $email, $phone];
                    $sql = 'UPDATE users SET name=?, email=?, phone=?';
                    if ($password !== '') {
                        $sql .= ', password=?';
                        $params[] = password_hash($password, PASSWORD_BCRYPT);
                    }
                    $sql .= ' WHERE id=?';
                    $params[] = $user['id'];
                    db()->prepare($sql)->execute($params);

                    // Формирование адреса доставки из профиля
                    $fullAddress = trim("$lastName $firstName $middleName $postalCode $region $district $city $street $building $apartment");
                    $fullAddress = preg_replace('/\s+/', ' ', $fullAddress);

                    // Сохранение профиля доставки и обновление адреса в users
                    if ($deliveryProfile) {
                        db()->prepare("UPDATE user_delivery_profiles SET first_name=?, last_name=?, middle_name=?, postal_code=?, region=?, district=?, city=?, street=?, building=?, apartment=? WHERE user_id=?")
                            ->execute([$firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment, $user['id']]);
                    } elseif (!empty($firstName) || !empty($lastName)) {
                        db()->prepare("INSERT INTO user_delivery_profiles (user_id, first_name, last_name, middle_name, postal_code, region, district, city, street, building, apartment) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                            ->execute([$user['id'], $firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment]);
                    }

                    // Обновление поля address в таблице users на основе данных профиля доставки
                    if (!empty($fullAddress)) {
                        db()->prepare("UPDATE users SET address=? WHERE id=?")->execute([$fullAddress, $user['id']]);
                    }

                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $name,
                        'email' => $email,
                        'role' => $user['role'],
                        'phone' => $phone,
                        'address' => $fullAddress,
                    ];
                    $user = current_user();
                    flash('Профиль сохранён','success');

                } catch (PDOException $e) {
                    flash('Ошибка сохранения профиля: ' . $e->getMessage(),'error');
                }
            }
        }
    }

    // Перезагрузка профиля доставки после сохранения (если еще не загружен)
    if ($isDeliveryForm && empty($deliveryProfile)) {
        $stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
        $stmt->execute([$user['id']]);
        $deliveryProfile = $stmt->fetch() ?: null;
    }
}
?>

<section class="account-panel admin-content-block">
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save_profile">
    <label>Логин <input type="text" name="name" value="<?= e($user['name']) ?>" required></label>
    <label>Email <input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
    <label>Телефон <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+7 введите ваш номер телефона"></label>
    <label>Адрес доставки <textarea name="address" readonly placeholder="Зарегистрируйте данные для доставки"><?= e($user['address'] ?? '') ?></textarea></label>
    <div class="password-row">
      <label>Текущий пароль <input type="password" name="current_password" placeholder="Оставьте пустым, если не меняете пароль"></label>
      <label>Новый пароль <input type="password" name="password" placeholder="Оставьте пустым, чтобы не менять"></label>
    </div>
    <button class="btn btn-ghost">Сохранить профиль</button>
  </form>

  <h3 style="margin-top: 2rem;">Данные для доставки</h3>
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save_profile">
    <div class="delivery-grid">
      <label>Фамилия * <input type="text" name="last_name" value="<?= e($deliveryProfile['last_name'] ?? '') ?>" required></label>
      <label>Имя * <input type="text" name="first_name" value="<?= e($deliveryProfile['first_name'] ?? '') ?>" required></label>
      <label>Отчество <input type="text" name="middle_name" value="<?= e($deliveryProfile['middle_name'] ?? '') ?>"></label>
      <label>Почтовый индекс <input type="text" name="postal_code" value="<?= e($deliveryProfile['postal_code'] ?? '') ?>"></label>
      <label>Область/Регион <input type="text" name="region" value="<?= e($deliveryProfile['region'] ?? '') ?>"></label>
      <label>Район <input type="text" name="district" value="<?= e($deliveryProfile['district'] ?? '') ?>"></label>
      <label>Город/Село * <input type="text" name="city" value="<?= e($deliveryProfile['city'] ?? '') ?>" required></label>
      <label>Улица * <input type="text" name="street" value="<?= e($deliveryProfile['street'] ?? '') ?>" required></label>
      <label>Дом * <input type="text" name="building" value="<?= e($deliveryProfile['building'] ?? '') ?>" required></label>
      <label>Квартира <input type="text" name="apartment" value="<?= e($deliveryProfile['apartment'] ?? '') ?>"></label>
    </div>
    <button class="btn btn-ghost" style="margin-top: 1rem;">Сохранить данные</button>
  </form>
</section>