<?php
// Определение режима работы: автономный или включаемый
if (!isset($standalone)) {
    $standalone = true;
}

// Если файл вызван напрямую (автономный режим), подключаем зависимости
if ($standalone) {
    session_start();
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../functions.php';

    // Проверка авторизации
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
    
    // Заголовок страницы для автономного режима
    $pageTitle = "Отзывы на купленные товары";
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
    $checkOrderStmt = $pdo->prepare("
        SELECT oi.id FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status != 'cancelled'
    ");
    $checkOrderStmt->execute([$_SESSION['user_id'], $productId]);
    $orderItem = $checkOrderStmt->fetch();

    if (!$orderItem) {
        $errors[] = "Вы не можете оставить отзыв на товар, который не покупали.";
    } elseif ($rating < 1 || $rating > 5) {
        $errors[] = "Оценка должна быть от 1 до 5.";
    } else {
        // Проверка существования отзыва
        $checkReviewStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $checkReviewStmt->execute([$_SESSION['user_id'], $productId]);
        $existingReview = $checkReviewStmt->fetch();

        if ($existingReview) {
            // Обновление существующего отзыва
            $updateReviewStmt = $pdo->prepare("
                UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() 
                WHERE user_id = ? AND product_id = ?
            ");
            $updateReviewStmt->execute([$rating, $comment, $_SESSION['user_id'], $productId]);
        } else {
            // Добавление нового отзыва
            $insertReviewStmt = $pdo->prepare("
                INSERT INTO reviews (user_id, product_id, rating, comment) 
                VALUES (?, ?, ?, ?)
            ");
            $insertReviewStmt->execute([$_SESSION['user_id'], $productId, $rating, $comment]);
        }

        $success = "Отзыв успешно сохранён!";
    }
}

// Загрузка данных для отображения (только если нет ошибок формы, чтобы сохранить введенные данные, или после успешного сохранения)
if (empty($errors) || !isset($_POST['save_review'])) {
    // Получение заказанных товаров для возможности оставлять отзывы
    $orderedItemsStmt = $pdo->prepare("
        SELECT DISTINCT p.id, p.name, p.image_url, o.id as order_id
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND o.status != 'cancelled'
        ORDER BY o.created_at DESC
    ");
    $orderedItemsStmt->execute([$_SESSION['user_id']]);
    $orderedItems = $orderedItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение уже оставленных отзывов
    $reviewedIdsStmt = $pdo->prepare("
        SELECT r.product_id FROM reviews r WHERE r.user_id = ?
    ");
    $reviewedIdsStmt->execute([$_SESSION['user_id']]);
    $reviewedIds = array_column($reviewedIdsStmt->fetchAll(), 'product_id');

    // Получение своих отзывов для отображения
    $userReviewsStmt = $pdo->prepare("
        SELECT r.*, p.name as product_name, p.image_url as product_image
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $userReviewsStmt->execute([$_SESSION['user_id']]);
    $userReviews = $userReviewsStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Если файл вызван напрямую (автономный режим), показываем полную страницу
if ($standalone) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>
        <link rel="stylesheet" href="/styles.css">
    </head>
    <body>
        <div class="container">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <a href="../users.php">← Вернуться в личный кабинет</a>
            <hr>
            
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

            <!-- Контент вставки ниже -->
            <?php include __DIR__ . '/reviews_content.php'; ?>
            
        </div>
    </body>
    </html>
    <?php
} else {
    // Режим интеграции: выводим только контент без обертки
    // Вывод сообщений об ошибках/успехе
    if (!empty($errors)): ?>
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

    <!-- Подключаем чистый контент -->
    <?php include __DIR__ . '/reviews_content.php'; ?>
<?php
}

// Внутренний файл для чистого HTML контента (чтобы не дублировать код в двух режимах)
// Создадим его "на лету" через heredoc или просто продублируем логику выше, 
// но правильнее вынести HTML в отдельный блок. 
// Для простоты в рамках одного файла, мы просто продублируем HTML ниже внутри условия, 
// если не создаем отдельный файл reviews_content.php. 
// Чтобы соблюсти чистоту, я напишу HTML прямо здесь для режима интеграции, 
// так как создание третьего файла может усложнить структуру без запроса.

// ПЕРЕОПРЕДЕЛЕНИЕ: Чтобы не создавать лишний файл, я просто выведу HTML ниже 
// в блоке else, скопировав его из автономного режима, но убрав лишние теги.
// Однако, чтобы код был DRY (Don't Repeat Yourself), лучше использовать буферизацию или функцию.
// Но в рамках простой PHP структуры сделаем так:

if (!$standalone) {
    // Этот блок уже был открыт выше в else, теперь закрываем PHP для вывода HTML
    ?>
    
    <h3>Товары для отзыва</h3>
    <div class="products-grid">
        <?php if (empty($orderedItems)): ?>
            <p>У вас пока нет заказов, на которые можно оставить отзыв.</p>
        <?php else: ?>
            <?php foreach ($orderedItems as $item): ?>
                <?php if (!in_array($item['id'], $reviewedIds)): ?>
                    <div class="product-card">
                        <?php if ($item['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <button onclick="toggleReviewForm(<?php echo $item['id']; ?>)">Оставить отзыв</button>
                        
                        <div id="review-form-<?php echo $item['id']; ?>" class="review-form" style="display:none;">
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?php echo $item['order_id']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                
                                <label for="rating_<?php echo $item['id']; ?>">Оценка:</label>
                                <select name="rating" id="rating_<?php echo $item['id']; ?>" required>
                                    <option value="">Выберите оценку</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select><br>

                                <label for="comment_<?php echo $item['id']; ?>">Комментарий:</label>
                                <textarea name="comment" id="comment_<?php echo $item['id']; ?>"></textarea><br>

                                <button type="submit" name="save_review">Отправить отзыв</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($userReviews)): ?>
        <h3>Мои отзывы</h3>
        <div class="reviews-list">
            <?php foreach ($userReviews as $review): ?>
                <div class="review-item">
                    <?php if ($review['product_image']): ?>
                        <img src="<?php echo htmlspecialchars($review['product_image']); ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>" width="50">
                    <?php endif; ?>
                    <div>
                        <strong><?php echo htmlspecialchars($review['product_name']); ?></strong><br>
                        Оценка: <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?><br>
                        <?php echo htmlspecialchars($review['comment']); ?><br>
                        <small>Обновлено: <?php echo date('d.m.Y H:i', strtotime($review['updated_at'] ?? $review['created_at'])); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <script>
    function toggleReviewForm(productId) {
        const form = document.getElementById('review-form-' + productId);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    </script>
    
    <?php
}
?>