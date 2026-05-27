<?php
// modules/product.php - Страница товара
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=? AND p.active=1");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { http_response_code(404); die('Товар не найден'); }

// Обработка отзыва
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='review') {
    csrf_check();
    require_login();
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment'] ?? '');
    if ($rating<1 || $rating>5 || $comment==='') {
        flash('Заполните все поля отзыва','error');
    } else {
        $stmt = db()->prepare("INSERT INTO reviews (product_id,user_id,rating,comment) VALUES (?,?,?,?)");
        $stmt->execute([$id, current_user()['id'], $rating, $comment]);
        flash('Спасибо! Ваш отзыв появится после модерации','success');
    }
    redirect("/?route=product&id=$id");
}

// Статистика отзывов
$rev = db()->prepare("SELECT COUNT(*) cnt, AVG(rating) avg FROM reviews WHERE product_id=? AND approved=1");
$rev->execute([$id]);
$rev = $rev->fetch();

$reviews = db()->prepare("SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.product_id=? AND r.approved=1 ORDER BY r.created_at DESC");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

$pageTitle = $p['name'];
require __DIR__ . '/../includes/header.php';
?>

<div class="product-detail">
  <div class="pd-image">
    <img src="<?= product_image($p['image']) ?>" alt="<?= e($p['name']) ?>">
  </div>
  <div class="pd-info">
    <?php if($p['category_name']): ?><span class="cat-tag"><?= e($p['category_name']) ?></span><?php endif; ?>
    <h1><?= e($p['name']) ?></h1>
    <div class="rating-summary">
      <?php if($rev['cnt']>0): ?>
        <?php for($i=1;$i<=5;$i++): ?><span class="<?= $i<=round($rev['avg'])?'star-filled':'star-empty' ?>">★</span><?php endfor; ?>
        <span><?= number_format($rev['avg'],1) ?> (<?= $rev['cnt'] ?>)</span>
      <?php else: ?>
        <span class="muted">Нет отзывов</span>
      <?php endif; ?>
    </div>
    <p class="big-price"><?= money($p['price']) ?></p>
    <p class="description"><?= nl2br(e($p['description'])) ?></p>
    <div class="specs">
      <?php if($p['aroma']): ?><div><strong>Аромат:</strong> <?= e($p['aroma']) ?></div><?php endif; ?>
      <?php if($p['weight']): ?><div><strong>Вес:</strong> <?= $p['weight'] ?> г</div><?php endif; ?>
      <div><strong>В наличии:</strong> <?= $p['stock'] ?> шт.</div>
    </div>
    <?php if($p['stock']>0): ?>
      <form method="post" action="/?route=cart" class="add-to-cart-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="number" name="qty" value="1" min="1" max="<?= $p['stock'] ?>">
        <button class="btn btn-primary">В корзину</button>
      </form>
    <?php else: ?>
      <p class="out-of-stock">Товара нет в наличии</p>
    <?php endif; ?>
  </div>
</div>

<section class="reviews-section">
  <h2>Отзывы</h2>

  <div class="reviews-list">
    <?php if(!$reviews): ?>
      <p class="muted">Пока нет отзывов. Будьте первым!</p>
    <?php else: foreach($reviews as $r): ?>
      <article class="review">
        <header>
          <strong><?= e($r['name']) ?></strong>
          <span class="stars"><?php for($i=1;$i<=$r['rating'];$i++): ?><span class="star-filled">★</span><?php endfor; ?></span>
          <time><?= date('d.m.Y', strtotime($r['created_at'])) ?></time>
        </header>
        <p><?= nl2br(e($r['comment'])) ?></p>
      </article>
    <?php endforeach; endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>