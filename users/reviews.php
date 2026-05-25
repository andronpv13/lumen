<?php
// reviews.php - модуль для users.php (не должен загружаться автономно)

// Проверка: если файл вызван напрямую (автономно), перенаправляем на users.php
if (!isset($standalone) || $standalone !== false) {
    // Файл вызван напрямую, а не через include из users.php
    // Перенаправляем на страницу пользователей с вкладкой отзывов
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../includes/functions.php';
    require_login();

    header('Location: /users.php?tab=reviews');
    exit;
}

// Режим модуля: переменная $user должна быть установлена в users.php
if (!isset($user)) {
    // Это не должно произойти, но на всякий случай
    $user = current_user();
}

$errors = [];
$success = '';
$orderedItems = [];
$reviewedIds = [];
$userReviews = [];

// Логика обработки сохранения отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review'])) {
    $orderId = (int)$_POST['order_id'];
    $productId = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    // Проверка, что пользователь действительно заказывал этот товар
    $checkOrderStmt = db()->prepare("
        SELECT oi.id FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status != 'cancelled'
    ");
    $checkOrderStmt->execute([$user['id'], $productId]);
    $orderItem = $checkOrderStmt->fetch();

    if (!$orderItem) {
        $errors[] = "Вы не можете оставить отзыв на товар, который не покупали.";
    } elseif ($rating < 1 || $rating > 5) {
        $errors[] = "Оценка должна быть от 1 до 5.";
    } else {
        // Проверка существования отзыва
        $checkReviewStmt = db()->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $checkReviewStmt->execute([$user['id'], $productId]);
        $existingReview = $checkReviewStmt->fetch();

        if ($existingReview) {
            // Обновление существующего отзыва
            $updateReviewStmt = db()->prepare("
                UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW()
                WHERE user_id = ? AND product_id = ?
            ");
            $updateReviewStmt->execute([$rating, $comment, $user['id'], $productId]);
        } else {
            // Добавление нового отзыва
            $insertReviewStmt = db()->prepare("
                INSERT INTO reviews (user_id, product_id, rating, comment)
                VALUES (?, ?, ?, ?)
            ");
            $insertReviewStmt->execute([$user['id'], $productId, $rating, $comment]);
        }

        $success = "Отзыв успешно сохранён!";
    }
}

// Загрузка данных для отображения (только если нет ошибок формы, чтобы сохранить введенные данные, или после успешного сохранения)
if (empty($errors) || !isset($_POST['save_review'])) {
    // Получение заказанных товаров для возможности оставлять отзывы
    $orderedItemsStmt = db()->prepare("
        SELECT DISTINCT p.id, p.name, p.image, o.id as order_id, MAX(o.created_at) as latest_order_date
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND o.status != 'cancelled'
        GROUP BY p.id, p.name, p.image, o.id
        ORDER BY latest_order_date DESC
    ");
    $orderedItemsStmt->execute([$user['id']]);
    $orderedItems = $orderedItemsStmt->fetchAll();

    // Получение уже оставленных отзывов
    $reviewedIdsStmt = db()->prepare("
        SELECT r.product_id FROM reviews r WHERE r.user_id = ?
    ");
    $reviewedIdsStmt->execute([$user['id']]);
    $reviewedIds = array_column($reviewedIdsStmt->fetchAll(), 'product_id');

    // Получение своих отзывов для отображения
    $userReviewsStmt = db()->prepare("
        SELECT r.*, p.name as product_name, p.image as product_image
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $userReviewsStmt->execute([$user['id']]);
    $userReviews = $userReviewsStmt->fetchAll();
}

// Вывод сообщений об ошибках/успехе и контента
?>

<div class="admin-content-block">

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success">
        <p><?php echo htmlspecialchars($success); ?></p>
    </div>
<?php endif; ?>

<h3>Товары для отзыва</h3>
<div class="product-grid reviews-grid">
    <?php if (empty($orderedItems)): ?>
        <p class="empty">У вас пока нет заказов, на которые можно оставить отзыв.</p>
    <?php else: ?>
        <?php foreach ($orderedItems as $item): ?>
            <?php if (!in_array($item['id'], $reviewedIds)): ?>
                <article class="product-card">
                    <?php if ($item['image']): ?>
                        <img src="<?php echo htmlspecialchars(product_image($item['image'])); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php else: ?>
                        <img src="assets/placeholder.svg" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php endif; ?>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <button class="btn btn-primary btn-sm" onclick="toggleReviewForm(<?php echo $item['id']; ?>)">Оставить отзыв</button>

                        <div id="review-form-<?php echo $item['id']; ?>" class="review-form" style="display:none;">
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?php echo $item['order_id']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">

                                <label for="rating_<?php echo $item['id']; ?>">Оценка:</label>
                                <div class="star-rating-input" id="star-rating-<?php echo $item['id']; ?>">
                                    <span data-value="1" onclick="setRating(<?php echo $item['id']; ?>, 1)">★</span>
                                    <span data-value="2" onclick="setRating(<?php echo $item['id']; ?>, 2)">★</span>
                                    <span data-value="3" onclick="setRating(<?php echo $item['id']; ?>, 3)">★</span>
                                    <span data-value="4" onclick="setRating(<?php echo $item['id']; ?>, 4)">★</span>
                                    <span data-value="5" onclick="setRating(<?php echo $item['id']; ?>, 5)">★</span>
                                    <input type="hidden" name="rating" id="rating_<?php echo $item['id']; ?>" value="5">
                                </div>

                                <label for="comment_<?php echo $item['id']; ?>">Комментарий:</label>
                                <textarea name="comment" id="comment_<?php echo $item['id']; ?>" placeholder="Ваш отзыв о товаре..."></textarea><br>

                                <button type="submit" name="save_review" class="btn btn-primary">Отправить отзыв</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($userReviews)): ?>
    <h3>Мои отзывы</h3>
    <div class="reviews-grid">
        <?php foreach ($userReviews as $review): ?>
            <div class="review-card">
                <div class="review-card__info">
                    <h3><?= e($review['product_name']) ?></h3>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= $review['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                </div>
                <p class="review-text"><?= e($review['comment']) ?></p>
                <div class="review-footer">
                    <span class="author"><?= e($user['name']) ?></span>
                    <time class="muted small"><?= date('d.m.Y', strtotime($review['created_at'])) ?></time>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div><!-- /.admin-content-block -->

<script>
function toggleReviewForm(productId) {
    const form = document.getElementById('review-form-' + productId);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function setRating(productId, rating) {
    // Устанавливаем значение в скрытый input
    document.getElementById('rating_' + productId).value = rating;

    // Обновляем визуальное отображение звёзд
    const container = document.getElementById('star-rating-' + productId);
    const stars = container.querySelectorAll('span');

    stars.forEach(function(star) {
        const value = parseInt(star.getAttribute('data-value'));
        if (value <= rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Инициализация: устанавливаем 5 звёзд по умолчанию при открытии формы
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.review-form');
    forms.forEach(function(form) {
        const productId = form.id.replace('review-form-', '');
        setRating(productId, 5);
    });
});
</script>