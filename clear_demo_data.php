<?php
require __DIR__ . '/includes/db.php';

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('DELETE FROM payments');
$pdo->exec('DELETE FROM order_items');
$pdo->exec('DELETE FROM orders');
$pdo->exec('DELETE FROM meal_schedules');
$pdo->exec('DELETE FROM menu_items');
$pdo->exec('DELETE FROM restaurants');
$pdo->exec('DELETE FROM users');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$counts = [
    'users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'restaurants' => $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn(),
    'menu_items' => $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn(),
    'orders' => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
];

foreach ($counts as $table => $count) {
    echo $table . ':' . $count . PHP_EOL;
}
