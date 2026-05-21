<?php
// includes/db.php
$cfg = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['dbname']};charset={$cfg['db']['charset']}";
try {
    $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . htmlspecialchars($e->getMessage()));
}

function db(): PDO {
    static $instance = null;
    if ($instance === null) {
        global $pdo;
        $instance = $pdo;
    }
    return $instance;
}