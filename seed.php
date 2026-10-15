<?php
require_once __DIR__ . '/includes/db.php';

$pdo->exec('DELETE FROM payments');
$pdo->exec('DELETE FROM order_items');
$pdo->exec('DELETE FROM orders');
$pdo->exec('DELETE FROM meal_schedules');
$pdo->exec('DELETE FROM menu_items');
$pdo->exec('DELETE FROM restaurants');
$pdo->exec('DELETE FROM users');

$users = [
    ['Alice Maina', 'alice@example.com', password_hash('password123', PASSWORD_DEFAULT), 'customer', '0712345678', 'Nairobi'],
    ['Brian Otieno', 'brian@example.com', password_hash('password123', PASSWORD_DEFAULT), 'restaurant_owner', '0723456789', 'Kisumu'],
    ['Carol Mwangi', 'carol@example.com', password_hash('password123', PASSWORD_DEFAULT), 'admin', '0734567890', 'Nakuru']
];

foreach ($users as $user) {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, phone, location) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute($user);
}

echo "Seed data loaded successfully.";
