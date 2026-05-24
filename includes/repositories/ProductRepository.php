<?php
/**
 * Репозиторий для работы с товарами
 */

/**
 * Получить все товары (для админки)
 * @return array Массив товаров
 */
function get_all_products() {
    $db = db_get();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Получить товары с фильтрацией и пагинацией
 * @param string|null $category Категория
 * @param string|null $search Поиск
 * @param int $limit Лимит
 * @param int $offset Смещение
 * @return array ['products' => [...], 'total' => int]
 */
function get_products_paginated($category = null, $search = null, $limit = 12, $offset = 0) {
    $db = db_get();
    $where = [];
    $params = [];

    if ($category) {
        $where[] = "p.category_id = ?";
        $params[] = $category;
    }

    if ($search) {
        $where[] = "(p.name LIKE ? OR p.aroma LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Получаем общее количество
    $countStmt = $db->prepare("SELECT COUNT(*) FROM products p $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Получаем товары
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id $whereClause ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['products' => $products, 'total' => $total];
}

/**
 * Создать новый товар
 * @param array $data Данные товара
 * @return int ID созданного товара
 */
function create_product($data) {
    $db = db_get();
    $stmt = $db->prepare("INSERT INTO products (name, description, price, image, aroma, stock, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['description'],
        $data['price'],
        $data['image'] ?? '',
        $data['aroma'] ?? '',
        $data['stock'] ?? 0,
        $data['category_id'] ?? null
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Обновить товар
 * @param int $id ID товара
 * @param array $data Данные для обновления
 * @return bool Успешность обновления
 */
function update_product($id, $data) {
    $db = db_get();
    $fields = [];
    $values = [];

    foreach ($data as $key => $value) {
        if (in_array($key, ['name', 'description', 'price', 'image', 'aroma', 'stock', 'category_id'])) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }

    if (empty($fields)) {
        return false;
    }

    $values[] = $id;
    $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
}

/**
 * Удалить товар
 * @param int $id ID товара
 * @return bool Успешность удаления
 */
function delete_product($id) {
    $db = db_get();
    $stmt = $db->prepare("DELETE FROM products WHERE id=?");
    return $stmt->execute([$id]);
}