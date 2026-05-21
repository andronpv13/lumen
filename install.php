<?php
// install.php — автоматическая установка БД и стартовых пользователей
$cfg = require __DIR__ . '/config.php';
try {
    $pdo = new PDO("mysql:host={$cfg['db']['host']};charset=utf8mb4", $cfg['db']['user'], $cfg['db']['pass']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['db']['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$cfg['db']['dbname']}`");
    $sql = file_get_contents(__DIR__ . '/db.sql');
    // Разбиваем по ; и выполняем построчно
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
        if ($q && stripos($q,'CREATE DATABASE')===false && stripos($q,'USE ')!==0) {
            try { $pdo->exec($q); } catch (Exception $e) { /* skip */ }
        }
    }
    // Перегенерируем пароли
    $hash = fn($p) => password_hash($p, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password=? WHERE email='admin@lumen.ru'")->execute([$hash('admin123')]);
    $pdo->prepare("UPDATE users SET password=? WHERE email='moderator@lumen.ru'")->execute([$hash('mod123')]);
    $pdo->prepare("UPDATE users SET password=? WHERE email='customer@lumen.ru'")->execute([$hash('cust123')]);
    if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads', 0755, true);
    echo "<h2>✅ Установка завершена</h2><p>Админ: admin@lumen.ru / admin123<br>Модератор: moderator@lumen.ru / mod123<br>Покупатель: customer@lumen.ru / cust123</p><p><a href='/'>Перейти на сайт</a></p>";
    @unlink(__FILE__);
} catch (Exception $e) {
    echo 'Ошибка: '.$e->getMessage();
}