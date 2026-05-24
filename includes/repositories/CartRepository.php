<?php
/**
 * Репозиторий для работы с корзиной
 */

/**
 * Получить элементы корзины пользователя
 * @param int $userId ID пользователя
 * @return array Массив элементов корзины
 */
function get_cart_items($userId) {
    $db = db_get();
    $stmt = $db->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Добавить товар в корзину
 * @param int $userId ID пользователя
 * @param int $productId ID товара
 * @param int $quantity Количество
 * @return bool Успешность добавления
 */
function add_to_cart($userId, $productId, $quantity = 1) {
    $db = db_get();

    // Проверяем, есть ли уже такой товар в корзине
    $stmt = $db->prepare("SELECT * FROM cart WHERE user_id=? AND product_id=?");
    $stmt->execute([$userId, $productId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Обновляем количество
        $stmt = $db->prepare("UPDATE cart SET quantity=quantity+? WHERE user_id=? AND product_id=?");
        return $stmt->execute([$quantity, $userId, $productId]);
    } else {
        // Добавляем новый элемент
        $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $productId, $quantity]);
    }
}

/**
 * Обновить количество товара в корзине
 * @param int $userId ID пользователя
 * @param int $productId ID товара
 * @param int $quantity Новое количество
 * @return bool Успешность обновления
 */
function update_cart_item($userId, $productId, $quantity) {
    $db = db_get();
    if ($quantity <= 0) {
        return remove_from_cart($userId, $productId);
    }
    $stmt = $db->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?");
    return $stmt->execute([$quantity, $userId, $productId]);
}

/**
 * Удалить товар из корзины
 * @param int $userId ID пользователя
 * @param int $productId ID товара
 * @return bool Успешность удаления
 */
function remove_from_cart($userId, $productId) {
    $db = db_get();
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?");
    return $stmt->execute([$userId, $productId]);
}

/**
 * Очистить корзину пользователя
 * @param int $userId ID пользователя
 * @return bool Успешность очистки
 */
function clear_cart($userId) {
    $db = db_get();
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id=?");
    return $stmt->execute([$userId]);
}

/**
 * Получить количество товаров в корзине
 * @param int $userId ID пользователя
 * @return int Количество товаров
 */
function get_cart_count($userId) {
    $db = db_get();
    $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id=?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Получить сумму корзины
 * @param int $userId ID пользователя
 * @return float Сумма корзины
 */
function get_cart_total($userId) {
    $db = db_get();
    $stmt = $db->prepare("SELECT COALESCE(SUM(c.quantity * p.price), 0) FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}