<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: auth.php');
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

require __DIR__ . '/includes/header.php';
?>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <h3>Личный кабинет</h3>
    <nav>
      <a href="?tab=profile" class="<?= $activeTab==='profile'?'active':'' ?>">👤 Профиль</a>
      <a href="?tab=orders" class="<?= $activeTab==='orders'?'active':'' ?>">🧾 Заказы</a>
      <a href="?tab=reviews" class="<?= $activeTab==='reviews'?'active':'' ?>">⭐ Отзывы</a>
      <a href="/">🏠 На сайт</a>
    </nav>
  </aside>

  <section class="admin-main">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

    <!-- Содержимое вкладки -->
    <?php if ($activeTab === 'profile'): ?>
        <?php
        $standalone = false;
        include __DIR__ . '/users/profile.php';
        ?>
    <?php elseif ($activeTab === 'orders'): ?>
        <?php
        $standalone = false;
        include __DIR__ . '/users/orders.php';
        ?>
    <?php elseif ($activeTab === 'reviews'): ?>
        <?php
        $standalone = false;
        include __DIR__ . '/users/reviews.php';
        ?>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>