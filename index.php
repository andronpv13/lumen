<?php
/**
 * Единая точка входа (фронт-контроллер) для SPA
 * 
 * Маршруты:
 * /                        → Главная
 * /?route=shop             → Каталог
 * /?route=product&id=5     → Товар
 * /?route=cart             → Корзина
 * /?route=checkout         → Оформление
 * /?route=auth             → Авторизация
 * /?route=users            → Личный кабинет
 * /?route=admin            → Админ-панель
 */

// Начинаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загрузка конфигурации
$config = require __DIR__ . '/config.php';

// Получаем и очищаем маршрут
$route = trim($_GET['route'] ?? '');

// БЕЗОПАСНОСТЬ: экранируем дефис в символьном классе и добавляем флаги
$route = preg_replace('/[^a-z0-9_\-]/i', '', $route);

// Маршрут по умолчанию
if ($route === '') {
    $route = 'main';
}

// Карта маршрутов
$routes = [
    'main' => __DIR__ . '/modules/main.php',
    'shop' => __DIR__ . '/modules/shop.php',
    'product' => __DIR__ . '/modules/product.php',
    'cart' => __DIR__ . '/modules/cart.php',
    'checkout' => __DIR__ . '/modules/checkout.php',
    'auth' => __DIR__ . '/modules/auth.php',
    'users' => __DIR__ . '/modules/users.php',
    'admin' => __DIR__ . '/modules/admin.php',
    'moderator' => __DIR__ . '/modules/moderator.php',
    '404' => __DIR__ . '/modules/404.php',
];

// Проверка существования маршрута
if (!array_key_exists($route, $routes)) {
    $route = '404';
}

$moduleFile = $routes[$route];

// Проверка существования файла модуля
if (!file_exists($moduleFile)) {
    http_response_code(500);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Модуль не найден', 'code' => 500]);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/modules/404.php';
    }
    exit;
}

// Определение AJAX-запроса
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax']);

// Обработка запроса
if ($isAjax) {
    // AJAX: только контент модуля
    header('Content-Type: text/html; charset=utf-8');
    require $moduleFile;
} else {
    // Обычный запрос: полная страница с хедером и футером
    require __DIR__ . '/includes/header.php';
    require $moduleFile;
    require __DIR__ . '/includes/footer.php';
}