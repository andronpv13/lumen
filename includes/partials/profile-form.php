<?php
/**
 * Шаблон формы профиля пользователя
 * Использование: require __DIR__ . '/partials/profile-form.php';
 * Требуется: $user - массив данных пользователя, $formAction - URL действия формы
 */
$formAction = $formAction ?? '';
?>
<form method="post" class="admin-form" action="<?= $formAction ?>">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="action" value="profile_save">

  <div class="form-group">
    <label>Логин</label>
    <input type="text" name="name" value="<?= e($user['name']) ?>" required>
  </div>

  <div class="form-group">
    <label>Email</label>
    <input type="email" name="email" value="<?= e($user['email']) ?>" required>
  </div>

  <div class="form-group">
    <label>Телефон</label>
    <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>">
  </div>

  <div class="password-row">
    <div class="form-group">
      <label>Текущий пароль</label>
      <input type="password" name="current_password" placeholder="...">
    </div>
    <div class="form-group">
      <label>Новый пароль</label>
      <input type="password" name="password" placeholder="...">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Сохранить</button>
</form>