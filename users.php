<?php
// users.php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
$tab = $_GET['tab'] ?? 'profile';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'save_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$name || !$email) {
            flash('Заполните имя и email','error');
        } elseif ($password !== '' && strlen($password) < 6) {
            flash('Пароль должен быть не менее 6 символов','error');
        } else {
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
            } catch (PDOException $e) {
                flash('Ошибка сохранения профиля: ' . $e->getMessage(),'error');
            }
        }
        redirect('/users.php?tab=profile');
    }

    if ($action === 'save_review') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        $stmt = db()->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.user_id=? AND oi.product_id=? AND o.status!='cancelled' LIMIT 1");
        $stmt->execute([$user['id'], $productId]);
        if (!$stmt->fetch()) {
            flash('Вы можете оставлять отзывы только на купленные товары','error');
            redirect('/users.php?tab=reviews');
        }

        $stmt = db()->prepare("SELECT id FROM reviews WHERE user_id=? AND product_id=?");
        $stmt->execute([$user['id'], $productId]);
        if ($stmt->fetch()) {
            flash('Вы уже оставили отзыв на этот товар','error');
            redirect('/users.php?tab=reviews');
        }

        if ($rating < 1 || $rating > 5 || $comment === '') {
            flash('Оценка и комментарий обязательны','error');
            redirect('/users.php?tab=reviews');
        }

        db()->prepare("INSERT INTO reviews (product_id,user_id,rating,comment) VALUES (?,?,?,?)")
            ->execute([$productId, $user['id'], $rating, $comment]);
        flash('Спасибо! Отзыв отправлен на модерацию','success');
        redirect('/users.php?tab=reviews');
    }
}

$orderStmt = db()->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$orderStmt->execute([$user['id']]);
$orders = $orderStmt->fetchAll();

$itemStmt = db()->prepare(
    "SELECT oi.product_id, oi.name, oi.price, oi.quantity, o.created_at
     FROM order_items oi
     JOIN orders o ON o.id=oi.order_id
     WHERE o.user_id=? AND o.status!='cancelled'
     ORDER BY o.created_at DESC"
);
$itemStmt->execute([$user['id']]);
$orderedItems = [];
foreach ($itemStmt->fetchAll() as $row) {
    if (!isset($orderedItems[$row['product_id']])) {
        $orderedItems[$row['product_id']] = $row;
    }
}

$reviewedStmt = db()->prepare("SELECT product_id FROM reviews WHERE user_id=?");
$reviewedStmt->execute([$user['id']]);
$reviewedIds = array_column($reviewedStmt->fetchAll(), 'product_id');

$reviewedListStmt = db()->prepare(
    "SELECT r.*, p.name AS product_name FROM reviews r JOIN products p ON p.id=r.product_id WHERE r.user_id=? ORDER BY r.created_at DESC"
);
$reviewedListStmt->execute([$user['id']]);
$userReviews = $reviewedListStmt->fetchAll();

$statusLabels = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'paid' => 'Оплачен',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменён',
];

$pageTitle = 'Мой аккаунт';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <h3>Мой аккаунт</h3>
    <nav>
      <a href="/users.php?tab=profile" class="<?= $tab==='profile'?'active':'' ?>">Профиль</a>
      <a href="/users.php?tab=orders" class="<?= $tab==='orders'?'active':'' ?>">Заказы</a>
      <a href="/users.php?tab=reviews" class="<?= $tab==='reviews'?'active':'' ?>">Отзывы</a>
    </nav>
  </aside>
  <section class="admin-main">
    <h1>Мой аккаунт</h1>

    <?php if ($tab === 'profile'): ?>
  <section class="account-panel">
    <h2>Данные профиля</h2>
    <form method="post" class="admin-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save_profile">
      <label>Имя <input type="text" name="name" value="<?= e($user['name']) ?>" required></label>
      <label>Email <input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
      <label>Телефон <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>"></label>
      <label>Адрес доставки <textarea name="address"><?= e($user['address'] ?? '') ?></textarea></label>
      <label>Новый пароль <input type="password" name="password" placeholder="Оставьте пустым, чтобы не менять"></label>
      <button class="btn btn-primary">Сохранить профиль</button>
    </form>
  </section>
<?php elseif ($tab === 'orders'): ?>
  <section class="account-panel">
    <h2>Мои заказы</h2>
    <?php if (!$orders): ?>
      <p class="empty">У вас ещё нет заказов. <a href="/">Перейти в каталог</a></p>
    <?php else: ?>
      <table class="cart-table">
        <thead><tr><th>№</th><th>Дата</th><th>Сумма</th><th>Статус</th><th>Оплата</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
            <td><?= money($o['total']) ?></td>
            <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
            <td><?= $o['payment_status'] === 'paid' ? 'Оплачен' : 'Ожидает' ?></td>
            <td><a href="/orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
<?php elseif ($tab === 'reviews'): ?>
  <section class="account-panel">
    <h2>Отзывы на купленные товары</h2>
    <?php if (!$orderedItems): ?>
      <p class="empty">Нет купленных товаров для отзыва. Оформите заказ, чтобы оставить отзыв.</p>
    <?php else: ?>
      <?php foreach ($orderedItems as $item): ?>
        <div class="review-card">
          <div class="review-card__info">
            <strong><?= e($item['name']) ?></strong>
            <span>Цена: <?= money($item['price']) ?></span>
          </div>
          <?php if (in_array($item['product_id'], $reviewedIds, true)): ?>
            <p class="muted">Отзыв уже оставлен.</p>
          <?php else: ?>
            <form method="post" class="review-form">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="save_review">
              <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
              <label>Оценка
                <select name="rating" required>
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>"><?= $i ?> ⭐</option>
                  <?php endfor; ?>
                </select>
              </label>
              <label>Комментарий
                <textarea name="comment" rows="3" required></textarea>
              </label>
              <button class="btn btn-primary btn-sm">Оставить отзыв</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($userReviews): ?>
      <h3>Мои отзывы</h3>
      <ul class="reviews-list">
        <?php foreach ($userReviews as $review): ?>
          <li>
            <strong><?= e($review['product_name']) ?></strong>
            <span>Оценка: <?= $review['rating'] ?> ⭐</span>
            <p><?= nl2br(e($review['comment'])) ?></p>
            <small>Статус: <?= $review['approved'] ? 'Одобрен' : 'На модерации' ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
<?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
