<?php
// admin/orders.php - Управление заказами
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repositories/OrderRepository.php';

/**
 * Отобразить страницу управления заказами
 * @param bool $isMod Является ли модератором
 * @param int|null $viewId ID заказа для просмотра деталей
 */
function render_orders_page($isMod = false, $viewId = null) {
    $statusLabels = get_order_status_labels();
    $paymentStatusLabels = [
        'pending' => 'Обработка',
        'paid' => 'Оплачен',
        'failed' => 'Отменён',
    ];

    if ($viewId) {
        $view = get_order_by_id($viewId);
        $viewItems = get_order_items($viewId);

        if (!$view) {
            echo '<p>Заказ не найден</p>';
            return;
        }
?>
      <a href="?action=orders" class="btn btn-ghost">← Все заказы</a>
      <h2>Заказ #<?= $view['id'] ?></h2>
      <p><strong>Клиент:</strong> <?= e($view['user_name'] ?? 'Гость') ?></p>
      <p><strong>Получатель:</strong> <?= e($view['shipping_name']) ?>, <?= e($view['shipping_phone']) ?></p>
      <p><strong>Адрес:</strong> <?= e($view['shipping_address']) ?></p>
      <?php if($view['notes']): ?><p><strong>Примечания:</strong> <?= e($view['notes']) ?></p><?php endif; ?>

      <form method="post" class="admin-form inline">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="order_update">
        <input type="hidden" name="id" value="<?= $view['id'] ?>">

        <label>Статус:
          <select name="status">
            <?php foreach($statusLabels as $value => $label): ?>
              <option value="<?= $value ?>" <?= $view['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Оплата:
          <select name="payment_status">
            <?php foreach($paymentStatusLabels as $value => $label): ?>
              <option value="<?= $value ?>" <?= $view['payment_status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <button class="btn btn-primary">Обновить</button>
      </form>

      <table class="admin-table">
        <thead><tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr></thead>
        <tbody>
        <?php foreach($viewItems as $it): ?>
          <tr>
            <td><?= e($it['name']) ?></td>
            <td><?= money($it['price']) ?></td>
            <td><?= $it['quantity'] ?></td>
            <td><?= money($it['price'] * $it['quantity']) ?></td>
          </tr>
        <?php endforeach; ?>
        <tfoot>
          <tr><td colspan="3"><strong>Итого</strong></td><td><strong><?= money($view['total']) ?></strong></td></tr>
        </tfoot>
        </tbody>
      </table>
<?php
    } else {
        $orders = get_all_orders();
?>
    <h1>Заказы</h1>
    <table class="admin-table">
      <thead><tr><th>№</th><th>Клиент</th><th>Сумма</th><th>Статус</th><th>Оплата</th><th>Дата</th><th></th></tr></thead>
      <tbody>
      <?php foreach($orders as $o): ?>
        <tr>
          <td>#<?= $o['id'] ?></td>
          <td><?= e($o['user_name'] ?? 'Гость') ?></td>
          <td><?= money($o['total']) ?></td>
          <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
          <td><?= e($paymentStatusLabels[$o['payment_status']] ?? $o['payment_status']) ?></td>
          <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
          <td>
            <a href="?action=orders&id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a>
            <?php if (!$isMod): ?>
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
<?php
    }
}