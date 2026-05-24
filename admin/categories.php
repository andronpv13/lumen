<?php
// admin/categories.php - Управление категориями (только админ)
require_once __DIR__ . '/../includes/repositories/CategoryRepository.php';

/**
 * Получить категорию по ID (если не загружен из functions.php)
 */
if (!function_exists('get_category_by_id')) {
    function get_category_by_id($id) {
        $db = db_get();
        $stmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id) as products_count FROM categories c WHERE c.id=?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}

/**
 * Отобразить страницу управления категориями
 * @param bool $isMod Является ли модератором (модераторы не имеют доступа)
 * @param int|null $editId ID редактируемой категории
 */
function render_categories_page($isMod = false, $editId = null) {
    if ($isMod) {
        echo '<p>Доступ запрещён</p>';
        return;
    }

    $categories = get_all_categories();
    $edit = null;

    if ($editId) {
        $edit = get_category_by_id((int)$editId);
    }
?>
    <h1>Категории</h1>

    <form method="post" class="admin-form inline">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="category_save">
      <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

      <input type="text" name="name" placeholder="Название для категории" value="<?= e($edit['name'] ?? '') ?>" required>
      <input type="text" name="slug" placeholder="Идентификатор писать латиницей" value="<?= e($edit['slug'] ?? '') ?>" required>
      <button class="btn btn-primary">Сохранить</button>
    </form>

    <table class="admin-table">
      <thead><tr><th>ID</th><th>Название</th><th>Идентификатор</th><th>Товаров</th><th></th></tr></thead>
      <tbody>
      <?php foreach($categories as $c): ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><?= e($c['name']) ?></td>
          <td><?= e($c['slug']) ?></td>
          <td><?= $c['products_count'] ?? 0 ?></td>
          <td>
            <a href="?action=categories&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="category_delete">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
<?php
}