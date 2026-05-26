<?php
// index.php
require_once __DIR__ . '/includes/functions.php';

// Получаем 4 последних добавленных товара
$stmt = db()->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.active=1
    ORDER BY p.created_at DESC
    LIMIT 4
");
$stmt->execute();
$new_products = $stmt->fetchAll();

// Получаем 4 последних отзыва
$reviews = db()->prepare("
    SELECT
        r.id,
        r.comment AS text,
        r.rating,
        r.created_at,
        u.name AS author_name,
        p.name AS product_name,
        p.id AS product_id
    FROM reviews r
    LEFT JOIN users u ON r.user_id=u.id
    LEFT JOIN products p ON r.product_id=p.id
    WHERE r.approved = 1  -- показываем только одобренные отзывы
    ORDER BY r.created_at DESC
    LIMIT 4
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
  <h2 align="center">Наши новинки</h2><br>
  <div class="product-grid home-grid">
    <?php foreach($new_products as $p): ?>
      <?php require __DIR__ . '/includes/partials/product-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>

<section class="reviews home-reviews">
  <h2 align="center">Отзывы покупателей</h2>
  <div class="reviews-grid">
    <?php foreach($reviews as $review): ?>
      <div class="review-card">
        <div class="review-card__info">
          <h3><a href="product.php?id=<?= $review['product_id'] ?>"><?= e($review['product_name']) ?></a></h3>
          <div class="rating-stars">
            <?php for ($i = 1; $i <= $review['rating']; $i++): ?>
              <span class="star star-filled">★</span>
            <?php endfor; ?>
          </div>
        </div>
        <p class="review-text"><?= e($review['text']) ?></p>
        <div class="review-footer">
          <span class="author"><?= e($review['author_name']) ?></span>
          <time class="muted small"><?= date('d.m.Y', strtotime($review['created_at'])) ?></time>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>