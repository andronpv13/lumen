<?php
// modules/shop.php - Каталог товаров
require_once __DIR__ . '/../includes/functions.php';

$cat = $_GET['cat'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)(require __DIR__ . '/../config.php')['items_per_page'];

$where = ['p.active=1'];
$params = [];
if ($cat) { $where[] = 'c.slug=?'; $params[] = $cat; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = db()->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id=c.id $whereSql");
$total->execute($params);
$total = (int)$total->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = db()->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p LEFT JOIN categories c ON p.category_id=c.id
    $whereSql
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, ($page-1)*$perPage]));
$products = $stmt->fetchAll();

$categories = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'Каталог свечей';
require __DIR__ . '/../includes/header.php';
?>
<div class="catalog-header">
    <h2 class="catalog-title">Каталог свечей</h2>
    <div class="filter-sidebar-inline">
        <select id="category-filter" name="cat" onchange="window.location.href='/?route=shop&cat='+this.value;">
            <option value="">Все товары</option>
            <?php foreach($categories as $c): ?>
                <option value="<?= e($c['slug']) ?>" <?= $cat===$c['slug']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="catalog-container">
    <div class="product-grid">
        <?php if(!$products): ?>
            <p class="empty">Товары не найдены</p>
        <?php else: foreach($products as $p): ?>
            <?php require __DIR__ . '/../includes/partials/product-card.php'; ?>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php if($pages > 1): ?>
<nav class="pagination">
    <?php for($i=1; $i<=$pages; $i++):
        $qs = http_build_query(array_merge($_GET, ['page'=>$i]));
    ?>
        <a href="/?route=shop&<?= $qs ?>" class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>