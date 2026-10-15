<?php
require_once __DIR__ . '/includes/db.php';

$restaurantId = (int)($_GET['restaurant_id'] ?? 0);
header('Content-Type: application/json; charset=utf-8');

if ($restaurantId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, price, availability FROM menu_items WHERE restaurant_id = ? AND TRIM(name) != "" ORDER BY name');
$stmt->execute([$restaurantId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Optionally filter only available
$items = array_values(array_filter($items, function($it) {
    return ($it['availability'] ?? '') !== 'Unavailable' && trim((string)($it['name'] ?? '')) !== '';
}));

echo json_encode($items);
