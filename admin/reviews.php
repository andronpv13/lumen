<?php
// admin/reviews.php - Управление отзывами (модерация)
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repositories/ReviewRepository.php';

/**
 * Отобразить страницу модерации отзывов
 * @param bool $isMod Является ли модератором
 */
function render_reviews_page($isMod = false) {
    $reviews = get_all_reviews();
?>
    <h1>Отзывы</h1>
    <table class="admin-table">
      <thead><tr><th>Товар</th><th>Автор</th><th>Оценка</th><th>Текст</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach($reviews as $r): ?>
        <tr>
          <td><?= e($r['product_name']) ?></td>
          <td><?= e($r['user_name']) ?></td>
          <td><?= str_repeat('★', $r['rating']) ?></td>
          <td><?= e(mb_substr($r['comment'], 0, 80)) ?>...</td>
          <td><?= $r['approved'] ? '✅' : '⏳' ?></td>
          <td>
            <?php if(!$r['approved']): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="review_approve">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-primary">Одобрить</button>
            </form>
            <?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="review_delete">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
<?php
}