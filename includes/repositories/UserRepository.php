<?php
/**
 * Репозиторий для работы с пользователями
 */

/**
 * Получить пользователя по ID
 * @param int $id ID пользователя
 * @return array|null Данные пользователя или null
 */
function get_user_by_id($id) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Получить пользователя по email
 * @param string $email Email
 * @return array|null Данные пользователя или null
 */
function get_user_by_email($email) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Получить всех пользователей (для админки)
 * @return array Массив пользователей
 */
function get_all_users() {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Создать нового пользователя
 * @param array $data Данные пользователя
 * @return int ID созданного пользователя
 */
function create_user($data) {
    $db = db();
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['email'],
        $data['password'],
        $data['role'] ?? 'user',
        $data['phone'] ?? null
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Обновить пользователя
 * @param int $id ID пользователя
 * @param array $data Данные для обновления
 * @return bool Успешность обновления
 */
function update_user($id, $data) {
    $db = db();
    $fields = [];
    $values = [];

    foreach ($data as $key => $value) {
        if (in_array($key, ['name', 'email', 'password', 'role', 'phone'])) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }

    if (empty($fields)) {
        return false;
    }

    $values[] = $id;
    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
}

/**
 * Удалить пользователя
 * @param int $id ID пользователя
 * @return bool Успешность удаления
 */
function delete_user($id) {
    $db = db();
    $stmt = $db->prepare("DELETE FROM users WHERE id=?");
    return $stmt->execute([$id]);
}

/**
 * Получить количество заказов пользователя
 * @param int $userId ID пользователя
 * @return int Количество заказов
 */
function get_user_orders_count($userId) {
    $db = db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}