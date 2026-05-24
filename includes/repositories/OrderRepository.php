<?php
/**
 * Репозиторий для работы с заказами
 */

/**
 * Получить заказы пользователя
 * @param int $userId ID пользователя
 * @return array Массив заказов
 */
if (!function_exists('get_orders_by_user')) {
function get_orders_by_user($userId) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

// Функции уже определены в includes/functions.php, здесь только уникальные функции репозитория

/**
 * Получить заказ по ID
 * @param int $id ID заказа
 * @return array|null Данные заказа или null
 */
if (!function_exists('get_order_by_id')) {
function get_order_by_id($id) {
    $db = db();
    $stmt = $db->prepare("SELECT o.*, u.name as user_login FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}
}

/**
 * Получить элементы заказа
 * @param int $orderId ID заказа
 * @return array Массив элементов
 */
if (!function_exists('get_order_items')) {
function get_order_items($orderId) {
    $db = db();
    $stmt = $db->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

/**
 * Получить все заказы (для админки)
 * @param string|null $status Фильтр по статусу
 * @return array Массив заказов
 */
if (!function_exists('get_all_orders')) {
function get_all_orders($status = null) {
    $db = db();
    if ($status) {
        $stmt = $db->prepare("SELECT o.*, u.name as user_login FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.status=? ORDER BY o.created_at DESC");
        $stmt->execute([$status]);
    } else {
        $stmt = $db->prepare("SELECT o.*, u.name as user_login FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC");
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

/**
 * Обновить статус заказа
 * @param int $id ID заказа
 * @param string $status Новый статус
 * @return bool Успешность обновления
 */
if (!function_exists('update_order_status')) {
function update_order_status($id, $status) {
    $db = db();
    $stmt = $db->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?");
    return $stmt->execute([$status, $id]);
}
}

/**
 * Обновить статус оплаты заказа
 * @param int $id ID заказа
 * @param string $paymentStatus Статус оплаты
 * @return bool Успешность обновления
 */
if (!function_exists('update_order_payment_status')) {
function update_order_payment_status($id, $paymentStatus) {
    $db = db();
    $stmt = $db->prepare("UPDATE orders SET payment_status=?, updated_at=NOW() WHERE id=?");
    return $stmt->execute([$paymentStatus, $id]);
}
}