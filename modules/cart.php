<?php
/**
 * Корзина покупок
 * Маршрут: /?route=cart
 */

require_once __DIR__ . '/../includes/functions.php';

// Обработка действий с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        // Обновление количества
        foreach ($_POST['qty'] ?? [] as $id => $qty) {
            $id = (int)$id;
            $qty = max(0, (int)$qty);
            
            if ($qty <= 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id] = $qty;
            }
        }
        flash('Корзина обновлена', 'success');
        
    } elseif ($action === 'remove') {
        // Удаление товара
        $id = (int)($_POST['id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        flash('Товар удалён из корзины', 'success');
        
    } elseif ($action === 'clear') {
        // Очистка корзины
        $_SESSION['cart'] = [];
        flash('Корзина очищена', 'success');
    }
    
    // AJAX или редирект
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'cart_count' => cart_count()]);
        exit;
    }
    redirect('/?route=cart');
}

// Получение товаров корзины
$items = cart_items();
$total = cart_total();

$pageTitle = 'Корзина';
?>

<!-- Разметка корзины (без изменений в стилях) -->
<section class="cart-page">
    <h1>Ваша корзина</h1>
    
    <?php if (!$items): ?>
        <p class="cart-empty">Корзина пуста. <a href="/?route=shop">Вернуться в каталог</a></p>
    <?php else: ?>
        <form method="POST" class="cart-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Цена</th>
                        <th>Кол-во</th>
                        <th>Сумма</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $p): ?>
                        <?php if ($p): // null-проверка ?>
                        <tr>
                            <td>
                                <a href="/?route=product&id=<?= (int)$p['id'] ?>">
                                    <?= e($p['name']) ?>
                                </a>
                            </td>
                            <td><?= money($p['price']) ?></td>
                            <td>
                                <input type="number" name="qty[<?= (int)$p['id'] ?>]" 
                                       value="<?= (int)$p['qty'] ?>" 
                                       min="1" 
                                       max="<?= (int)$p['stock'] ?>" 
                                       class="qty-input"
                                       data-id="<?= (int)$p['id'] ?>">
                            </td>
                            <td><?= money($p['price'] * $p['qty']) ?></td>
                            <td>
                                <button type="submit" name="action" value="remove" 
                                        class="btn-remove" 
                                        formaction="" 
                                        data-ajax
                                        onclick="this.form.querySelector('[name=id]').value=<?= (int)$p['id'] ?>">
                                    🗑️
                                </button>
                                <input type="hidden" name="id" value="">
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Итого:</strong></td>
                        <td colspan="2"><strong><?= money($total) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="cart-actions">
                <button type="submit" name="action" value="clear" class="btn btn-secondary">Очистить корзину</button>
                <a href="/?route=checkout" class="btn btn-primary">Оформить заказ →</a>
            </div>
        </form>
    <?php endif; ?>
</section>