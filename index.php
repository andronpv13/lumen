<?php
/**
 * index.php - Единая точка входа (SPA Router)
 *
 * Все запросы проходят через этот файл.
 * Маршрут определяется через параметр $_GET['route']
 *
 * Поддержка AJAX-запросов для SPA-навигации
 *
 * Примеры URL:
 * /                    -> Главная страница (main)
 * /?route=shop         -> Каталог товаров
 * /?route=product&id=5 -> Страница товара
 * /?route=cart         -> Корзина
 * /?route=checkout     -> Оформление заказа
 * /?route=auth         -> Вход/регистрация
 * /?route=users        -> Личный кабинет
 * /?route=admin        -> Админ-панель
 */

// Начинаем сессию (если ещё не начата)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Получаем маршрут из query параметра
$route = $_GET['route'] ?? '';

// Очищаем маршрут от лишних символов для безопасности
$route = preg_replace('/[^a-zA-Z0-9_-]/', '', $route);

// Пустой маршрут = главная страница
if ($route === '') {
    $route = 'main';
}

// Карта маршрутов: маршрут => файл модуля
$routes = [
    'main'       => __DIR__ . '/modules/main.php',
    'shop'       => __DIR__ . '/modules/shop.php',
    'product'    => __DIR__ . '/modules/product.php',
    'cart'       => __DIR__ . '/modules/cart.php',
    'checkout'   => __DIR__ . '/modules/checkout.php',
    'auth'       => __DIR__ . '/modules/auth.php',
    'users'      => __DIR__ . '/modules/users.php',
    'admin'      => __DIR__ . '/modules/admin.php',
    'moderator'  => __DIR__ . '/modules/moderator.php',
];

// Проверяем существование маршрута
if (!array_key_exists($route, $routes)) {
    // Для AJAX-запросов возвращаем JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Страница не найдена', 'code' => 404]);
        exit;
    }
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>404 - Страница не найдена</title></head><body>';
    echo '<h1>404 - Страница не найдена</h1>';
    echo '<p>Запрашиваемая страница не существует.</p>';
    echo '<a href="/">Вернуться на главную</a>';
    echo '</body></html>';
    exit;
}

// Проверяем существование файла модуля
$moduleFile = $routes[$route];
if (!file_exists($moduleFile)) {
    // Для AJAX-запросов возвращаем JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Модуль не найден', 'code' => 500]);
        exit;
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>500 - Ошибка сервера</title></head><body>';
    echo '<h1>500 - Ошибка сервера</h1>';
    echo '<p>Модуль страницы не найден.</p>';
    echo '<a href="/">Вернуться на главную</a>';
    echo '</body></html>';
    exit;
}

// Определяем тип запроса: AJAX или обычный
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax']);

if ($isAjax) {
    // AJAX-запрос: подключаем модуль и возвращаем только контент
    header('Content-Type: text/html; charset=utf-8');
    require $moduleFile;
} else {
    // Обычный запрос: подключаем header, затем модуль, затем footer
    require __DIR__ . '/includes/header.php';
    require $moduleFile;
    require __DIR__ . '/includes/footer.php';
}