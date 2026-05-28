<?php
// modules/users.php - Личный кабинет пользователя

// AJAX-проверка текущего пароля (должна быть ДО подключения config.php и любых других операций)
if (isset($_POST['check_password'])) {
    // Начинаем сессию для доступа к данным пользователя
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    // Проверка авторизации
    if (!isset($_SESSION['user'])) {
        echo json_encode(['ok' => false, 'valid' => false, 'message' => 'Пользователь не авторизован']);
        exit;
    }

    $user = $_SESSION['user'];
    $currentPassword = $_POST['check_password'] ?? '';

    // Проверка на наличие пробелов
    if (preg_match('/\s/', $currentPassword)) {
        echo json_encode(['ok' => false, 'valid' => false, 'message' => 'Пробелы запрещены']);
        exit;
    }

    if ($currentPassword === '') {
        echo json_encode(['ok' => false, 'valid' => false, 'message' => 'Введите текущий пароль']);
        exit;
    }

    // Получаем актуальный хеш пароля из БД (в сессии пароль не хранится)
    try {
        require_once __DIR__ . '/../includes/db.php';
        $stmt = db()->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$user['id']]);
        $dbUser = $stmt->fetch();

        if ($dbUser && password_verify($currentPassword, $dbUser['password'])) {
            echo json_encode(['ok' => true, 'valid' => true, 'message' => 'Пароль подтверждён']);
        } else {
            echo json_encode(['ok' => false, 'valid' => false, 'message' => 'Неверный текущий пароль']);
        }
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'valid' => false, 'message' => 'Ошибка проверки пароля']);
    }
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: /?route=auth');
    exit;
}

$user = current_user();

// Определение активной вкладки
$activeTab = $_GET['tab'] ?? 'profile';

// Заголовок страницы в зависимости от вкладки
$pageTitle = match($activeTab) {
    'orders' => 'Мои заказы',
    'reviews' => 'Отзывы на купленные товары',
    'profile' => 'Данные профиля',
    default => 'Данные профиля'
};

?>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <h3>Личный кабинет</h3>
    <nav>
      <a href="/?route=users&tab=profile" class="<?= $activeTab==='profile'?'active':'' ?>">👤 Профиль</a>
      <a href="/?route=users&tab=orders" class="<?= $activeTab==='orders'?'active':'' ?>">🧾 Заказы</a>
      <a href="/?route=users&tab=reviews" class="<?= $activeTab==='reviews'?'active':'' ?>">⭐ Отзывы</a>
      <a href="/">🏠 На сайт</a>
    </nav>
  </aside>

  <section class="admin-main">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

    <!-- Содержимое вкладки -->
    <?php if ($activeTab === 'profile'): ?>
        <?php
        $standalone = false;
        include __DIR__ . '/../users/profile.php';
        ?>
    <?php elseif ($activeTab === 'orders'): ?>
        <?php
        $standalone = false;
        include __DIR__ . '/../users/orders.php';
        ?>
    <?php elseif ($activeTab === 'reviews'): ?>
        <?php
        $standalone = false;
        include __DIR__ . '/../users/reviews.php';
        ?>
    <?php endif; ?>
  </section>
</div>