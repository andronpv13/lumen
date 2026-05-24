<?php
// admin.php
require_once __DIR__ . '/includes/functions.php';
require_role(['admin','moderator']);
$user = current_user();
$isMod = $user['role'] === 'moderator';

$action = $_GET['action'] ?? 'dashboard';

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
            if (!empty($_POST['password'])) $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $sql = $id ? "UPDATE users SET name=?,email=?,role=?,phone=?" . (!empty($_POST['password'])?",password=?" : "") . " WHERE id=?" : "";
            $params = [$data['name'], $data['email'], $data['role'], $data['phone']];
            if (!empty($_POST['password'])) $params[] = $data['password'];
            $params[] = $id;
            db()->prepare($sql)->execute($params);
        } else {
            db()->prepare("INSERT INTO users (name,email,role,phone,password) VALUES (?,?,?,?,?)")
                ->execute([$data['name'], $data['email'], $data['role'], $data['phone'], password_hash($_POST['password'] ?? '123456', PASSWORD_BCRYPT)]);
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
            if (!password_verify($current, $hash)) {
              flash('Текущий пароль неверный','error');
              redirect('?action=account');
            }
          }

          // All validation passed: update
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
      <a href="?action=orders" class="<?= $action==='orders'?'active':'' ?>">📦 Заказы</a>
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
    $stats = [
        'products' => db()->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'orders' => db()->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'revenue' => db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),
        'users' => db()->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'pending_reviews' => db()->query("SELECT COUNT(*) FROM reviews WHERE approved=0")->fetchColumn(),
    ];
    $recent = db()->query("SELECT o.*, u.name FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
?>
    <h1>Дашборд</h1>
    <div class="stats-grid">
      <div class="stat"><h3><?= $stats['products'] ?></h3><p>Товаров</p></div>
      <div class="stat"><h3><?= $stats['orders'] ?></h3><p>Заказов</p></div>
      <div class="stat"><h3><?= money($stats['revenue']) ?></h3><p>Выручка</p></div>
      <div class="stat"><h3><?= $stats['users'] ?></h3><p>Пользователей</p></div>
      <div class="stat"><h3><?= $stats['pending_reviews'] ?></h3><p>Отзывов на модерации</p></div>
    </div>
    <?php $statusLabels = ['new'=>'Новый','processing'=>'В обработке','paid'=>'Оплачен','shipped'=>'Отправлен','delivered'=>'Доставлен','cancelled'=>'Отменён']; ?>
    <h2>Последние заказы</h2>
    <table class="admin-table">
      <thead><tr><th>№</th><th>Клиент</th><th>Сумма</th><th>Статус</th><th>Дата</th></tr></thead>
      <tbody>
      <?php foreach($recent as $o): ?>
        <tr><td>#<?= $o['id'] ?></td><td><?= e($o['name'] ?? 'Гость') ?></td><td><?= money($o['total']) ?></td><td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td><td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>

<?php
// ===== PRODUCTS =====
elseif ($action === 'products'):
    $products = db()->query("SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC")->fetchAll();
    $categories = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $editId = (int)($_GET['id'] ?? 0);
    $edit = null;
    if ($editId) {
        $s = db()->prepare("SELECT * FROM products WHERE id=?");
        $s->execute([$editId]); $edit = $s->fetch();
    }
?>
    <h1>Товары</h1>
    <a href="?action=products&id=new" class="btn btn-primary">+ Добавить</a>
    <?php if($edit || isset($_GET['id'])): ?>
      <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="product_save">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
        <input type="hidden" name="image_current" value="<?= e($edit['image'] ?? '') ?>">
        <label>Название <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></label>
        <label>Категория
          <select name="category_id">
            <option value="">—</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit['category_id']??0)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Цена <input type="number" step="0.01" name="price" value="<?= $edit['price'] ?? 0 ?>" required></label>
        <label>Остаток <input type="number" name="stock" value="<?= $edit['stock'] ?? 0 ?>"></label>
        <label>Аромат <input type="text" name="aroma" value="<?= e($edit['aroma'] ?? '') ?>"></label>
        <label>Вес (г) <input type="number" name="weight" value="<?= $edit['weight'] ?? 0 ?>"></label>
        <label>Описание <textarea name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea></label>
        <label>Изображение <input type="file" name="image" accept="image/*">
          <?php if(!empty($edit['image'])): ?><br><img src="<?= product_image($edit['image']) ?>" style="max-width:120px;margin-top:8px"><?php endif; ?>
        </label>
        <label class="checkbox"><input type="checkbox" name="active" <?= ($edit['active'] ?? 1)?'checked':'' ?>> Активен</label>
        <button class="btn btn-primary">Сохранить</button>
        <a href="?action=products" class="btn btn-ghost">Отмена</a>
      </form>
    <?php endif; ?>

    <table class="admin-table">
      <thead><tr><th>ИЗО</th><th>Название</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach($products as $p): ?>
        <tr>
          <td><img src="<?= product_image($p['image']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px"></td>
          <td><?= e($p['name']) ?></td>
          <td><?= e($p['cat']) ?></td>
          <td><?= money($p['price']) ?></td>
          <td><?= $p['stock'] ?></td>
          <td><?= $p['active']?'✅':'⏸️' ?></td>
          <td>
            <a href="?action=products&id=<?= $p['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
            <?php if(!$isMod): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="product_delete">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

<?php
// ===== CATEGORIES =====
elseif ($action === 'categories' && !$isMod):
    $cats = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $editId = (int)($_GET['id'] ?? 0);
    $edit = $editId ? db()->prepare("SELECT * FROM categories WHERE id=?")->execute([$editId]) && ($edit = db()->prepare("SELECT * FROM categories WHERE id=?")) ? (function()use($editId){$s=db()->prepare("SELECT * FROM categories WHERE id=?");$s->execute([$editId]);return $s->fetch();})() : null : null;
?>
    <h1>Категории</h1>
    <form method="post" class="admin-form inline">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="category_save">
      <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
      <input type="text" name="name" placeholder="Название" value="<?= e($edit['name'] ?? '') ?>" required>
      <input type="text" name="slug" placeholder="slug" value="<?= e($edit['slug'] ?? '') ?>" required>
      <button class="btn btn-primary">Сохранить</button>
    </form>
    <table class="admin-table">
      <thead><tr><th>ID</th><th>Название</th><th>Slug</th><th></th></tr></thead>
      <tbody>
      <?php foreach($cats as $c): ?>
        <tr>
          <td><?= $c['id'] ?></td><td><?= e($c['name']) ?></td><td><?= e($c['slug']) ?></td>
          <td>
            <a href="?action=categories&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="category_delete">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

<?php
// ===== ORDERS =====
elseif ($action === 'orders'):
    $statusLabels = [
        'new' => 'Новый',
        'processing' => 'В обработке',
        'paid' => 'Ожидает оплату',
        'shipped' => 'Отправлен',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменён',
    ];
    $paymentStatusLabels = [
        'pending' => 'Обработка',
        'paid' => 'Оплачен',
        'failed' => 'Отменён',
    ];
    $orders = db()->query("SELECT o.*, u.name AS uname FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC")->fetchAll();
    $viewId = (int)($_GET['id'] ?? 0);
    $view = $viewItems = null;
    if ($viewId) {
        $s = db()->prepare("SELECT o.*, u.name AS uname FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
        $s->execute([$viewId]); $view = $s->fetch();
        $s = db()->prepare("SELECT * FROM order_items WHERE order_id=?");
        $s->execute([$viewId]); $viewItems = $s->fetchAll();
    }
?>
    <h1>Заказы</h1>
    <?php if($view): ?>
      <a href="?action=orders">← Все заказы</a>
      <h2>Заказ #<?= $view['id'] ?></h2>
      <p><strong>Клиент:</strong> <?= e($view['uname']) ?></p>
      <p><strong>Получатель:</strong> <?= e($view['shipping_name']) ?>, <?= e($view['shipping_phone']) ?></p>
      <p><strong>Адрес:</strong> <?= e($view['shipping_address']) ?></p>
      <?php if($view['notes']): ?><p><strong>Примечания:</strong> <?= e($view['notes']) ?></p><?php endif; ?>
      <form method="post" class="admin-form inline">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="order_update">
        <input type="hidden" name="id" value="<?= $view['id'] ?>">
        <select name="status">
          <?php foreach($statusLabels as $value => $label): ?>
            <option value="<?= $value ?>" <?= $view['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <select name="payment_status">
          <?php foreach($paymentStatusLabels as $value => $label): ?>
            <option value="<?= $value ?>" <?= $view['payment_status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Обновить</button>
      </form>
      <table class="admin-table">
        <thead><tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr></thead>
        <tbody>
        <?php foreach($viewItems as $it): ?>
          <tr><td><?= e($it['name']) ?></td><td><?= money($it['price']) ?></td><td><?= $it['quantity'] ?></td><td><?= money($it['price']*$it['quantity']) ?></td></tr>
        <?php endforeach; ?>
        <tfoot><tr><td colspan="3"><strong>Итого</strong></td><td><strong><?= money($view['total']) ?></strong></td></tr></tfoot>
        </tbody>
      </table>
    <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>№</th><th>Клиент</th><th>Сумма</th><th>Статус</th><th>Оплата</th><th>Дата</th><th></th></tr></thead>
        <tbody>
        <?php foreach($orders as $o): ?>
          <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= e($o['uname'] ?? 'Гость') ?></td>
            <td><?= money($o['total']) ?></td>
            <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
            <td><?= e($paymentStatusLabels[$o['payment_status']] ?? $o['payment_status']) ?></td>
            <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
            <td>
              <a href="?action=orders&id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a>
              <?php if(!$isMod): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="order_delete">
                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                <button class="btn btn-sm btn-ghost">🗑️</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
<?php
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
      <label>Наш адрес <textarea name="address"><?= e($profile['address'] ?? '') ?></textarea></label>
      <div class="password-row">
        <label>Текущий пароль <input type="password" name="current_password" placeholder="Оставьте пустым, если не меняете пароль"></label>
        <label>Новый пароль <input type="password" name="password" placeholder="Оставьте пустым, чтобы не менять"></label>
      </div>
      <button class="btn btn-primary">Сохранить профиль</button>
    </form>
<?php
// ===== MY ORDERS =====
elseif ($action === 'my_orders'):
    $myOrdersStmt = db()->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
    $myOrdersStmt->execute([$user['id']]);
    $myOrders = $myOrdersStmt->fetchAll();
?>
    <?php $statusLabels = ['new'=>'Новый','processing'=>'В обработке','paid'=>'Оплачен','shipped'=>'Отправлен','delivered'=>'Доставлен','cancelled'=>'Отменён']; ?>
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
<?php
// ===== REVIEWS =====
elseif ($action === 'reviews'):
    $reviews = db()->query("SELECT r.*, u.name AS uname, p.name AS pname FROM reviews r LEFT JOIN users u ON r.user_id=u.id LEFT JOIN products p ON r.product_id=p.id ORDER BY r.approved, r.created_at DESC")->fetchAll();
?>
    <h1>Отзывы</h1>
    <table class="admin-table">
      <thead><tr><th>Товар</th><th>Автор</th><th>Оценка</th><th>Текст</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach($reviews as $r): ?>
        <tr>
          <td><?= e($r['pname']) ?></td>
          <td><?= e($r['uname']) ?></td>
          <td><?= str_repeat('★', $r['rating']) ?></td>
          <td><?= e(mb_substr($r['comment'], 0, 80)) ?></td>
          <td><?= $r['approved']?'✅':'⏳' ?></td>
          <td>
            <?php if(!$r['approved']): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="review_approve">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-primary">Одобрить</button>
            </form>
            <?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="review_delete">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

<?php
// ===== USERS =====
elseif ($action === 'users' && !$isMod):
    $users = db()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
    $editId = (int)($_GET['id'] ?? 0);
    $edit = null;
    if ($editId) {
        $s = db()->prepare("SELECT * FROM users WHERE id=?");
        $s->execute([$editId]); $edit = $s->fetch();
    }
?>
    <h1>Пользователи</h1>
    <?php if($edit || isset($_GET['id'])): ?>
      <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="user_save">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
        <label>Имя <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required></label>
        <label>Телефон <input type="text" name="phone" value="<?= e($edit['phone'] ?? '') ?>"></label>
        <label>Роль
          <select name="role">
            <?php foreach(['customer','moderator','admin'] as $r): ?>
              <option value="<?= $r ?>" <?= ($edit['role']??'customer')===$r?'selected':'' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Пароль <?= $edit?'(оставьте пустым, чтобы не менять)':'<input type="password" name="password" required>' ?>
          <?php if($edit): ?><input type="password" name="password"><?php endif; ?>
        </label>
        <button class="btn btn-primary">Сохранить</button>
        <a href="?action=users" class="btn btn-ghost">Отмена</a>
      </form>
    <?php endif; ?>
    <table class="admin-table">
      <thead><tr><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th>Дата</th><th></th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['role']) ?></td>
          <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
          <td>
            <a href="?action=users&id=<?= $u['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
            <?php if($u['id']!=$user['id']): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="user_delete">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

<?php
// ===== SETTINGS =====
elseif ($action === 'settings' && !$isMod):
?>
    <h1>Настройки магазина</h1>
    <form method="post" class="admin-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="settings_save">
      <label>Название магазина <input type="text" name="shop_name" value="<?= e(setting('shop_name')) ?>"></label>
      <label>Телефон магазина <input type="text" name="shop_phone" value="<?= e(setting('shop_phone')) ?>"></label>
      <label>Email магазина <input type="email" name="shop_email" value="<?= e(setting('shop_email')) ?>"></label>
      <label>Адрес магазина <input type="text" name="shop_address" value="<?= e(setting('shop_address')) ?>"></label>
      <label>Стоимость доставки до транспортной <input type="number" step="0.01" name="delivery_price" value="<?= e(setting('delivery_price')) ?>"></label>
      <label>Валюта <input type="text" name="currency" value="<?= e(setting('currency')) ?>"></label>
      <button class="btn btn-primary">Сохранить</button>
    </form>
<?php else: ?>
    <p>Раздел недоступен</p>
<?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>