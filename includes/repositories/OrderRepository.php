<?php
/**
 * Репозиторий для работы с заказами
 */

class OrderRepository {

    /**
     * Получить заказы пользователя
     * @param int $userId ID пользователя
     * @return array Массив заказов
     */
    public static function getByUser($userId) {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получить заказ по ID
     * @param int $id ID заказа
     * @return array|null Данные заказа или null
     */
    public static function getById($id) {
        $db = db();
        $stmt = $db->prepare("SELECT o.*, u.name as user_login FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Получить все заказы (для админки)
     * @param string|null $status Фильтр по статусу
     * @return array Массив заказов
     */
    public static function getAll($status = null) {
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

    /**
     * Обновить статус заказа
     * @param int $id ID заказа
     * @param string $status Новый статус
     * @return bool Успешность обновления
     */
    public static function updateStatus($id, $status) {
        $db = db();
        $stmt = $db->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Обновить статус оплаты заказа
     * @param int $id ID заказа
     * @param string $paymentStatus Статус оплаты
     * @return bool Успешность обновления
     */
    public static function updatePaymentStatus($id, $paymentStatus) {
        $db = db();
        $stmt = $db->prepare("UPDATE orders SET payment_status=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$paymentStatus, $id]);
    }
}