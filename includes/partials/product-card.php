<?php
/**
 * Шаблон карточки товара
 * Использование: require __DIR__ . '/partials/product-card.php';
 * Требуется: $p - массив данных товара
 */
?>
<article class="product-card">
  <a href="/product.php?id=<?= $p['id'] ?>">
    <img src="<?= product_image($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
  </a>
  <div class="product-info">
    <?php if($p['category_name']): ?><span class="cat-tag"><?= e($p['category_name']) ?></span><?php endif; ?>
    <h3><a href="/product.php?id=<?= $p['id'] ?>"><?= e($p['name']) ?></a></h3>
    <?php if($p['aroma']): ?><p class="aroma"><?= e($p['aroma']) ?></p><?php endif; ?>
    <div class="price-row">
      <span class="price"><?= money($p['price']) ?></span>
      <?php if($p['stock']>0): ?>
        <form method="post" action="/cart.php">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="btn btn-ghost btn-sm">В корзину</button>
        </form>
      <?php else: ?>
        <span class="out-of-stock">Нет в наличии</span>
      <?php endif; ?>
    </div>
  </div>
</article>