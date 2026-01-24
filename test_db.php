<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'danoon_shop');      
define('DB_USER', 'root'); 
define('DB_PASS', 'Daniel2006');    
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Успешное подключение!<br>";
    
    // Проверяем товары
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    
    echo "Товаров в БД: " . $result['count'] . "<br>";
    
    // Выводим первые 3 товара
    $stmt = $pdo->query("SELECT id, name, price FROM products LIMIT 3");
    $products = $stmt->fetchAll();
    
    echo "<pre>";
    print_r($products);
    
} catch(PDOException $e) {
    die("❌ Ошибка подключения: " . $e->getMessage());
}
?>
