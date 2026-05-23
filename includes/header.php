<?php
// includes/header.php
require_once __DIR__ . '/functions.php';
$user = current_user();
$shopName = setting('shop_name', 'Lumen');
$flash = flash();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? $shopName) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="/" class="logo">🕯️ <?= e($shopName) ?></a>
    <nav class="main-nav">
      <a href="/shop.php">Каталог</a>
      <a href="/cart.php" title="Корзина"> 🛒 <?php if(cart_count()): ?><span class="badge"><?= cart_count() ?></span><?php endif; ?></a>
      <?php if($user): ?>
        <?php if($user['role'] !== 'admin'): ?>
          <a href="/users.php" title="Аккаунт">Аккаунт</a>
          <a href="/orders.php" title="Заказы">Заказы</a>
        <?php endif; ?>
        <?php if($user['role']==='admin'): ?><a href="/admin.php" title="Админка"> 🛠️ </a><?php endif; ?>
        <?php if($user['role']==='moderator'): ?><a href="/moderator.php" title="Модерация"> 🛠️ </a><?php endif; ?>
        <span class="user-hello"><?= e($user['name']) ?></span>
        <a href="/logout.php" title="Выход"> 🚪 </a>
      <?php else: ?>
        <a href="/auth.php" title="Вход"> 🚪 </a>
      <?php endif; ?>
    </nav>
    <button class="burger" aria-label="Меню">☰</button>
  </div>
</header>

<?php if($flash): ?>
  <div class="container"><div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div></div>
<?php endif; ?>

<main class="container">