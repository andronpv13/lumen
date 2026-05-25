<?php
// profile.php - может использоваться как отдельно, так и включаться в users.php
if (!isset($user)) {
    require_once __DIR__ . '/../includes/functions.php';
    require_login();
    $user = current_user();
    $standalone = true;
} else {
    $standalone = false;
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

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
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

    if (!$name || !$email) {
        flash('Заполните имя и email','error');
    } elseif ($password !== '' && strlen($password) < 6) {
        flash('Пароль должен быть не менее 6 символов','error');
    } else {
        // Проверка текущего пароля при смене пароля
        if ($password !== '') {
            if (!password_verify($currentPassword, $user['password'])) {
                flash('Неверный текущий пароль','error');
                if ($standalone) {
                    redirect('/users.php?tab=profile');
                }
            } else {
                $stmt = db()->prepare("SELECT password FROM users WHERE id=?");
                $stmt->execute([$user['id']]);
                $dbUser = $stmt->fetch();
                if (!password_verify($currentPassword, $dbUser['password'])) {
                    flash('Неверный текущий пароль','error');
                    if ($standalone) {
                        redirect('/users.php?tab=profile');
                    }
                }
            }
        }

        if (empty($_SESSION['flash'])) {
            try {
                $params = [$name, $email, $phone, $address];
                $sql = 'UPDATE users SET name=?, email=?, phone=?, address=?';
                if ($password !== '') {
                    $sql .= ', password=?';
                    $params[] = password_hash($password, PASSWORD_BCRYPT);
                }
                $sql .= ' WHERE id=?';
                $params[] = $user['id'];
                db()->prepare($sql)->execute($params);

                // Сохранение профиля доставки
                if ($deliveryProfile) {
                    db()->prepare("UPDATE user_delivery_profiles SET first_name=?, last_name=?, middle_name=?, postal_code=?, region=?, district=?, city=?, street=?, building=?, apartment=? WHERE user_id=?")
                        ->execute([$firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment, $user['id']]);
                } else {
                    db()->prepare("INSERT INTO user_delivery_profiles (user_id, first_name, last_name, middle_name, postal_code, region, district, city, street, building, apartment) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([$user['id'], $firstName, $lastName, $middleName, $postalCode, $region, $district, $city, $street, $building, $apartment]);
                }

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $name,
                    'email' => $email,
                    'role' => $user['role'],
                    'phone' => $phone,
                    'address' => $address,
                ];
                $user = current_user();
                flash('Профиль сохранён','success');

                if ($standalone) {
                    redirect('/users.php?tab=profile');
                }
            } catch (PDOException $e) {
                flash('Ошибка сохранения профиля: ' . $e->getMessage(),'error');
                if ($standalone) {
                    redirect('/users.php?tab=profile');
                }
            }
        }
    }

    // Перезагрузка профиля доставки после сохранения
    if (!$standalone) {
        $stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
        $stmt->execute([$user['id']]);
        $deliveryProfile = $stmt->fetch() ?: null;
    }
}

// Если автономный режим, загружаем заголовок
if ($standalone) {
    $pageTitle = 'Данные профиля';
    require __DIR__ . '/../includes/header.php';
    ?>
    <h2><?= e($pageTitle) ?></h2>
    <?php
}
?>

<section class="account-panel">
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save_profile">
    <label>Логин <input type="text" name="name" value="<?= e($user['name']) ?>" required></label>
    <label>Email <input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
    <label>Телефон <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>"></label>
    <label>Адрес доставки <textarea name="address"><?= e($user['address'] ?? '') ?></textarea></label>
    <div class="password-row">
      <label>Текущий пароль <input type="password" name="current_password" placeholder="Оставьте пустым, если не меняете пароль"></label>
      <label>Новый пароль <input type="password" name="password" placeholder="Оставьте пустым, чтобы не менять"></label>
    </div>
    <button class="btn btn-primary">Сохранить профиль</button>
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
    <button class="btn btn-primary" style="margin-top: 1rem;">Сохранить адрес доставки</button>
  </form>
</section>

<?php if ($standalone): ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?>