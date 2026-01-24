<?php
// api.php - Основной API для магазина

require_once 'config.php';

// Настройки CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Получение данных запроса
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? ($input['action'] ?? '');

$pdo = getDBConnection();

// Маршрутизация запросов
switch ($action) {
    case 'get_products':
        getProducts();
        break;
        
    case 'get_categories':
        getCategories();
        break;
        
    case 'create_order':
        createOrder($input);
        break;
        
    case 'toggle_like':
        toggleLike($input);
        break;
        
    case 'search':
        searchProducts($_GET['q'] ?? '');
        break;
        
    case 'get_product':
        getProduct($_GET['id'] ?? 0);
        break;
        
    default:
        jsonResponse(['success' => true, 'message' => 'Danoon Shop API v1.0']);
}

// ======================
// ФУНКЦИИ API
// ======================

function getProducts() {
    global $pdo;
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $filter = $_GET['filter'] ?? 'all';
    $sort = $_GET['sort'] ?? 'new';
    $limit = ITEMS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    // Базовый запрос
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1";
    
    $params = [];
    
    // Фильтрация
    if ($filter !== 'all' && is_numeric($filter)) {
        $sql .= " AND p.category_id = ?";
        $params[] = $filter;
    }
    
    // Сортировка
    switch ($sort) {
        case 'price_asc':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'popular':
            $sql .= " ORDER BY p.likes DESC, p.views DESC";
            break;
        default:
            $sql .= " ORDER BY p.created_at DESC";
    }
    
    // Пагинация
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Выполнение запроса
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Проверяем лайки пользователя
    $telegram_user_id = $_GET['user_id'] ?? '';
    if ($telegram_user_id) {
        foreach ($products as &$product) {
            $stmt = $pdo->prepare("SELECT 1 FROM product_likes WHERE product_id = ? AND telegram_user_id = ?");
            $stmt->execute([$product['id'], $telegram_user_id]);
            $product['is_liked'] = $stmt->rowCount() > 0;
        }
    }
    
    // Проверяем, есть ли еще товары
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    $has_more = ($page * $limit) < $total;
    
    jsonResponse([
        'success' => true,
        'products' => $products,
        'page' => $page,
        'has_more' => $has_more,
        'total' => $total
    ]);
}

function getCategories() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
    $categories = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'categories' => $categories
    ]);
}

function createOrder($data) {
    global $pdo;
    
    if (empty($data['items']) || empty($data['user_id'])) {
        jsonResponse(['success' => false, 'error' => 'Неверные данные заказа']);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Создаем заказ
        $stmt = $pdo->prepare("
            INSERT INTO orders 
            (telegram_user_id, user_name, total_amount, items_json, status) 
            VALUES (?, ?, ?, ?, 'new')
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['user_name'] ?? 'Аноним',
            $data['total'],
            json_encode($
