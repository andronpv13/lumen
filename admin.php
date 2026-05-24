<?php
// admin.php - Модернизированная панель управления с модульной архитектурой
require_once __DIR__ . '/includes/functions.php';
require_role(['admin','moderator']);
$user = current_user();
$isMod = $user['role'] === 'moderator';

// Подключение модулей из папки admin/
require_once __DIR__ . '/admin/dashboard.php';
require_once __DIR__ . '/admin/products.php';
require_once __DIR__ . '/admin/orders.php';
require_once __DIR__ . '/admin/categories.php';
require_once __DIR__ . '/admin/users.php';
require_once __DIR__ . '/admin/reviews.php';
require_once __DIR__ . '/admin/settings.php';

$action = $_GET['action'] ?? 'dashboard';
$viewId = isset($_GET['id']) ? $_GET['id'] : null;

// POST-обработчики
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';

    // Товары
    if ($act === 'product_save') {
        $id = (int)$_POST['id'];
        $data = [
            'category_id' => $_POST['category_id'] ?: null,
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description']),
            'price' => (float)$_POST['price'],
            'stock' => (int)$_POST['stock'],
            'aroma' => trim($_POST['aroma']),
            'weight' => (int)$_POST['weight'],
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        $image = $_POST['image_current'] ?? '';
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error']===0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $fname = uniqid('p_') . '.' . $ext;
            $cfg = require __DIR__ . '/config.php';
            if (!is_dir($cfg['upload_dir'])) mkdir($cfg['upload_dir'], 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $cfg['upload_dir'].$fname)) $image = $fname;
        }
        $data['image'] = $image;
        if ($id) {
            $sql = "UPDATE products SET category_id=?, name=?, description=?, price=?, stock=?, aroma=?, weight=?, active=?, image=? WHERE id=?";
            db()->prepare($sql)->execute([...array_values($data), $id]);
        } else {
            $sql = "INSERT INTO products (category_id,name,description,price,stock,aroma,weight,active,image) VALUES (?,?,?,?,?,?,?,?,?)";
            db()->prepare($sql)->execute(array_values($data));
        }
        flash('Товар сохранён','success');
        redirect('?action=products');
    }
    if ($act === 'product_delete' && !$isMod) {
        db()->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Удалено','success'); redirect('?action=products');
    }

    // Категории (только админ)
    if ($act === 'category_save' && !$isMod) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        if ($id) db()->prepare("UPDATE categories SET name=?, slug=? WHERE id=?")->execute([$name,$slug,$id]);
        else db()->prepare("INSERT INTO categories (name,slug) VALUES (?,?)")->execute([$name,$slug]);
        flash('Категория сохранена','success'); redirect('?action=categories');
    }
    if ($act === 'category_delete' && !$isMod) {
        db()->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Удалено','success'); redirect('?action=categories');
    }

    // Заказы
    if ($act === 'order_update') {
        $validStatuses = ['new','processing','paid','shipped','delivered','cancelled'];
        $validPaymentStatuses = ['pending','paid','failed'];
        $status = in_array($_POST['status'] ?? '', $validStatuses, true) ? $_POST['status'] : 'new';
        $paymentStatus = in_array($_POST['payment_status'] ?? '', $validPaymentStatuses, true) ? $_POST['payment_status'] : 'pending';
        db()->prepare("UPDATE orders SET status=?, payment_status=? WHERE id=?")
            ->execute([$status, $paymentStatus, (int)$_POST['id']]);
        flash('Заказ обновлён','success'); redirect('?action=orders');
    }
    if ($act === 'order_delete' && !$isMod) {
        db()->prepare("DELETE FROM orders WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Удалено','success'); redirect('?action=orders');
    }

    // Пользователи (только админ)
    if ($act === 'user_save' && !$isMod) {
        $id = (int)$_POST['id'];
        $data = ['name'=>$_POST['name'], 'email'=>$_POST['email'], 'role'=>$_POST['role'], 'phone'=>$_POST['phone']];
        if ($id) {
            if (!empty($_POST['password'])) $data['password'] = hash_password($_POST['password']);
            $sql = $id ? "UPDATE users SET name=?,email=?,role=?,phone=?" . (!empty($_POST['password'])?",password=?" : "") . " WHERE id=?" : "";
            $params = [$data['name'], $data['email'], $data['role'], $data['phone']];
            if (!empty($_POST['password'])) $params[] = $data['password'];
            $params[] = $id;
            db()->prepare($sql)->execute($params);
        } else {
            db()->prepare("INSERT INTO users (name,email,role,phone,password) VALUES (?,?,?,?,?)")
                ->execute([$data['name'], $data['email'], $data['role'], $data['phone'], hash_password($_POST['password'] ?? '123456')]);
        }
        flash('Сохранено','success'); redirect('?action=users');
    }
    if ($act === 'save_profile') {
        $name = trim($_POST['name'] ?? '');
          $email = trim($_POST['email'] ?? '');
          $phone = trim($_POST['phone'] ?? '');
          $address = trim($_POST['address'] ?? '');
          $password = $_POST['password'] ?? '';
          $current = $_POST['current_password'] ?? '';

          // basic validation: name (login) and email
          if ($name === '' || $email === '') {
            flash('Логин и email обязательны','error');
            redirect('?action=account');
          }
          // login (name) cannot contain spaces or tabs
          if (preg_match('/[\s\t]/', $name)) {
            flash('Логин не может содержать пробелы или табы','error');
            redirect('?action=account');
          }
          // email format
          if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Неверный формат email','error');
            redirect('?action=account');
          }

          // If user wants to change password - validate current and new
          if ($password !== '') {
            // new password restrictions
            if (strlen($password) < 6) {
              flash('Новый пароль должен быть не менее 6 символов','error');
              redirect('?action=account');
            }
            if (preg_match('/[\s\t]/', $password)) {
              flash('Новый пароль не может содержать пробелы или табы','error');
              redirect('?action=account');
            }
            // current password required
            if ($current === '') {
              flash('Для смены пароля введите текущий пароль','error');
              redirect('?action=account');
            }
            if (preg_match('/[\s\t]/', $current)) {
              flash('Текущий пароль не может содержать пробелы или табы','error');
              redirect('?action=account');
            }
            // verify current password from DB
            $stmt = db()->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            $hash = $row['password'] ?? '';
            if (!verify_password($current, $hash)) {
              flash('Текущий пароль неверный','error');
              redirect('?action=account');
            }
          }

          // All validation passed: update
          $params = [$name, $email, $phone, $address];
          $sql = 'UPDATE users SET name=?, email=?, phone=?, address=?';
          if ($password !== '') {
            $sql .= ', password=?';
            $params[] = hash_password($password);
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
          flash('Профиль сохранён','success');
          redirect('?action=account');
    }
    if ($act === 'user_delete' && !$isMod) {
        if ((int)$_POST['id'] !== $user['id']) {
            db()->prepare("DELETE FROM users WHERE id=?")->execute([(int)$_POST['id']]);
            flash('Удалено','success');
        }
        redirect('?action=users');
    }

    // Отзывы
    if ($act === 'review_approve') {
        db()->prepare("UPDATE reviews SET approved=1 WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Одобрено','success'); redirect('?action=reviews');
    }
    if ($act === 'review_delete') {
        db()->prepare("DELETE FROM reviews WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Удалено','success'); redirect('?action=reviews');
    }

    // Настройки (только админ)
    if ($act === 'settings_save' && !$isMod) {
        $keys = ['shop_name','shop_phone','shop_email','shop_address','currency','delivery_price'];
        $stmt = db()->prepare("INSERT INTO settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
        foreach ($keys as $k) $stmt->execute([$k, $_POST[$k] ?? '']);
        flash('Настройки сохранены','success'); redirect('?action=settings');
    }
}

// internal admin views for admin-only sidebar actions
$pageTitle = 'Панель управления';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <h3>Панель: <?= $isMod?'Модератор':'Админ' ?></h3>
    <nav>
      <a href="?action=dashboard" class="<?= $action==='dashboard'?'active':'' ?>">📊 Дашборд</a>
      <a href="?action=account" class="<?= $action==='account'?'active':'' ?>">👤 Мои данные</a>
        <a href="?action=my_orders" class="<?= $action==='my_orders'?'active':'' ?>">🧾 Мои заказы</a>
      <a href="?action=products" class="<?= $action==='products'?'active':'' ?>">🕯️ Товары</a>
      <?php if(!$isMod): ?><a href="?action=categories" class="<?= $action==='categories'?'active':'' ?>">📂 Категории</a><?php endif; ?>
      <a href="?action=orders" class="<?= $action==='orders'?'active':'' ?>">🛍️ Заказы</a>
      <a href="?action=reviews" class="<?= $action==='reviews'?'active':'' ?>">⭐ Отзывы</a>
      <?php if(!$isMod): ?>
        <a href="?action=users" class="<?= $action==='users'?'active':'' ?>">👥 Пользователи</a>
        <a href="?action=settings" class="<?= $action==='settings'?'active':'' ?>">⚙️ Настройки</a>
      <?php endif; ?>
      <a href="/">🏠 На сайт</a>
    </nav>
  </aside>

  <section class="admin-main">
<?php
// ===== DASHBOARD =====
if ($action === 'dashboard'):
    render_admin_dashboard($user, $isMod);

// ===== PRODUCTS =====
elseif ($action === 'products'):
    render_products_page($isMod, $_GET['id'] ?? null);

// ===== CATEGORIES =====
elseif ($action === 'categories' && !$isMod):
    render_categories_page($isMod, $_GET['id'] ?? null);

// ===== ORDERS =====
elseif ($action === 'orders'):
    render_orders_page($isMod, isset($_GET['id']) ? (int)$_GET['id'] : null);

// ===== ACCOUNT =====
elseif ($action === 'account'):
    $profileStmt = db()->prepare("SELECT * FROM users WHERE id=?");
    $profileStmt->execute([$user['id']]);
    $profile = $profileStmt->fetch();
?>
    <h1>Мои данные</h1>
    <form method="post" class="admin-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save_profile">
      <label>Логин <input type="text" name="name" value="<?= e($profile['name']) ?>" required></label>
      <label>Email <input type="email" name="email" value="<?= e($profile['email']) ?>" required></label>
      <label>Телефон <input type="tel" name="phone" value="<?= e($profile['phone'] ?? '') ?>"></label>
      <div class="password-row">
        <label>Текущий пароль <input type="password" name="current_password" placeholder="Оставьте пустым, если не меняете пароль"></label>
        <label>Новый пароль <input type="password" name="password" placeholder="Оставьте пустым, чтобы не менять"></label>
      </div>
      <button class="btn btn-primary">Сохранить профиль</button>
    </form>

<?php
// ===== MY ORDERS =====
elseif ($action === 'my_orders'):
    $statusLabels = get_order_status_labels();
    $myOrders = get_orders_by_user($user['id']);
?>
    <h1>Мои заказы</h1>
    <?php if (!$myOrders): ?>
      <p class="empty">У вас пока нет заказов.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>№</th><th>Дата</th><th>Сумма</th><th>Статус</th><th>Оплата</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($myOrders as $o): ?>
          <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
            <td><?= money($o['total']) ?></td>
            <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
            <td><?= $o['payment_status'] === 'paid' ? 'Оплачен' : 'Ожидает' ?></td>
            <td>
              <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="?action=orders&id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a>
              <?php else: ?>
                <a href="/orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

// ===== REVIEWS =====
elseif ($action === 'reviews'):
    render_reviews_page($isMod);

// ===== USERS =====
elseif ($action === 'users' && !$isMod):
    render_users_page($isMod, $_GET['id'] ?? null);

// ===== SETTINGS =====
elseif ($action === 'settings' && !$isMod):
    render_settings_page($isMod);

<?php else: ?>
    <p>Раздел недоступен</p>
<?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>