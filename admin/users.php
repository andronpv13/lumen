<?php
// admin/users.php - Управление пользователями
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/repositories/UserRepository.php';

/**
 * Отобразить страницу управления пользователями
 * @param bool $isMod Является ли модератором (модераторы не имеют доступа)
 * @param int|null $editId ID редактируемого пользователя
 */
function render_users_page($isMod = false, $editId = null) {
    if ($isMod) {
        echo '<p>Доступ запрещён</p>';
        return;
    }

    $users = get_all_users();
    $edit = null;

    if ($editId) {
        $edit = get_user_by_id((int)$editId);
    }
?>
    <h1>Пользователи</h1>

    <?php if($edit !== null): ?>
      <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="user_save">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

        <label>Имя <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required></label>
        <label>Телефон <input type="text" name="phone" value="<?= e($edit['phone'] ?? '') ?>"></label>

        <label>Роль
          <select name="role">
            <?php foreach(['customer','moderator','admin'] as $r): ?>
              <option value="<?= $r ?>" <?= ($edit['role'] ?? 'customer') === $r ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Пароль
          <?php if($edit): ?>
            (оставьте пустым, чтобы не менять)
            <input type="password" name="password" placeholder="Новый пароль">
          <?php else: ?>
            <input type="password" name="password" required>
          <?php endif; ?>
        </label>

        <button class="btn btn-primary">Сохранить</button>
        <a href="?action=users" class="btn btn-ghost">Отмена</a>
      </form>
    <?php endif; ?>

    <table class="admin-table">
      <thead><tr><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th>Дата</th><th></th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['role']) ?></td>
          <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
          <td>
            <a href="?action=users&id=<?= $u['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
            <?php if($u['id'] != ($GLOBALS['current_user']['id'] ?? 0)): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Удалить?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="user_delete">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button class="btn btn-sm btn-ghost">🗑️</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
<?php
}