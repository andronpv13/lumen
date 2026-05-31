<?php
/**
 * Оформление заказа
 * Маршрут: /?route=checkout
 */

require_once __DIR__ . '/../includes/functions.php';
require_login();

// Получение данных пользователя
$user = current_user();
$items = cart_items();

// Проверка: корзина не пуста
if (!$items) {
    flash('Корзина пуста', 'error');
    redirect('/?route=cart');
}

$total = cart_total();
$delivery = setting('delivery_cost', 0);
$grandTotal = $total + $delivery;

// Загрузка профиля доставки если есть
$deliveryProfile = null;
if ($user) {
    $stmt = db()->prepare("SELECT * FROM user_delivery_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $deliveryProfile = $stmt->fetch() ?: null;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $name = trim(post_param('name', ''));
    $phone = trim(post_param('phone', ''));
    $address = trim(post_param('address', ''));
    $notes = trim(post_param('notes', ''));
    $payment = post_param('payment', 'cod');
    
    // Валидация
    $errors = [];
    if ($name === '') $errors[] = 'Укажите имя получателя';
    if ($phone === '') $errors[] = 'Укажите телефон';
    // Простая валидация телефона (можно усилить)
    if ($phone && !preg_match('/^[\d\s\+\-\(\)]{10,}$/', $phone)) {
        $errors[] = 'Неверный формат телефона';
    }
    if ($address === '') $errors[] = 'Укажите адрес доставки';
    
    if ($errors) {
        flash(implode('<br>', $errors), 'error');
    } else {
        try {
            db()->beginTransaction();
            
            // Создание заказа
            $payStatus = ($payment === 'card') ? 'paid' : 'pending';
            $status = ($payment === 'card') ? 'paid' : 'new';
            
            $stmt = db()->prepare("
                INSERT INTO orders (
                    user_id, total, status, payment_method, payment_status,
                    shipping_name, shipping_phone, shipping_address, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'], $grandTotal, $status, $payment, $payStatus,
                $name, $phone, $address, $notes
            ]);
            $orderId = (int)db()->lastInsertId();
            
            // Элементы заказа
            $itemStmt = db()->prepare("
                INSERT INTO order_items (order_id, product_id, name, price, quantity) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stockStmt = db()->prepare("
                UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?
            ");
            
            foreach ($items as $it) {
                if ($it) { // null-проверка
                    $itemStmt->execute([
                        $orderId, $it['id'], $it['name'], $it['price'], $it['qty']
                    ]);
                    $stockStmt->execute([$it['qty'], $it['id']]);
                }
            }
            
            db()->commit();
            
            // Очистка корзины
            $_SESSION['cart'] = [];
            
            flash("Заказ №$orderId оформлен! Спасибо за покупку.", 'success');
            redirect('/?route=users&tab=orders');
            
        } catch (Exception $e) {
            db()->rollBack();
            error_log('Order creation error: ' . $e->getMessage());
            flash('Ошибка оформления заказа', 'error');
        }
    }
}

$pageTitle = 'Оформление заказа';
?>

<!-- Форма оформления (разметка без изменений) -->
<section class="checkout-page">
    <h1>Оформление заказа</h1>
    
    <div class="checkout-summary">
        <h2>Ваш заказ</h2>
        <ul class="order-items">
            <?php foreach ($items as $it): ?>
                <?php if ($it): ?>
                <li>
                    <?= e($it['name']) ?> × <?= (int)$it['qty'] ?> = <?= money($it['price'] * $it['qty']) ?>
                </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <p><strong>Итого товаров:</strong> <?= money($total) ?></p>
        <?php if ($delivery > 0): ?>
            <p><strong>Доставка:</strong> <?= money($delivery) ?></p>
        <?php endif; ?>
        <p class="grand-total"><strong>К оплате:</strong> <?= money($grandTotal) ?></p>
    </div>
    
    <form method="POST" class="checkout-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        
        <fieldset>
            <legend>Данные доставки</legend>
            
            <p>
                <label>Имя получателя *:
                    <input type="text" name="name" value="<?= e($name ?? $deliveryProfile['name'] ?? $user['name'] ?? '') ?>" required>
                </label>
            </p>
            
            <p>
                <label>Телефон *:
                    <input type="tel" name="phone" value="<?= e($phone ?? $deliveryProfile['phone'] ?? $user['phone'] ?? '') ?>" required pattern="^[\d\s\+\-\(\)]{10,}$">
                </label>
            </p>
            
            <p>
                <label>Адрес доставки *:
                    <textarea name="address" rows="3" required><?= e($address ?? $deliveryProfile['address'] ?? $user['address'] ?? '') ?></textarea>
                </label>
            </p>
            
            <p>
                <label>Комментарий к заказу:
                    <textarea name="notes" rows="2"><?= e($notes ?? '') ?></textarea>
                </label>
            </p>
        </fieldset>
        
        <fieldset>
            <legend>Способ оплаты</legend>
            
            <label class="payment-option">
                <input type="radio" name="payment" value="card" <?= ($payment ?? '') === 'card' ? 'checked' : '' ?>>
                <span>💳 Банковская карта</span>
            </label>
            
            <label class="payment-option">
                <input type="radio" name="payment" value="sbp" <?= ($payment ?? '') === 'sbp' ? 'checked' : '' ?>>
                <span>📱 СБП</span>
            </label>
            
            <label class="payment-option">
                <input type="radio" name="payment" value="cod" <?= !in_array($payment ?? '', ['card', 'sbp']) ? 'checked' : '' ?>>
                <span>💵 Наличными при получении</span>
            </label>
        </fieldset>
        
        <button type="submit" class="btn btn-primary btn-lg">Подтвердить заказ</button>
    </form>
</section>