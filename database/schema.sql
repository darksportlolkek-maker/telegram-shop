CREATE DATABASE danoon_shop CHARACTER SET utf8mb4;
USE danoon_shop;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price INT NOT NULL,
    image VARCHAR(500),
    category VARCHAR(100),
    description TEXT,
    stock INT DEFAULT 0,
    rating FLOAT DEFAULT 5
);

INSERT INTO products (name, price, image, category, description, stock, rating) VALUES
('iPhone 15 Pro', 89990, 'https://via.placeholder.com/600x400/0088cc/ffffff?text=iPhone+15+Pro', '📱 Электроника', 'Новый iPhone с камерой 48MP', 10, 4.9),
('Nike Air Max 270', 12990, 'https://via.placeholder.com/600x400/0088cc/ffffff?text=Nike+Air+Max', '👟 Обувь', 'Легендарные кроссовки', 20, 4.7);
