-- Миграция: добавление таблицы профилей доставки
CREATE TABLE IF NOT EXISTS user_delivery_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    middle_name VARCHAR(100),
    postal_code VARCHAR(20),
    region VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100),
    street VARCHAR(150),
    building VARCHAR(20),
    apartment VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);