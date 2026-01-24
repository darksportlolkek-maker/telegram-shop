<?php
require_once 'config.php';
require_once 'Database.php';

// Получаем действие из запроса
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Обработка разных действий
switch ($action) {
    case 'get_categories':
        getCategories();
        break;
    case 'get_products':
        getProducts();
        break;
    case 'get_product':
        getProduct();
        break;
    case 'create_order':
        createOrder();
        break;
    case 'check_admin':
        checkAdmin();
        break;
    case 'like_product':
        likeProduct();
        break;
    case 'get_orders':
        getOrders();
        break;
    default:
        sendError('Invalid action');
}

// Получение категорий
function getCategories() {
    $sql = "SELECT id, name, slug, description, image_url, sort_order 
            FROM categories 
            WHERE is_active = 1 
            ORDER BY sort_order, name";
    
    $categories = Database::fetchAll($sql);
    
    // Добавляем "Все товары" как первую категорию
    array_unshift($categories, [
        'id' => 'all',
        'name' => 'Все товары',
        'slug' => 'all',
        'description' => 'Все товары магазина',
        'sort_order' => 0
    ]);
    
    sendSuccess(['categories' => $categories]);
}

// Получение товаров
function getProducts() {
    $category_id = $_GET['category_id'] ?? 'all';
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    $sql = "SELECT 
                p.*,
                c.name as category_name,
                c.slug as category_slug,
                ROUND(p.price * (1 - p.discount/100), 2) as final_price
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1";
    
    $params = [];
    
    if ($category_id !== 'all') {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }
    
    $sql .= " ORDER BY p.is_featured DESC, p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;
    
    $products = Database::fetchAll($sql, $params);
    
    // Добавляем URL для изображений
    foreach ($products as &$product) {
        if (empty($product['image_url'])) {
            $product['image_url'] = 'https://via.placeholder.com/600x400/0088cc/ffffff?text=' . urlencode($product['name']);
        }
    }
    
    sendSuccess(['products' => $products]);
}

// Получение одного товара
function getProduct() {
    $id = $_GET['id'] ?? 0;
    $slug = $_GET['slug'] ?? '';
    
    if (empty($id) && empty($slug)) {
        sendError('Product ID or slug required');
    }
    
    $sql = "SELECT 
                p.*,
                c.name as category_name,
                c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1 AND ";
    
    $params = [];
    
    if (!empty($id)) {
        $sql .= "p.id = ?";
        $params[] = $id;
    } else {
        $sql .= "p.slug = ?";
        $params[] = $slug;
    }
    
    $product = Database::fetchOne($sql, $params);
    
    if (!$product) {
        sendError('Product not found', 404);
    }
    
    // Увеличиваем счетчик просмотров
    $telegram_user_id = $_GET['telegram_user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    Database::query(
        "INSERT INTO product_views (product_id, telegram_user_id, ip_address, user_agent) 
         VALUES (?, ?, ?, ?)",
        [$product['id'], $telegram_user_id, $ip_address, $user_agent]
    );
    
    // Обновляем счетчик в таблице продуктов
    Database::query(
        "UPDATE products SET views = views + 1 WHERE id = ?",
        [$product['id']]
    );
    
    sendSuccess(['product' => $product]);
}

// Создание заказа
function createOrder() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data)) {
        sendError('No order data received');
    }
    
    // Проверка обязательных полей
    $required = ['user', 'cart', 'total'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            sendError("Missing required field: $field");
        }
    }
    
    $user = $data['user'];
    $cart = $data['cart'];
    $total = $data['total'];
    
    // Сохраняем заказ в БД
    try {
        $orderId = Database::query(
            "INSERT INTO orders 
                (telegram_user_id, user_name, total_amount, items_json, status) 
             VALUES (?, ?, ?, ?, 'new')",
            [
                $user['id'] ?? null,
                $user['name'] ?? 'Аноним',
                $total,
                json_encode($cart, JSON_UNESCAPED_UNICODE)
            ]
        )->rowCount();
        
        // Обновляем количество товаров на складе
        foreach ($cart as $item) {
            Database::query(
                "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?",
                [$item['quantity'], $item['id'], $item['quantity']]
            );
        }
        
        sendSuccess(['order_id' => $orderId, 'message' => 'Order created successfully']);
        
    } catch (Exception $e) {
        sendError('Failed to create order: ' . $e->getMessage(), 500);
    }
}

// Проверка администратора
function checkAdmin() {
    $telegram_id = $_GET['telegram_id'] ?? '';
    
    if (empty($telegram_id)) {
        sendSuccess(['is_admin' => false]);
    }
    
    $admin = Database::fetchOne(
        "SELECT id, name, role FROM admins WHERE telegram_id = ? AND is_active = 1",
        [$telegram_id]
    );
    
    sendSuccess(['is_admin' => !empty($admin), 'admin' => $admin]);
}

// Лайк товара
function likeProduct() {
    $product_id = $_POST['product_id'] ?? 0;
    $telegram_user_id = $_POST['telegram_user_id'] ?? '';
    
    if (empty($product_id) || empty($telegram_user_id)) {
        sendError('Product ID and user ID required');
    }
    
    try {
        // Пытаемся добавить лайк
        Database::query(
            "INSERT INTO product_likes (product_id, telegram_user_id) VALUES (?, ?)",
            [$product_id, $telegram_user_id]
        );
        
        // Увеличиваем счетчик лайков в таблице продуктов
        Database::query(
            "UPDATE products SET likes = likes + 1 WHERE id = ?",
            [$product_id]
        );
        
        $likes = Database::fetchOne(
            "SELECT likes FROM products WHERE id = ?",
            [$product_id]
        );
        
        sendSuccess(['liked' => true, 'likes' => $likes['likes'] ?? 0]);
        
    } catch (PDOException $e) {
        // Если уже лайкнул - удаляем лайк
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            Database::query(
                "DELETE FROM product_likes WHERE product_id = ? AND telegram_user_id = ?",
                [$product_id, $telegram_user_id]
            );
            
            Database::query(
                "UPDATE products SET likes = likes - 1 WHERE id = ? AND likes > 0",
                [$product_id]
            );
            
            $likes = Database::fetchOne(
                "SELECT likes FROM products WHERE id = ?",
                [$product_id]
            );
            
            sendSuccess(['liked' => false, 'likes' => $likes['likes'] ?? 0]);
        } else {
            sendError('Failed to like product: ' . $e->getMessage(), 500);
        }
    }
}

// Получение заказов (только для админов)
function getOrders() {
    // Проверка админа
    $telegram_id = $_GET['telegram_id'] ?? '';
    $admin = Database::fetchOne(
        "SELECT id FROM admins WHERE telegram_id = ? AND is_active = 1",
        [$telegram_id]
    );
    
    if (!$admin) {
        sendError('Access denied', 403);
    }
    
    $status = $_GET['status'] ?? 'new';
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];
    
    if ($status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;
    
    $orders = Database::fetchAll($sql, $params);
    
    // Декодируем JSON с товарами
    foreach ($orders as &$order) {
        $order['items'] = json_decode($order['items_json'], true);
        unset($order['items_json']);
    }
    
    sendSuccess(['orders' => $orders]);
}
?>
