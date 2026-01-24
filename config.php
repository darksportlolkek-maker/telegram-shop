<?php
// config.php - Настройки подключения к MySQL

// ЗАМЕНИТЕ ЭТИ ДАННЫЕ НА ВАШИ!
define('DB_HOST', 'localhost');
define('DB_NAME', 'danoon_shop');      
define('DB_USER', 'root'); 
define('DB_PASS', 'Daniel2006');    
define('DB_CHARSET', 'utf8mb4');

// Настройки магазина
define('SITE_URL', 'https://ваш-сайт.com');
define('ITEMS_PER_PAGE', 12);

// Telegram настройки
define('ADMIN_TELEGRAM_ID', 'ВАШ_ТЕЛЕГРАМ_ID');

// Функция для безопасного подключения к БД
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    
    return $pdo;
}

// Защита от SQL-инъекций
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Логирование ошибок
function logError($message, $data = []) {
    $logMessage = date('[Y-m-d H:i:s]') . ' ' . $message . ' ' . json_encode($data) . PHP_EOL;
    file_put_contents('error.log', $logMessage, FILE_APPEND);
}

// Возврат JSON ответа
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
?>
