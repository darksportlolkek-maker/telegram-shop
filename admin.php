<?php
// admin.php - Админ-панель магазина

require_once 'config.php';

session_start();

// Проверка авторизации через Telegram
$telegram_user = $_GET['user'] ?? $_SESSION['admin_user'] ?? null;

if (!$telegram_user || $telegram_user['id'] != ADMIN_TELEGRAM_ID) {
    die('<h2>⚠️ Доступ запрещен</h2><p>Только администратор может просматривать эту страницу.</p>');
}

$_SESSION['admin_user'] = $telegram_user;
$pdo = getDBConnection();

// Обработка действий
$action = $_GET['action'] ?? '';
$product_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch ($action) {
        case 'add_product':
            addProduct($_POST);
            break;
        case 'update_product':
            updateProduct($_POST);
            break;
        case 'delete_product':
            deleteProduct($product_id);
            break;
        case 'update_order_status':
            updateOrderStatus($_POST);
            break;
    }
}

// ======================
// HTML АДМИН-ПАНЕЛИ
// ======================
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👑 Админ-панель Danoon Shop</title>
    <style>
        :root {
            --primary: #0088cc;
            --dark: #1a1a1a;
            --light: #f8f9fa;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--light);
            color: #333;
        }
        
        .admin-header {
            background: var(--dark);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-nav {
            background: white;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            gap: 15px;
        }
        
        .nav-btn {
            padding: 10px 20px;
            border: none;
            background: var(--primary);
            color: white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-new { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 5px; }
        .status-processing { background: #cce5ff; color: #004085; padding: 5px 10px; border-radius: 5px; }
        .status-completed { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 5px; }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit { background: var(--warning); color: #000; }
        .btn-delete { background: var(--danger); color: white; }
        .btn-view { background: var(--primary); color: white; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>👑 Админ-панель Danoon Shop</h1>
        <div>
            <span>Привет, <?php echo $telegram_user['first_name']; ?>!</span>
            <a href="?logout=1" style="color: white; margin-left: 20px;">Выйти</a>
        </div>
    </header>
    
    <nav class="admin-nav">
        <button class="nav-btn" onclick="showSection('dashboard')">📊 Дашборд</button>
        <button class="nav-btn" onclick="showSection('products')">📦 Товары</button>
        <button class="nav-btn" onclick="showSection('orders')">🛒 Заказы</button>
        <button class="nav-btn" onclick="showSection('categories')">🏷️ Категории</button>
        <button class="nav-btn" onclick="showSection('analytics')">📈 Аналитика</button>
    </nav>
    
    <div class="admin-container">
        <!-- Дашборд -->
        <section id="dashboard-section" style="display: block;">
            <h2>📊 Общая статистика</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo getTotalOrders(); ?></div>
                    <div class="stat-label">Всего заказов</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo getTotalRevenue(); ?> ₽</div>
                    <div class="stat-label">Общая выручка</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo getTotalProducts(); ?></div>
                    <div class="stat-label">Товаров в каталоге</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo getNewOrdersToday(); ?></div>
                    <div class="stat-label">Новых заказов сегодня</div>
                </div>
            </div>
            
            <h3 style="margin-top: 30px;">📈 Последние заказы</h3>
            <div class="table-container">
                <?php displayRecentOrders(); ?>
            </div>
        </section>
        
        <!-- Товары -->
        <section id="products-section" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📦 Управление товарами</h2>
                <button class="nav-btn" onclick="openProductModal()">+ Добавить товар</button>
            </div>
            
            <div class="table-container">
                <?php displayProductsTable(); ?>
            </div>
        </section>
        
        <!-- Заказы -->
        <section id="orders-section" style="display: none;">
            <h2>🛒 Управление заказами</h2>
            
            <div style="margin: 20px 0;">
                <button class="nav-btn" onclick="filterOrders('new')">Новые</button>
                <button class="nav-btn" onclick="filterOrders('processing')">В обработке</button>
                <button class="nav-btn" onclick="filterOrders('all')">Все</button>
            </div>
            
            <div class="table-container">
                <?php displayOrdersTable(); ?>
            </div>
        </section>
        
        <!-- Категории -->
        <section id="categories-section" style="display: none;">
            <h2>🏷️ Управление категориями</h2>
            <?php displayCategories(); ?>
        </section>
        
        <!-- Аналитика -->
        <section id="analytics-section" style="display: none;">
            <h2>📈 Аналитика продаж</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo getPopularProduct(); ?></div>
                    <div class="stat-label">Самый популярный товар</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo getAverageOrderValue(); ?> ₽</div>
                    <div class="stat-label">Средний чек</div>
                </div>
            </div>
            
            <h3 style="margin-top: 30px;">📊 Статистика по дням</h3>
            <div class="table-container">
                <?php displayDailyStats(); ?>
            </div>
        </section>
    </div>
    
    <!-- Модальное окно для товара -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <h3 id="modalTitle">Добавить товар</h3>
            <form id="productForm" method="POST">
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="id" id="productId" value="0">
                
                <div class="form-group">
                    <label>Название товара</label>
                    <input type="text" name="name" id="productName" required>
                </div>
                
                <div class="form-group">
                    <label>Категория</label>
                    <select name="category_id" id="productCategory" required>
                        <?php displayCategoryOptions(); ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Цена (₽)</label>
                    <input type="number" name="price" id="productPrice" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Старая цена (₽)</label>
                    <input type="number" name="old_price" id="productOldPrice" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>Количество</label>
                    <input type="number" name="stock" id="productStock" required>
                </div>
                
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="productDescription" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>URL изображения</label>
                    <input type="url" name="image_url" id="productImageUrl">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="nav-btn">Сохранить</button>
                    <button type="button" class="btn btn-delete" onclick="closeModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Переключение разделов
        function showSection(sectionId) {
            document.querySelectorAll('[id$="-section"]').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId + '-section').style.display = 'block';
        }
        
        // Модальное окно товара
        function openProductModal(productId = 0) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('productForm');
            
            if (productId > 0) {
                title.textContent = 'Редактировать товар';
                form.action = '?action=update_product';
                document.getElementById('productId').value = productId;
                
                // Загружаем данные товара (в реальном приложении через AJAX)
                // loadProductData(productId);
            } else {
                title.textContent = 'Добавить товар';
                form.action = '?action=add_product';
                form.reset();
                document.getElementById('productId').value = '0';
            }
            
            modal.style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        // Фильтрация заказов
        function filterOrders(status) {
            // В реальном приложении здесь будет AJAX запрос
            alert('Фильтр: ' + status);
        }
        
        // Подтверждение удаления
        function confirmDelete(productId, productName) {
            if (confirm(`Удалить товар "${productName}"?`)) {
                window.location.href = `?action=delete_product&id=${productId}`;
            }
        }
        
        // Обновление статуса заказа
        function updateOrderStatus(orderId, status) {
            if (confirm('Изменить статус заказа?')) {
                // AJAX запрос для обновления статуса
                fetch('admin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'update_order_status',
                        order_id: orderId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Статус обновлен');
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>

<?php
// ======================
// ФУНКЦИИ АДМИН-ПАНЕЛИ
// ======================

function getTotalOrders() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    return $stmt->fetch()['total'];
}

function getTotalRevenue() {
    global $pdo;
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
    return number_format($stmt->fetch()['total'] ?? 0, 0, ',', ' ');
}

function getTotalProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    return $stmt->fetch()['total'];
}

function getNewOrdersToday() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

function displayRecentOrders() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
    $orders = $stmt->fetchAll();
    
    if (empty($orders)) {
        echo "<p style='padding: 20px; text-align: center;'>Нет заказов</p>";
        return;
    }
    
    echo '<table>';
    echo '<tr><th>ID</th><th>Клиент</th><th>Сумма</th><th>Статус</th><th>Дата</th><th>Действия</th></tr>';
    
    foreach ($orders as $order) {
        $statusClass = 'status-' . $order['status'];
        $statusText = $order['status'] == 'new' ? 'Новый' : 
                     ($order['status'] == 'processing' ? 'В обработке' : 'Завершен');
        
        echo "<tr>";
        echo "<td>#{$order['id']}</td>";
        echo "<td>{$order['user_name']}</td>";
        echo "<td>{$order['total_amount']} ₽</td>";
        echo "<td><span class='{$statusClass}'>{$statusText}</span></td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($order['created_at'])) . "</td>";
        echo "<td>
                <button class='btn btn-view' onclick=\"viewOrder({$order['id']})\">Просмотр</button>
                <button class='btn btn-edit' onclick=\"updateOrderStatus({$order['id']}, 'processing')\">В обработку</button>
              </td>";
        echo "</tr>";
    }
    
    echo '</table>';
}

function displayProductsTable() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "<p style='padding: 20px; text-align: center;'>Нет товаров</p>";
        return;
    }
    
    echo '<table>';
    echo '<tr><th>ID</th><th>Название</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Лайки</th><th>Действия</th></tr>';
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$product['category_name']}</td>";
        echo "<td>{$product['price']} ₽</td>";
        echo "<td>{$product['stock']} шт.</td>";
        echo "<td>{$product['likes']}</td>";
        echo "<td>
                <button class='btn btn-edit' onclick=\"openProductModal({$product['id']})\">Редактировать</button>
                <button class='btn btn-delete' onclick=\"confirmDelete({$product['id']}, '{$product['name']}')\">Удалить</button>
              </td>";
        echo "</tr>";
    }
    
    echo '</table>';
}

function displayOrdersTable() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();
    
    if (empty($orders)) {
        echo "<p style='padding: 20px; text-align: center;'>Нет заказов</p>";
        return;
    }
    
    echo '<table>';
    echo '<tr><th>ID</th><th>Клиент</th><th>Телефон</th><th>Сумма</th><th>Статус</th><th>Дата</th><th>Действия</th></tr>';
    
    foreach ($orders as $order) {
        $statusClass = 'status-' . $order['status'];
        $statusText = $order['status'] == 'new' ? 'Новый' : 
                     ($order['status'] == 'processing' ? 'В обработке' : 'Завершен');
        
        echo "<tr>";
        echo "<td>#{$order['id']}</td>";
        echo "<td>{$order['user_name']}</td>";
        echo "<td>" . ($order['user_phone'] ?? '-') . "</td>";
        echo "<td>{$order['total_amount']} ₽</td>";
        echo "<td><span class='{$statusClass}'>{$statusText}</span></td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($order['created_at'])) . "</td>";
        echo "<td>
                <select onchange=\"updateOrderStatus({$order['id']}, this.value)\">
                    <option value='new' " . ($order['status'] == 'new' ? 'selected' : '') . ">Новый</option>
                    <option value='processing' " . ($order['status'] == 'processing' ? 'selected' : '') . ">В обработке</option>
                    <option value='completed' " . ($order['status'] == 'completed' ? 'selected' : '') . ">Завершен</option>
                    <option value='cancelled' " . ($order['status'] == 'cancelled' ? 'selected' : '') . ">Отменен</option>
                </select>
                <button class='btn btn-view' onclick=\"viewOrder({$order['id']})\">Детали</button>
              </td>";
        echo "</tr>";
    }
    
    echo '</table>';
}

function displayCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order");
    $categories = $stmt->fetchAll();
    
    echo '<div class="table-container">';
    echo '<table>';
    echo '<tr><th>ID</th><th>Название</th><th>Товаров</th><th>Действия</th></tr>';
    
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->execute([$category['id']]);
        $productCount = $stmt->fetch()['count'];
        
        echo "<tr>";
        echo "<td>{$category['id']}</td>";
        echo "<td>{$category['name']}</td>";
        echo "<td>{$productCount}</td>";
        echo "<td>
                <button class='btn btn-edit'>Редактировать</button>
                <button class='btn btn-delete'>Удалить</button>
              </td>";
        echo "</tr>";
    }
    
    echo '</table>';
    echo '</div>';
}

function displayCategoryOptions() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY sort_order");
    $categories = $stmt->fetchAll();
    
    foreach ($categories as $category) {
        echo "<option value='{$category['id']}'>{$category['name']}</option>";
    }
}

function getPopularProduct() {
    global $pdo;
    $stmt = $pdo->query("SELECT name FROM products ORDER BY likes DESC, views DESC LIMIT 1");
    $product = $stmt->fetch();
    return $product['name'] ?? 'Нет данных';
}

function getAverageOrderValue() {
    global $pdo;
    $stmt = $pdo->query("SELECT AVG(total_amount) as avg FROM orders WHERE status = 'completed'");
    $avg = $stmt->fetch()['avg'];
    return number_format($avg ?? 0, 0, ',', ' ');
}

function displayDailyStats() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders_count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stats = $stmt->fetchAll();
    
    echo '<table>';
    echo '<tr><th>Дата</th><th>Заказов</th><th>Выручка</th></tr>';
    
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>" . date('d.m.Y', strtotime($stat['date'])) . "</td>";
        echo "<td>{$stat['orders_count']}</td>";
        echo "<td>" . number_format($stat['revenue'], 0, ',', ' ') . " ₽</td>";
        echo "</tr>";
    }
    
    echo '</table>';
}

// Функции обработки форм
function addProduct($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO products 
            (category_id, name, description, price, old_price, stock, image_url, slug)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $slug = translit($data['name']);
        
        $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['old_price'] ?? null,
            $data['stock'],
            $data['image_url'] ?? null,
            $slug
        ]);
        
        echo "<script>alert('Товар добавлен!'); window.location.href = 'admin.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Ошибка: " . $e->getMessage() . "');</script>";
    }
}

function translit($str) {
    $translit = array(
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh',
        'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts',
        'ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu',
        'я'=>'ya',' '=>'-'
    );
    
    $str = mb_strtolower($str, 'UTF-8');
    $str = strtr($str, $translit);
    $str = preg_replace('/[^a-z0-9-]/', '', $str);
    $str = preg_replace('/-+/', '-', $str);
    $str = trim($str, '-');
    
    return $str;
}
?>
