<?php
// includes/functions.php
session_start();
require_once __DIR__ . '/db.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function setting(string $key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $rows = db()->query("SELECT `key`, value FROM settings")->fetchAll();
        $cache = [];
        foreach ($rows as $r) $cache[$r['key']] = $r['value'];
    }
    return $cache[$key] ?? $default;
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Войдите в аккаунт'];
        header('Location: /auth.php'); exit;
    }
}

function require_role($roles) {
    require_login();
    $roles = (array)$roles;
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        die('Доступ запрещён');
    }
}

function flash($msg = null, $type = 'info') {
    if ($msg === null) {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
    $_SESSION['flash'] = ['type'=>$type,'msg'=>$msg];
}

function cart_count() {
    $c = $_SESSION['cart'] ?? [];
    return array_sum($c);
}

function cart_items() {
    $ids = array_keys($_SESSION['cart'] ?? []);
    if (!$ids) return [];
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE id IN ($ph) AND active=1");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    $out = [];
    foreach ($rows as $p) {
        $p['qty'] = $_SESSION['cart'][$p['id']];
        $out[] = $p;
    }
    return $out;
}

function cart_total() {
    $total = 0;
    foreach (cart_items() as $it) $total += $it['price'] * $it['qty'];
    return $total;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Неверный CSRF токен');
    }
}

function product_image($img) {
    if (!$img) return 'assets/placeholder.svg';
    if (file_exists(__DIR__ . '/../uploads/' . $img)) return 'uploads/' . $img;
    return 'assets/placeholder.svg';
}

function redirect($url) { header('Location: ' . $url); exit; }

function money($v) {
    return number_format((float)$v, 0, ',', ' ') . ' ' . setting('currency', '₽');
}