<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: auth.php');
    exit;
}

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

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <div class="container">
        <!-- Динамический заголовок страницы -->
        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>

        <!-- Навигация по вкладкам -->
        <div class="tabs">
            <a href="?tab=profile" class="<?php echo $activeTab === 'profile' ? 'active' : ''; ?>">Профиль</a>
            <a href="?tab=orders" class="<?php echo $activeTab === 'orders' ? 'active' : ''; ?>">Заказы</a>
            <a href="?tab=reviews" class="<?php echo $activeTab === 'reviews' ? 'active' : ''; ?>">Отзывы</a>
        </div>

        <!-- Содержимое вкладки -->
        <div class="tab-content">
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
        </div>
    </div>
</body>
</html>