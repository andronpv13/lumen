<?php
// admin/settings.php - Настройки магазина (только админ)

/**
 * Отобразить страницу настроек магазина
 * @param bool $isMod Является ли модератором (модераторы не имеют доступа)
 */
function render_settings_page($isMod = false) {
    if ($isMod) {
        echo '<p>Доступ запрещён</p>';
        return;
    }
?>
    <h1>Настройки магазина</h1>
    <form method="post" class="admin-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="settings_save">

      <label>Название магазина
        <input type="text" name="shop_name" value="<?= e(setting('shop_name')) ?>">
      </label>

      <label>Телефон магазина
        <input type="text" name="shop_phone" value="<?= e(setting('shop_phone')) ?>">
      </label>

      <label>Email магазина
        <input type="email" name="shop_email" value="<?= e(setting('shop_email')) ?>">
      </label>

      <label>Адрес магазина
        <input type="text" name="shop_address" value="<?= e(setting('shop_address')) ?>">
      </label>

      <div class="form-row">
        <label>Стоимость доставки до транспортной
          <input type="number" step="0.01" name="delivery_price" value="<?= e(setting('delivery_price')) ?>">
        </label>

        <label>Валюта
          <input type="text" name="currency" value="<?= e(setting('currency')) ?>">
        </label>
      </div>

      <button class="btn btn-primary">Сохранить</button>
    </form>
<?php
}