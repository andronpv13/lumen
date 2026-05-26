<?php
/**
 * Базовые шаблоны для повторяющихся HTML-блоков
 * Размещено в includes/partials/templates.php
 */

/**
 * Шаблон таблицы заказов (используется в admin/orders.php и users/orders.php)
 * @param array $orders Массив заказов
 * @param array $statusLabels Метки статусов
 * @param bool $showActions Показывать действия
 * @param bool $isAdmin Режим администратора
 */
function render_orders_table($orders, $statusLabels, $showActions = true, $isAdmin = false) {
    if (empty($orders)) {
        echo '<p class="empty">Заказов не найдено</p>';
        return;
    }
    ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>№</th>
          <th>Дата</th>
          <th>Клиент</th>
          <th>Сумма</th>
          <th>Статус</th>
          <th>Оплата</th>
          <?php if ($showActions): ?><th>Действия</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>№<?= $o['id'] ?></td>
          <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
          <td><?= e($o['user_login'] ?? $o['name'] ?? 'Гость') ?></td>
          <td><?= money($o['total']) ?></td>
          <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
          <td><?= e($o['payment_status'] === 'paid' ? 'Оплачен' : ($o['payment_status'] ?? 'Ожидает')) ?></td>
          <?php if ($showActions): ?>
          <td>
            <?php if ($isAdmin): ?>
              <a href="?action=orders&id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a>
            <?php else: ?>
              <a href="/users/orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php
}

/**
 * Шаблон карточки товара (используется в index.php, shop.php, category pages)
 * @param array $product Данные товара
 */
function render_product_card($product) {
    $img = product_image($product['image'] ?? '');
    ?>
    <div class="product-card">
      <a href="/product.php?id=<?= $product['id'] ?>">
        <img src="<?= $img ?>" alt="<?= e($product['name']) ?>" loading="lazy">
      </a>
      <div class="product-info">
        <h3><a href="/product.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a></h3>
        <?php if (!empty($product['category_name'])): ?>
          <p class="category"><?= e($product['category_name']) ?></p>
        <?php endif; ?>
        <p class="price"><?= money($product['price']) ?></p>
        <?php if (!empty($product['aroma'])): ?>
          <p class="aroma">Аромат: <?= e($product['aroma']) ?></p>
        <?php endif; ?>
        <form method="post" action="/cart.php">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id" value="<?= $product['id'] ?>">
          <input type="hidden" name="action" value="add">
          <button type="submit" class="btn btn-primary" <?= empty($product['stock']) ? 'disabled' : '' ?>>
            <?= empty($product['stock']) ? 'Нет в наличии' : 'В корзину' ?>
          </button>
        </form>
      </div>
    </div>
    <?php
}

/**
 * Шаблон формы профиля пользователя (используется в users/profile.php и admin.php account)
 * @param array $user Данные пользователя
 * @param string $action URL действия формы
 * @param bool $showRole Показывать поле роли (только для админа)
 */
function render_profile_form($user, $action = '/users/profile.php', $showRole = false) {
    ?>
    <form method="post" class="admin-form" action="<?= $action ?>">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save_profile">

      <label>Имя (логин)
        <input type="text" name="name" value="<?= e($user['name']) ?>" required>
      </label>

      <label>Email
        <input type="email" name="email" value="<?= e($user['email']) ?>" required>
      </label>

      <label>Телефон
        <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>">
      </label>

      <label>Адрес
        <textarea name="address" rows="3"><?= e($user['address'] ?? '') ?></textarea>
      </label>

      <?php if ($showRole && isset($user['role'])): ?>
      <label>Роль
        <select name="role">
          <?php foreach (['customer', 'moderator', 'admin'] as $r): ?>
            <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php endif; ?>

      <div class="password-row">
        <label>Текущий пароль
          <input type="password" name="current_password" placeholder="Для смены пароля">
        </label>
        <label>Новый пароль
          <input type="password" name="password" placeholder="Оставьте пустым, чтобы не менять">
        </label>
      </div>

      <button type="submit" class="btn btn-primary">Сохранить профиль</button>
    </form>
    <?php
}

/**
 * Шаблон сообщения flash (уведомления)
 * @return string HTML уведомлений или пустая строка
 */
function render_flash_messages() {
    $flash = flash();
    if (!$flash) return '';

    $type = $flash['type'] ?? 'info';
    $msg = $flash['msg'] ?? '';

    $types = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];

    $icon = $types[$type] ?? $types['info'];

    return "<div class=\"flash flash-{$type}\">{$icon} " . e($msg) . "</div>";
}

/**
 * Шаблон пагинации
 * @param int $total Всего записей
 * @param int $limit Записей на страницу
 * @param int $offset Текущее смещение
 * @param string $baseUrl Базовый URL
 * @return string HTML пагинации
 */
function render_pagination($total, $limit, $offset, $baseUrl = '') {
    if ($total <= $limit) return '';

    $pages = ceil($total / $limit);
    $currentPage = floor($offset / $limit) + 1;

    $html = '<div class="pagination">';

    if ($currentPage > 1) {
        $prevOffset = $offset - $limit;
        $html .= "<a href=\"{$baseUrl}?offset={$prevOffset}\" class=\"btn btn-ghost\">← Назад</a>";
    }

    $html .= "<span class=\"page-info\">Страница {$currentPage} из {$pages}</span>";

    if ($currentPage < $pages) {
        $nextOffset = $offset + $limit;
        $html .= "<a href=\"{$baseUrl}?offset={$nextOffset}\" class=\"btn btn-ghost\">Вперёд →</a>";
    }

    $html .= '</div>';
    return $html;
}

/**
 * Шаблон бокового меню администратора
 * @param string $currentAction Текущее действие
 * @param bool $isMod Режим модератора
 */
function render_admin_sidebar($currentAction = 'dashboard', $isMod = false) {
    ?>
    <aside class="admin-sidebar">
      <h3>Панель: <?= $isMod ? 'Модератор' : 'Админ' ?></h3>
      <nav>
        <a href="?action=dashboard" class="<?= $currentAction === 'dashboard' ? 'active' : '' ?>">📊 Дашборд</a>
        <a href="?action=account" class="<?= $currentAction === 'account' ? 'active' : '' ?>">👤 Мои данные</a>
        <a href="?action=my_orders" class="<?= $currentAction === 'my_orders' ? 'active' : '' ?>">🧾 Мои заказы</a>
        <a href="?action=products" class="<?= $currentAction === 'products' ? 'active' : '' ?>">🕯️ Товары</a>
        <?php if (!$isMod): ?>
          <a href="?action=categories" class="<?= $currentAction === 'categories' ? 'active' : '' ?>">📂 Категории</a>
        <?php endif; ?>
        <a href="?action=orders" class="<?= $currentAction === 'orders' ? 'active' : '' ?>">🛍️ Заказы</a>
        <a href="?action=reviews" class="<?= $currentAction === 'reviews' ? 'active' : '' ?>">⭐ Отзывы</a>
        <?php if (!$isMod): ?>
          <a href="?action=users" class="<?= $currentAction === 'users' ? 'active' : '' ?>">👥 Пользователи</a>
          <a href="?action=settings" class="<?= $currentAction === 'settings' ? 'active' : '' ?>">⚙️ Настройки</a>
        <?php endif; ?>
        <a href="/" class="<?= $currentAction === 'home' ? 'active' : '' ?>">🏠 На сайт</a>
      </nav>
    </aside>
    <?php
}

/**
 * Шаблон статистики для дашборда
 * @param array $stats Массив статистики
 */
function render_dashboard_stats($stats) {
    ?>
    <div class="stats-grid">
      <div class="stat">
        <h3><?= $stats['products'] ?? 0 ?></h3>
        <p>Товаров</p>
      </div>
      <div class="stat">
        <h3><?= $stats['orders'] ?? 0 ?></h3>
        <p>Заказов</p>
      </div>
      <div class="stat">
        <h3><?= money($stats['revenue'] ?? 0) ?></h3>
        <p>Выручка</p>
      </div>
      <div class="stat">
        <h3><?= $stats['users'] ?? 0 ?></h3>
        <p>Пользователей</p>
      </div>
      <div class="stat">
        <h3><?= $stats['pending_reviews'] ?? 0 ?></h3>
        <p>Отзывов на модерации</p>
      </div>
    </div>
    <?php
}

/**
 * Шаблон формы редактирования товара
 * @param array $product Данные товара
 * @param array $categories Список категорий
 * @param string $imageCurrent Текущее изображение
 */
function render_product_form($product, $categories, $imageCurrent = '') {
    $isNew = empty($product['id']);
    ?>
    <form method="post" enctype="multipart/form-data" class="admin-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="product_save">
      <input type="hidden" name="id" value="<?= $product['id'] ?? 0 ?>">
      <input type="hidden" name="image_current" value="<?= e($imageCurrent) ?>">

      <label>Название
        <input type="text" name="name" value="<?= e($product['name'] ?? '') ?>" required>
      </label>

      <label>Категория
        <select name="category_id">
          <option value="">—</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Цена
        <input type="number" step="0.01" name="price" value="<?= $product['price'] ?? 0 ?>" required>
      </label>

      <label>Остаток
        <input type="number" name="stock" value="<?= $product['stock'] ?? 0 ?>">
      </label>

      <label>Вес (г)
        <input type="number" name="weight" value="<?= $product['weight'] ?? 0 ?>">
      </label>

      <label>Описание
        <textarea name="description" rows="4"><?= e($product['description'] ?? '') ?></textarea>
      </label>

      <label>Фото
        <input type="file" name="image" accept="image/*">
        <?php if (!empty($imageCurrent)): ?>
          <br>
          <img src="<?= product_image($imageCurrent) ?>" style="max-width:120px;margin-top:8px">
        <?php endif; ?>
      </label>

      <label class="checkbox">
        <input type="checkbox" name="active" <?= ($product['active'] ?? 1) ? 'checked' : '' ?>>
        Активен
      </label>

      <button type="submit" class="btn btn-primary">Сохранить</button>
      <a href="?action=products" class="btn btn-ghost">Отмена</a>
    </form>
    <?php
}