<?php
// admin/dashboard.php - Панель управления (дашборд)

/**
 * Получить метки статусов заказов (если не загружен из functions.php)
 */
if (!function_exists('get_order_status_labels')) {
    function get_order_status_labels() {
        return [
            'new' => 'Новый',
            'processing' => 'В обработке',
            'shipped' => 'Отправлен',
            'delivered' => 'Доставлен',
            'cancelled' => 'Отменён',
        ];
    }
}

/**
 * Отобразить дашборд администратора/модератора
 * @param array $user Данные текущего пользователя
 * @param bool $isMod Является ли модератором
 */
function render_admin_dashboard($user, $isMod = false) {
    $db = db();

    // Статистика
    $stats = [
        'products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'revenue' => $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),
        'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'pending_reviews' => $db->query("SELECT COUNT(*) FROM reviews WHERE approved=0")->fetchColumn(),
    ];

    // Последние заказы
    $recent = $db->query("SELECT o.*, u.name FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
    $statusLabels = get_order_status_labels();
?>
    <h1>Дашборд</h1>
    <div class="stats-grid">
      <div class="stat"><h3><?= $stats['products'] ?></h3><p>Товаров</p></div>
      <div class="stat"><h3><?= $stats['orders'] ?></h3><p>Заказов</p></div>
      <div class="stat"><h3><?= money($stats['revenue']) ?></h3><p>Выручка</p></div>
      <div class="stat"><h3><?= $stats['users'] ?></h3><p>Пользователей</p></div>
      <div class="stat"><h3><?= $stats['pending_reviews'] ?></h3><p>Отзывов на модерации</p></div>
    </div>

    <h2>Последние заказы</h2>
    <table class="admin-table">
      <thead><tr><th>№</th><th>Клиент</th><th>Сумма</th><th>Статус</th><th>Дата</th></tr></thead>
      <tbody>
      <?php foreach($recent as $o): ?>
        <tr>
          <td>#<?= $o['id'] ?></td>
          <td><?= e($o['name'] ?? 'Гость') ?></td>
          <td><?= money($o['total']) ?></td>
          <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
          <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
<?php
}