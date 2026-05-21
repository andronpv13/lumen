<?php
// orders.php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();

$orders = db()->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

$orderId = (int)($_GET['id'] ?? 0);
$detail = null;
$detailItems = [];
if ($orderId) {
    $stmt = db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $stmt->execute([$orderId, $user['id']]);
    $detail = $stmt->fetch();
    if ($detail) {
        $stmt = db()->prepare("SELECT * FROM order_items WHERE order_id=?");
        $stmt->execute([$orderId]);
        $detailItems = $stmt->fetchAll();
    }
}

$pageTitle = 'Мои заказы';
require __DIR__ . '/includes/header.php';
$statusLabels = ['new'=>'Новый','processing'=>'В обработке','paid'=>'Оплачен','shipped'=>'Отправлен','delivered'=>'Доставлен','cancelled'=>'Отменён'];
?>

<h1>Мои заказы</h1>

<?php if($detail): ?>
  <a href="/orders.php" class="back">← Все заказы</a>
  <div class="order-detail">
    <h2>Заказ №<?= $detail['id'] ?></h2>
    <p><strong>Статус:</strong> <?= $statusLabels[$detail['status']] ?? $detail['status'] ?></p>
    <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($detail['created_at'])) ?></p>
    <p><strong>Адрес:</strong> <?= e($detail['shipping_address']) ?></p>
    <table class="cart-table">
      <thead><tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr></thead>
      <tbody>
      <?php foreach($detailItems as $it): ?>
        <tr>
          <td><?= e($it['name']) ?></td>
          <td><?= money($it['price']) ?></td>
          <td><?= $it['quantity'] ?></td>
          <td><?= money($it['price']*$it['quantity']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="3"><strong>Итого</strong></td><td><strong><?= money($detail['total']) ?></strong></td></tr></tfoot>
    </table>
  </div>
<?php else: ?>
  <?php if(!$orders): ?>
    <p class="empty">У вас пока нет заказов. <a href="/">Перейти в каталог</a></p>
  <?php else: ?>
    <table class="cart-table">
      <thead><tr><th>№</th><th>Дата</th><th>Сумма</th><th>Статус</th><th>Оплата</th><th></th></tr></thead>
      <tbody>
      <?php foreach($orders as $o): ?>
        <tr>
          <td>#<?= $o['id'] ?></td>
          <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
          <td><?= money($o['total']) ?></td>
          <td><span class="status status-<?= $o['status'] ?>"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span></td>
          <td><?= $o['payment_status']==='paid'?'Оплачен':'Ожидает' ?></td>
          <td><a href="/orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost">Детали</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>