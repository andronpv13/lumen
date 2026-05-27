<?php
// modules/checkout.php - Оформление заказа
require_once __DIR__ . '/../includes/functions.php';
require_login();

$items = cart_items();
if (!$items) redirect('/?route=cart');

$total = cart_total();
$delivery = (float)setting('delivery_price', 0);
$user = current_user();

// Загрузка профиля доставки
$deliveryProfile = null;
$stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id=?");
$stmt->execute([$user['id']]);
$deliveryProfile = $stmt->fetch() ?: null;

// Формирование полного адреса из полей профиля
$fullAddress = '';
if ($deliveryProfile) {
    $parts = [];
    if ($deliveryProfile['postal_code']) $parts[] = $deliveryProfile['postal_code'];
    if ($deliveryProfile['region']) $parts[] = $deliveryProfile['region'];
    if ($deliveryProfile['district']) $parts[] = $deliveryProfile['district'];
    if ($deliveryProfile['city']) $parts[] = $deliveryProfile['city'];
    if ($deliveryProfile['street']) $parts[] = $deliveryProfile['street'];
    if ($deliveryProfile['building']) {
        $building = $deliveryProfile['building'];
        if ($deliveryProfile['apartment']) $building .= ', кв. ' . $deliveryProfile['apartment'];
        $parts[] = $building;
    }
    $fullAddress = implode(', ', $parts);
}

$payment = 'card';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $payment = $_POST['payment'] ?? 'cod';
    if (!$name || !$phone || !$address) {
        flash('Заполните все обязательные поля','error');
    } else {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare("INSERT INTO orders (user_id,total,status,payment_method,payment_status,shipping_name,shipping_phone,shipping_address,notes) VALUES (?,?,?,?,?,?,?,?,?)");
            $payStatus = $payment==='card' ? 'paid' : 'pending';
            $status = $payment==='card' ? 'paid' : 'new';
            $stmt->execute([$user['id'], $total + $delivery, $status, $payment, $payStatus, $name, $phone, $address, $notes]);
            $orderId = db()->lastInsertId();

            $ins = db()->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (?,?,?,?,?)");
            foreach ($items as $it) {
                $ins->execute([$orderId, $it['id'], $it['name'], $it['price'], $it['qty']]);
                db()->prepare("UPDATE products SET stock=GREATEST(0,stock-?) WHERE id=?")->execute([$it['qty'], $it['id']]);
            }
            db()->commit();
            $_SESSION['cart'] = [];
            flash("Заказ №$orderId оформлен! Спасибо за покупку.",'success');
            redirect('/?route=users&tab=orders');
        } catch (Exception $e) {
            db()->rollBack();
            flash('Ошибка оформления: '.$e->getMessage(),'error');
        }
    }
}

$pageTitle = 'Оформление заказа';
?>

<h1>Оформление заказа</h1>

<div class="checkout-grid">
  <form method="post" class="checkout-form">
    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
    <h2>Данные доставки</h2>
    <label>Имя получателя *
      <input type="text" name="name" value="<?php echo e($deliveryProfile['first_name'] ?? $user['name']); ?>" required>
    </label>
    <label>Телефон *
      <input type="tel" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>" required>
    </label>
    <label>Адрес доставки *
      <textarea name="address" required><?php echo e($fullAddress ?: $user['address'] ?? ''); ?></textarea>
    </label>
    <label>Комментарий к заказу
      <textarea name="notes"></textarea>
    </label>

    <h2>Способ оплаты</h2>
    <div class="payment-options">
      <label class="payment-card<?= $payment === 'card' ? ' active' : '' ?>">
        <input type="radio" name="payment" value="card" <?= $payment === 'card' ? 'checked' : '' ?>>
        <strong>Банковская карта</strong>
        <span>Оплата картой через терминал</span>
      </label>
      <label class="payment-card<?= $payment === 'sbp' ? ' active' : '' ?>">
        <input type="radio" name="payment" value="sbp" <?= $payment === 'sbp' ? 'checked' : '' ?>>
        <strong>СБП</strong>
        <span>Оплата по Системе быстрых платежей</span>
      </label>
      <label class="payment-card<?= $payment === 'cod' ? ' active' : '' ?>">
        <input type="radio" name="payment" value="cod" <?= $payment === 'cod' ? 'checked' : '' ?>>
        <strong>Наличными</strong>
        <span>Оплата при получении</span>
      </label>
    </div>

    <button class="btn btn-primary btn-lg">Подтвердить заказ</button>
  </form>

  <aside class="order-summary">
    <h2>Ваш заказ</h2>
    <?php foreach($items as $it): ?>
      <div class="summary-row">
        <span><?php echo e($it['name']); ?> × <?php echo $it['qty']; ?></span>
        <strong><?php echo money($it['price']*$it['qty']); ?></strong>
      </div>
    <?php endforeach; ?>
    <hr>
    <div class="summary-row"><span>Товары</span><span><?php echo money($total); ?></span></div>
    <div class="summary-row"><span>Доставка</span><span><?php echo money($delivery); ?></span></div>
    <div class="summary-row total"><span>Итого</span><strong><?php echo money($total+$delivery); ?></strong></div>
  </aside>
</div>