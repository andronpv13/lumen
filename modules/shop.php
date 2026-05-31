<?php
/**
 * Каталог товаров
 * Маршрут: /?route=shop[&cat=ID][&page=N][&sort=...]
 */

require_once __DIR__ . '/../includes/functions.php';

// Параметры с валидацией
$page = max(1, get_int_param('page', 1, 1, 1000));
$perPage = $config['items_per_page'] ?? 12;
$categoryId = get_int_param('cat', 0, 0);
$sort = preg_replace('/[^a-z_]/', '', $_GET['sort'] ?? 'new');

// Формирование WHERE-условий
$whereParts = ['p.active = 1'];
$params = [];

if ($categoryId > 0) {
    $whereParts[] = 'p.category_id = ?';
    $params[] = $categoryId;
}

$whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

// Сортировка (белый список)
$sortOptions = [
    'new' => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name' => 'p.name ASC',
];
$orderBy = $sortOptions[$sort] ?? $sortOptions['new'];

// Подсчёт общего количества
$countStmt = db()->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages); // Корректировка страницы если вышла за пределы

// Получение товаров
$stmt = db()->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $whereSql 
    ORDER BY $orderBy 
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, ($page - 1) * $perPage]));
$products = $stmt->fetchAll();

// Категории для фильтра
$categories = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'Каталог свечей';
?>

<!-- Фильтры и товары (разметка без изменений) -->
<section class="shop-page">
    <h1>Каталог свечей</h1>
    
    <!-- Фильтры -->
    <form class="shop-filters" method="GET">
        <input type="hidden" name="route" value="shop">
        
        <label>Категория:
            <select name="cat" onchange="this.form.submit()">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        
        <label>Сортировка:
            <select name="sort" onchange="this.form.submit()">
                <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Новинки</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цена: по возрастанию</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>По названию</option>
            </select>
        </label>
    </form>
    
    <!-- Сетка товаров -->
    <?php if ($products): ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <article class="product-card">
                    <a href="/?route=product&id=<?= (int)$p['id'] ?>">
                        <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>">
                        <h3><?= e($p['name']) ?></h3>
                        <p class="price"><?= money($p['price']) ?></p>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
        
        <!-- Пагинация -->
        <?php if ($pages > 1): ?>
            <nav class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/?route=shop<?= $categoryId ? '&cat=' . $categoryId : '' ?><?= $sort !== 'new' ? '&sort=' . $sort : '' ?>&page=<?= $page - 1 ?>">← Назад</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php if ($i == 1 || $i == $pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="/?route=shop<?= $categoryId ? '&cat=' . $categoryId : '' ?><?= $sort !== 'new' ? '&sort=' . $sort : '' ?>&page=<?= $i ?>" 
                           class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $pages): ?>
                    <a href="/?route=shop<?= $categoryId ? '&cat=' . $categoryId : '' ?><?= $sort !== 'new' ? '&sort=' . $sort : '' ?>&page=<?= $page + 1 ?>">Вперёд →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <p class="no-products">Товары не найдены</p>
    <?php endif; ?>
</section>