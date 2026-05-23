<?php
// index.php
require_once __DIR__ . '/includes/functions.php';

// Получаем 3 последних добавленных товара
$stmt = db()->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.active=1
    ORDER BY p.created_at DESC
    LIMIT 3
");
$stmt->execute();
$new_products = $stmt->fetchAll();

// Получаем 3 последних отзыва
$reviews = db()->prepare("
    SELECT 
        r.id, 
        r.comment AS text, 
        r.rating, 
        u.name AS author_name, 
        p.name AS product_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id=u.id
    LEFT JOIN products p ON r.product_id=p.id
    WHERE r.approved = 1  -- показываем только одобренные отзывы
    ORDER BY r.created_at DESC
    LIMIT 3
");
$reviews->execute();
$reviews = $reviews->fetchAll();

$pageTitle = 'Главная страница';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <h1>Свечи, которые согревают душу</h1>
  <p>Натуральный пчелиный воск. Ароматы, вдохновлённые природой.</p>
</section>

<section class="new-products">
  <h2>Новинки</h2>
  <div class="product-grid">
    <?php foreach($new_products as $p): ?>
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
                <button class="btn btn-primary btn-sm">В корзину</button>
              </form>
            <?php else: ?>
              <span class="out-of-stock">Нет в наличии</span>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="reviews">
  <h2>Отзывы покупателей</h2>
  <?php foreach($reviews as $review): ?>
    <div class="review-card">
      <div class="review-header">
        <h3><?= e($review['product_name']) ?></h3>
        <div class="rating" style="width: <?= $review['rating'] * 20 ?>%"></div>
      </div>
      <p class="review-text"><?= e($review['text']) ?></p>
      <div class="review-footer">
        <span class="author"><?= e($review['author_name']) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
