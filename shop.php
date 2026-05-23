<?php
// shop.php
require_once __DIR__ . '/includes/functions.php';

$cat = $_GET['cat'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)(require __DIR__ . '/config.php')['items_per_page'];

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
require __DIR__ . '/includes/header.php';
?>

<div class="catalog-container">
    <div class="product-grid">
        <?php if(!$products): ?>
            <p class="empty">Товары не найдены</p>
        <?php else: foreach($products as $p): ?>
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
        <?php endforeach; endif; ?>
    </div>

    <div class="filter-sidebar">
        <select id="category-filter" name="cat" onchange="document.location='?cat='+this.value;" style="width: fit-content;">
            <option value="">Все товары</option>
            <?php foreach($categories as $c): ?>
                <option value="<?= e($c['slug']) ?>" <?= $cat===$c['slug']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php if($pages > 1): ?>
<nav class="pagination">
    <?php for($i=1; $i<=$pages; $i++):
        $qs = http_build_query(array_merge($_GET, ['page'=>$i]));
    ?>
        <a href="?<?= $qs ?>" class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>