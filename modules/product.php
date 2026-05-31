<?php
/**
 * Страница товара
 * Маршрут: /?route=product&id=ID
 */

require_once __DIR__ . '/../includes/functions.php';

// Получаем и валидируем ID товара
$id = get_int_param('id', 0, 1);

if ($id <= 0) {
    http_response_code(400);
    echo '<p>Неверный параметр товара</p>';
    return;
}

// Запрос товара с проверкой активности
$stmt = db()->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.active = 1
");
$stmt->execute([$id]);
$p = $stmt->fetch();

// Товар не найден или не активен
if (!$p) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

// Обработка добавления в корзину
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    csrf_check();
    
    $qty = max(1, (int)post_param('qty', 1));
    
    // Проверка наличия товара
    if ($p['stock'] > 0) {
        $_SESSION['cart'][$p['id']] = ($_SESSION['cart'][$p['id']] ?? 0) + $qty;
        flash('Товар добавлен в корзину', 'success');
    } else {
        flash('Товара нет в наличии', 'error');
    }
    
    // AJAX или обычный редирект
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'cart_count' => cart_count()]);
        exit;
    }
    redirect('/?route=product&id=' . $id);
}

// Обработка отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    csrf_check();
    require_login();
    
    $rating = (int)post_param('rating', 0);
    $comment = trim(post_param('comment', ''));
    
    if ($rating < 1 || $rating > 5 || $comment === '') {
        flash('Заполните все поля отзыва', 'error');
    } else {
        $stmt = db()->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, approved) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$id, current_user()['id'], $rating, $comment]);
        flash('Спасибо! Ваш отзыв появится после модерации', 'success');
    }
    redirect('/?route=product&id=' . $id);
}

// Статистика отзывов
$revStmt = db()->prepare("SELECT COUNT(*) as cnt, AVG(rating) as avg FROM reviews WHERE product_id = ? AND approved = 1");
$revStmt->execute([$id]);
$revStats = $revStmt->fetch();

// Список одобренных отзывов
$revListStmt = db()->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.approved = 1 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$revListStmt->execute([$id]);
$reviews = $revListStmt->fetchAll();

$pageTitle = e($p['name']);
?>

<!-- HTML-разметка страницы товара (без изменений в структуре и стилях) -->
<article class="product-page">
    <div class="product-images">
        <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>" class="product-main-image">
    </div>
    
    <div class="product-info">
        <h1><?= e($p['name']) ?></h1>
        
        <?php if (!empty($p['category_name'])): ?>
            <p class="product-category">Категория: <a href="/?route=shop&cat=<?= e($p['category_id']) ?>"><?= e($p['category_name']) ?></a></p>
        <?php endif; ?>
        
        <p class="product-price"><?= money($p['price']) ?></p>
        
        <div class="product-description">
            <?= nl2br(e($p['description'])) ?>
        </div>
        
        <div class="product-meta">
            <?php if (!empty($p['weight'])): ?>
                <p><strong>Вес:</strong> <?= e($p['weight']) ?> г</p>
            <?php endif; ?>
            <p><strong>В наличии:</strong> <?= (int)$p['stock'] ?> шт.</p>
        </div>
        
        <?php if ($p['stock'] > 0): ?>
            <form method="POST" class="add-to-cart-form" data-ajax>
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <label>
                    Количество:
                    <input type="number" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>" class="qty-input">
                </label>
                <button type="submit" class="btn btn-primary">В корзину</button>
            </form>
        <?php else: ?>
            <p class="out-of-stock">Товара нет в наличии</p>
        <?php endif; ?>
    </div>
    
    <!-- Отзывы -->
    <section class="product-reviews">
        <h2>Отзывы</h2>
        
        <?php if ($revStats && $revStats['cnt'] > 0): ?>
            <p class="reviews-summary">
                Средняя оценка: 
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= $i <= round($revStats['avg']) ? 'filled' : '' ?>">★</span>
                <?php endfor; ?>
                (<?= (int)$revStats['cnt'] ?>)
            </p>
        <?php else: ?>
            <p>Пока нет отзывов. Будьте первым!</p>
        <?php endif; ?>
        
        <?php if (current_user()): ?>
            <form method="POST" class="review-form">
                <input type="hidden" name="action" value="review">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <p>
                    <label>Оценка:
                        <select name="rating" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> ★</option>
                            <?php endfor; ?>
                        </select>
                    </label>
                </p>
                <p>
                    <label>Комментарий:
                        <textarea name="comment" rows="4" required></textarea>
                    </label>
                </p>
                <button type="submit" class="btn">Оставить отзыв</button>
            </form>
        <?php else: ?>
            <p><a href="/?route=auth">Войдите</a>, чтобы оставить отзыв</p>
        <?php endif; ?>
        
        <?php if ($reviews): ?>
            <ul class="reviews-list">
                <?php foreach ($reviews as $r): ?>
                    <li class="review-item">
                        <strong><?= e($r['user_name']) ?></strong>
                        <span class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $r['rating'] ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                        </span>
                        <p><?= nl2br(e($r['comment'])) ?></p>
                        <small class="review-date"><?= date('d.m.Y', strtotime($r['created_at'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</article>