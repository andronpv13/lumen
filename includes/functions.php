<?php
/**
 * Вспомогательные функции приложения
 */

/**
 * Экранирование вывода для защиты от XSS
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Получение настройки из БД с кэшированием
 */
function setting(string $key, mixed $default = null): mixed {
    static $cache = null;
    
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query("SELECT `key`, `value` FROM settings")->fetchAll();
            foreach ($rows as $r) {
                $cache[$r['key']] = $r['value'];
            }
        } catch (PDOException $e) {
            error_log('Settings load error: ' . $e->getMessage());
            return $default;
        }
    }
    
    return $cache[$key] ?? $default;
}

/**
 * Текущий авторизованный пользователь
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Требует авторизации, перенаправляет на вход если не авторизован
 */
function require_login(): void {
    if (!current_user()) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Войдите в аккаунт'];
        redirect('/?route=auth');
    }
}

/**
 * Требует определённую роль пользователя
 * @param string|array $roles Одна роль или массив ролей
 */
function require_role(string|array $roles): void {
    require_login();
    $roles = (array) $roles;
    $user = current_user();
    if (!in_array($user['role'] ?? '', $roles, true)) {
        http_response_code(403);
        die('Доступ запрещён');
    }
}

/**
 * Работа с флеш-сообщениями
 */
function flash(?string $msg = null, string $type = 'info'): ?array {
    if ($msg === null) {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    return null;
}

/**
 * Количество товаров в корзине
 */
function cart_count(): int {
    $c = $_SESSION['cart'] ?? [];
    return array_sum($c);
}

/**
 * Элементы корзины с данными из БД
 * @return array<array>
 */
function cart_items(): array {
    $cart = $_SESSION['cart'] ?? [];
    $ids = array_keys($cart);
    
    if (!$ids) {
        return [];
    }
    
    // Безопасное формирование плейсхолдеров
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND active=1");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    
    $out = [];
    foreach ($rows as $p) {
        $p['qty'] = $cart[$p['id']] ?? 1;
        $out[] = $p;
    }
    return $out;
}

/**
 * Общая сумма корзины
 */
function cart_total(): float {
    $total = 0.0;
    foreach (cart_items() as $it) {
        $total += (float)$it['price'] * (int)$it['qty'];
    }
    return $total;
}

/**
 * Генерация/получение CSRF-токена
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/**
 * Проверка CSRF-токена
 */
function csrf_check(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Неверный CSRF токен']);
            exit;
        }
        die('Неверный CSRF токен');
    }
}

/**
 * Получение пути к изображению товара
 */
function product_image(?string $img): string {
    if (!$img) {
        return '/assets/placeholder.svg';
    }
    
    $uploadDir = rtrim(__DIR__ . '/../uploads', '/');
    $safeImg = basename($img); // Защита от path traversal
    
    if (file_exists($uploadDir . '/' . $safeImg)) {
        return '/uploads/' . $safeImg;
    }
    
    return '/assets/placeholder.svg';
}

/**
 * Редирект с завершением скрипта
 */
function redirect(string $url): void {
    // Очистка буфера перед редиректом
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Location: ' . $url, true, 302);
    exit;
}

/**
 * Форматирование денежной суммы
 */
function money(float|int $value): string {
    return number_format((float)$value, 0, ',', ' ') . ' ' . setting('currency', '₽');
}

/**
 * Метки статусов заказов
 * @return array<string, string>
 */
function get_order_status_labels(): array {
    return [
        'new' => 'Новый',
        'processing' => 'В обработке',
        'paid' => 'Оплачен',
        'shipped' => 'Отправлен',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменён',
    ];
}

/**
 * Валидация пароля
 * @return array{valid: bool, errors: array<string>}
 */
function validate_password(string $password): array {
    $errors = [];

    if (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    // Исправлено: проверка на пробелы (была ошибка в регекспе /[st]/)
    if (preg_match('/\s/', $password)) {
        $errors[] = 'Пароль не должен содержать пробелы';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

/**
 * Хеширование пароля
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Проверка пароля
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Элементы заказа
 * @return array<array>
 */
function get_order_items(int $orderId): array {
    $stmt = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/**
 * Получение пользователя по ID с типизацией
 */
function get_user_by_id(int $id): ?array {
    $stmt = db()->prepare("SELECT id, email, name, role, phone, address, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Безопасное получение параметра из $_GET
 */
function get_param(string $key, mixed $default = null): mixed {
    return $_GET[$key] ?? $default;
}

/**
 * Безопасное получение параметра из $_POST
 */
function post_param(string $key, mixed $default = null): mixed {
    return $_POST[$key] ?? $default;
}

/**
 * Валидация целого числа из параметров
 */
function get_int_param(string $key, int $default = 0, int $min = null, int $max = null): int {
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
    
    if ($min !== null && $value < $min) {
        return $min;
    }
    if ($max !== null && $value > $max) {
        return $max;
    }
    
    return $value;
}