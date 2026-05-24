<?php
/**
 * Репозиторий для работы с категориями
 */

/**
 * Получить категорию по ID
 * @param int $id ID категории
 * @return array|null Данные категории или null
 */
function get_category_by_id($id) {
    $db = db_get();
    $stmt = $db->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Получить все категории
 * @return array Массив категорий
 */
function get_all_categories() {
    $db = db_get();
    $stmt = $db->prepare("SELECT c.*, COUNT(p.id) as products_count FROM categories c LEFT JOIN products p ON c.id=p.category_id GROUP BY c.id ORDER BY c.name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Создать новую категорию
 * @param array $data Данные категории
 * @return int ID созданной категории
 */
function create_category($data) {
    $db = db_get();
    $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute([
        $data['name'],
        $data['description'] ?? ''
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Обновить категорию
 * @param int $id ID категории
 * @param array $data Данные для обновления
 * @return bool Успешность обновления
 */
function update_category($id, $data) {
    $db = db_get();
    $fields = [];
    $values = [];

    foreach ($data as $key => $value) {
        if (in_array($key, ['name', 'description'])) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }

    if (empty($fields)) {
        return false;
    }

    $values[] = $id;
    $sql = "UPDATE categories SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
}

/**
 * Удалить категорию
 * @param int $id ID категории
 * @return bool Успешность удаления
 */
function delete_category($id) {
    $db = db_get();
    $stmt = $db->prepare("DELETE FROM categories WHERE id=?");
    return $stmt->execute([$id]);
}