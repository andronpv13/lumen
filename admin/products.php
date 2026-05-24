<?php
// admin/products.php - Управление товарами
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repositories/ProductRepository.php';
require_once __DIR__ . '/../includes/repositories/CategoryRepository.php';

/**
 * Сохранить товар (создать или обновить)
 * @param array $postData Данные из формы
 * @return int ID сохранённого товара
 */
function save_product($postData) {
    $id = (int)$postData['id'];
    $cfg = require __DIR__ . '/../config.php';

    $data = [
        'category_id' => $postData['category_id'] ?: null,
        'name' => trim($postData['name']),
        'description' => trim($postData['description']),
        'price' => (float)$postData['price'],
        'stock' => (int)$postData['stock'],
        'aroma' => trim($postData['aroma']),
        'weight' => (int)$postData['weight'],
        'active' => isset($postData['active']) ? 1 : 0,
    ];

    $image = $postData['image_current'] ?? '';
    if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $fname = uniqid('p_') . '.' . $ext;
        if (!is_dir($cfg['upload_dir'])) mkdir($cfg['upload_dir'], 0755, true);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $cfg['upload_dir'] . $fname)) {
            $image = $fname;
        }
    }
    $data['image'] = $image;

    if ($id) {
        update_product($id, $data);
    } else {
        $id = create_product($data);
    }

    return $id;
}

/**
 * Отобразить страницу управления товарами
 * @param bool $isMod Является ли модератором
 * @param string|null $editId ID редактируемого товара или 'new'
 */
function render_products_page($isMod = false, $editId = null) {
    $products = get_all_products();
    $categories = get_all_categories();
    $edit = null;

    if ($editId) {
        if ($editId === 'new') {
            $edit = [];
        } else {
            $edit = get_product_by_id((int)$editId);
        }
    }
?>
    <h1>Товары</h1>
    <?php if (!$isMod): ?>
    <a href="?action=products&id=new" class="btn btn-primary">+ Добавить</a>
    <?php endif; ?>

    <?php if ($edit !== null && !$isMod): ?>
      <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="product_save">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
        <input type="hidden" name="image_current" value="<?= e($edit['image'] ?? '') ?>">

        <label>Название <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></label>

        <label>Категория
          <select name="category_id">
            <option value="">—</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Цена <input type="number" step="0.01" name="price" value="<?= $edit['price'] ?? 0 ?>" required></label>
        <label>Остаток <input type="number" name="stock" value="<?= $edit['stock'] ?? 0 ?>"></label>
        <label>Аромат <input type="text" name="aroma" value="<?= e($edit['aroma'] ?? '') ?>"></label>
        <label>Вес (г) <input type="number" name="weight" value="<?= $edit['weight'] ?? 0 ?>"></label>
        <label>Описание <textarea name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea></label>

        <label>Изображение
          <input type="file" name="image" accept="image/*">
          <?php if(!empty($edit['image'])): ?>
            <br><img src="<?= product_image($edit['image']) ?>" style="max-width:120px;margin-top:8px">
          <?php endif; ?>
        </label>

        <label class="checkbox"><input type="checkbox" name="active" <?= ($edit['active'] ?? 1) ? 'checked' : '' ?>> Активен</label>

        <button class="btn btn-primary">Сохранить</button>
        <a href="?action=products" class="btn btn-ghost">Отмена</a>
      </form>
    <?php endif; ?>

    <table class="admin-table">
      <thead><tr><th>ИЗО</th><th>Название</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach($products as $p): ?>
        <tr>
          <td><img src="<?= product_image($p['image']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px"></td>
          <td><?= e($p['name']) ?></td>
          <td><?= e($p['category_name'] ?? '') ?></td>
          <td><?= money($p['price']) ?></td>
          <td><?= $p['stock'] ?></td>
          <td><?= $p['active'] ? '✅' : '⏸️' ?></td>
          <td>
            <?php if (!$isMod): ?>
            <a href="?action=products&id=<?= $p['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="product_delete">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
            <?php else: ?>
            <span class="text-muted">Только просмотр</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
<?php
}