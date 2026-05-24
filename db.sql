CREATE DATABASE IF NOT EXISTS lumen_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lumen_shop;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30),
    address TEXT,
    role ENUM('admin','moderator','customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Поля доставки пользователя
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

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    aroma VARCHAR(100),
    weight INT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('new','processing','paid','shipped','delivered','cancelled') DEFAULT 'new',
    payment_method VARCHAR(50),
    payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
    shipping_name VARCHAR(100),
    shipping_phone VARCHAR(30),
    shipping_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    name VARCHAR(200),
    price DECIMAL(10,2),
    quantity INT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    value TEXT
);

-- Стартовые данные
INSERT INTO settings (`key`, value) VALUES
('shop_name','Lumen — восковые свечи ручной работы'),
('shop_phone','+7 (900) 000-00-00'),
('shop_email','hello@lumen.ru'),
('shop_address','Москва, ул. Примерная, 1'),
('currency','₽'),
('delivery_price','300.00');

INSERT INTO categories (name, slug) VALUES
('Ароматические','aromatic'),
('Декоративные','decorative'),
('Подарочные наборы','gift-sets'),
('Без аромата','unscented');

INSERT INTO users (email, password, name, role) VALUES
('admin@lumen.ru','$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy','Администратор','admin'),
('moderator@lumen.ru','$2y$10$wH8xQk5YqZxJ6kXq6qJqZeXq6qJqZeXq6qJqZeXq6qJqZeXq6qJqZ','Модератор','moderator'),
('customer@lumen.ru','$2y$10$wH8xQk5YqZxJ6kXq6qJqZeXq6qJqZeXq6qJqZeXq6qJqZeXq6qJqZ','Покупатель','customer');
-- Пароли: admin123, mod123, cust123 (перегенерируйте через install.php)

INSERT INTO products (category_id, name, description, price, stock, aroma, weight, image) VALUES
(1,'Ваниль и сандал','Тёплые ноты ванили, переплетённые с дымным сандалом. Соевый воск, хлопковый фитиль.',890.00,25,'Ваниль, сандал',180,'vanilla.jpg'),
(1,'Лаванда','Расслабляющий аромат прованских полей. Идеально для вечернего ритуала.',750.00,30,'Лаванда',150,'lavender.jpg'),
(1,'Хвоя и можжевельник','Свежий аромат зимнего леса. Кедр, можжевельник, еловая смола.',950.00,15,'Хвоя',200,'pine.jpg'),
(2,'Свеча «Роза»','Декоративная свеча в форме бутона. Натуральный пчелиный воск.',650.00,40,'Без аромата',120,'rose.jpg'),
(2,'Свеча «Сфера»','Минималистичная форма, матовая поверхность. 4 цвета на выбор.',550.00,50,'Без аромата',140,'sphere.jpg'),
(3,'Подарочный набор «Уют»','3 свечи: ваниль, корица, апельсин. Крафтовая упаковка.',2400.00,10,'Микс',500,'gift-cozy.jpg'),
(3,'Подарочный набор «Свежесть»','3 свечи: эвкалипт, мята, лимон. В подарочной коробке.',2400.00,12,'Микс',500,'gift-fresh.jpg'),
(4,'Чистая свеча','Классическая свеча без аромата из соевого воска.',450.00,60,'Без аромата',180,'pure.jpg');