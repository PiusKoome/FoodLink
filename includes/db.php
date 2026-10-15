<?php
session_start();

$dbConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'foodlink',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
];

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function addColumnIfNotExists(PDO $pdo, string $table, string $column, string $definition): void {
    if (!columnExists($pdo, $table, $column)) {
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }
}

try {
    $baseDsn = 'mysql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'] . ';charset=utf8mb4';
    $basePdo = new PDO($baseDsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $safeDbName = str_replace('`', '``', $dbConfig['name']);
    $basePdo->exec('CREATE DATABASE IF NOT EXISTS `' . $safeDbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $pdo = new PDO('mysql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'] . ';dbname=' . $dbConfig['name'] . ';charset=utf8mb4', $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Database connection could not be established.');
    }
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
} catch (RuntimeException $e) {
    die($e->getMessage());
}

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

$pdo->exec("CREATE TABLE IF NOT EXISTS restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_restaurants_owner FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB;");

$pdo->exec("CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT DEFAULT NULL,
    availability VARCHAR(50) NOT NULL DEFAULT 'Available',
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
) ENGINE=InnoDB;");

addColumnIfNotExists($pdo, 'menu_items', 'image_path', 'VARCHAR(255) DEFAULT NULL');

$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    delivery_address TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    delivery_person VARCHAR(255) DEFAULT NULL,
    restaurant_order_number INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_orders_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
) ENGINE=InnoDB;");

addColumnIfNotExists($pdo, 'orders', 'delivery_person', 'VARCHAR(255) DEFAULT NULL');
addColumnIfNotExists($pdo, 'orders', 'restaurant_order_number', 'INT NOT NULL DEFAULT 0');

function ensureRestaurantOrderNumbers(PDO $pdo): void {
    $orders = $pdo->query('SELECT id, restaurant_id FROM orders WHERE restaurant_order_number IS NULL OR restaurant_order_number = 0 ORDER BY restaurant_id, created_at, id')->fetchAll(PDO::FETCH_ASSOC);
    if (!$orders) {
        return;
    }

    $counters = [];
    foreach ($orders as $order) {
        $restaurantId = (int) ($order['restaurant_id'] ?? 0);
        if (!isset($counters[$restaurantId])) {
            $counters[$restaurantId] = 1;
        }
        $number = $counters[$restaurantId];
        $counters[$restaurantId] = $number + 1;

        $stmt = $pdo->prepare('UPDATE orders SET restaurant_order_number = ? WHERE id = ?');
        $stmt->execute([$number, (int) $order['id']]);
    }
}

ensureRestaurantOrderNumbers($pdo);

$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id),
    CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
) ENGINE=InnoDB;");

$pdo->exec("CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(50) NOT NULL,
    transaction_code VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB;");

$pdo->exec("CREATE TABLE IF NOT EXISTS meal_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    meal_type VARCHAR(50) NOT NULL DEFAULT 'breakfast',
    schedule_type VARCHAR(50) NOT NULL,
    schedule_date VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meal_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_meal_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
    CONSTRAINT fk_meal_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
) ENGINE=InnoDB;");

addColumnIfNotExists($pdo, 'meal_schedules', 'meal_type', 'VARCHAR(50) NOT NULL DEFAULT "breakfast"');

if (!tableExists($pdo, 'notifications')) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB;");
}
addColumnIfNotExists($pdo, 'notifications', 'is_read', 'TINYINT(1) NOT NULL DEFAULT 0');
addColumnIfNotExists($pdo, 'notifications', 'link', 'VARCHAR(255) DEFAULT NULL');

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($roles): void {
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: dashboard.php');
        exit;
    }
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

function getFlash(): array {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatCurrency($value): string {
    return 'KSh ' . number_format((float) $value, 2);
}
?>
