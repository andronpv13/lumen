<?php
// modules/cart.php - Корзина
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD']==='POST') csrf_check();

if ($action === 'add') {
    $id = (int)$_POST['id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    flash('Товар добавлен в корзину','success');
    redirect($_SERVER['HTTP_REFERER'] ?? '/?route=cart');
}
if ($action === 'update') {
    $id = (int)$_POST['id'];
    $qty = (int)$_POST['qty'];
    if ($qty <= 0) unset($_SESSION['cart'][$id]);
    else $_SESSION['cart'][$id] = $qty;
    redirect('/?route=cart');
}
if ($action === 'remove') {
    unset($_SESSION['cart'][(int)$_POST['id']]);
    redirect('/?route=cart');
}
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    redirect('/?route=cart');
}

$items = cart_items();
$total = cart_total();
$pageTitle = 'Корзина';
?>

<h1>Корзина</h1>

<?php if(!$items): ?>
  <p class="empty">Корзина пуста. <a href="/?route=shop" class="btn btn-sm btn-ghost" style="margin-bottom:1rem;">Вернуться в каталог</a></p>
<?php else: ?>
  <table class="cart-table">
    <thead><tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th><th></th></tr></thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td>
          <a href="/?route=product&id=<?= $it['id'] ?>" class="cart-item">
            <img src="<?= product_image($it['image']) ?>" alt="">
            <span><?= e($it['name']) ?></span>
          </a>
        </td>
        <td><?= money($it['price']) ?></td>
        <td>
          <form method="post" class="qty-form">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $it['id'] ?>">
            <input type="number" name="qty" value="<?= $it['qty'] ?>" min="1" onchange="this.form.submit()">
          </form>
        </td>
        <td><?= money($it['price']*$it['qty']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Удалить?')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="id" value="<?= $it['id'] ?>">
            <button class="btn btn-ghost btn-sm" title="Удалить"> 🗑️ </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="3"><strong>Итого товаров в 🛒 - <?php if(cart_count()): ?><?= cart_count() ?><?php endif; ?></strong></td><td colspan="2"><strong class="big-price"><?= money($total) ?></strong></td></tr>
    </tfoot>
  </table>

  <div class="cart-actions">
    <form method="post" onsubmit="return confirm('Очистить корзину?')">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="clear">
      <button class="btn btn-ghost" title=""> Очистить 🛒 </button>
    </form>
    <a href="/?route=checkout" class="btn btn-primary">Оформить заказ →</a>
  </div>
<?php endif; ?>