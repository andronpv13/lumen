<?php
/**
 * Репозиторий для работы с отзывами
 */

/**
 * Получить отзывы пользователя
 * @param int $userId ID пользователя
 * @return array Массив отзывов
 */
function get_reviews_by_user($userId) {
    $db = db_get();
    $stmt = $db->prepare("SELECT r.*, p.name as product_name FROM reviews r JOIN products p ON r.product_id=p.id WHERE r.user_id=? ORDER BY r.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получить отзывы товара
 * @param int $productId ID товара
 * @return array Массив отзывов
 */
function get_reviews_by_product($productId) {
    $db = db_get();
    $stmt = $db->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.product_id=? AND r.approved=1 ORDER BY r.created_at DESC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получить все отзывы (для админки/модерации)
 * @param bool|null $approved Фильтр по статусу модерации
 * @return array Массив отзывов
 */
function get_all_reviews($approved = null) {
    $db = db_get();
    if ($approved === null) {
        $stmt = $db->prepare("SELECT r.*, u.name as user_name, p.name as product_name FROM reviews r JOIN users u ON r.user_id=u.id JOIN products p ON r.product_id=p.id ORDER BY r.created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT r.*, u.name as user_name, p.name as product_name FROM reviews r JOIN users u ON r.user_id=u.id JOIN products p ON r.product_id=p.id WHERE r.approved=? ORDER BY r.created_at DESC");
        $stmt->execute([$approved]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Создать новый отзыв
 * @param array $data Данные отзыва
 * @return int ID созданного отзыва
 */
function create_review($data) {
    $db = db_get();
    $stmt = $db->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, approved) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['user_id'],
        $data['product_id'],
        $data['rating'],
        $data['comment'],
        $data['approved'] ?? 0
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Обновить отзыв (модерация)
 * @param int $id ID отзыва
 * @param bool $approved Статус модерации
 * @return bool Успешность обновления
 */
function update_review_approval($id, $approved) {
    $db = db_get();
    $stmt = $db->prepare("UPDATE reviews SET approved=?, updated_at=NOW() WHERE id=?");
    return $stmt->execute([$approved ? 1 : 0, $id]);
}

/**
 * Удалить отзыв
 * @param int $id ID отзыва
 * @return bool Успешность удаления
 */
function delete_review($id) {
    $db = db_get();
    $stmt = $db->prepare("DELETE FROM reviews WHERE id=?");
    return $stmt->execute([$id]);
}

/**
 * Получить количество отзывов пользователя
 * @param int $userId ID пользователя
 * @return int Количество отзывов
 */
function get_user_reviews_count($userId) {
    $db = db_get();
    $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id=?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}