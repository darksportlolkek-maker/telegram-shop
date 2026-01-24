<?php
require_once 'config.php';

$sql = file_get_contents('products.sql'); // если файл есть
// или вставьте SQL код напрямую

try {
    $pdo->exec($sql);
    echo "✅ Таблицы созданы успешно!<br>";
    
    // Проверяем
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Создано таблиц: " . count($tables) . "<br>";
    echo implode(', ', $tables);
    
} catch(PDOException $e) {
    die("❌ Ошибка: " . $e->getMessage());
}
?>
