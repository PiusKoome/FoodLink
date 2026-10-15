<?php require_once __DIR__ . '/includes/db.php';
requireRole('admin');

function deleteRestaurantRecords(PDO $pdo, int $restaurantId): void {
    $orders = $pdo->prepare('SELECT id FROM orders WHERE restaurant_id = ?');
    $orders->execute([$restaurantId]);
    $orderIds = array_map('intval', $orders->fetchAll(PDO::FETCH_COLUMN, 0));

    if ($orderIds !== []) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $pdo->prepare('DELETE FROM payments WHERE order_id IN (' . $placeholders . ')')->execute($orderIds);
        $pdo->prepare('DELETE FROM order_items WHERE order_id IN (' . $placeholders . ')')->execute($orderIds);
        $pdo->prepare('DELETE FROM orders WHERE id IN (' . $placeholders . ')')->execute($orderIds);
    }

    $pdo->prepare('DELETE FROM meal_schedules WHERE restaurant_id = ?')->execute([$restaurantId]);
    $pdo->prepare('DELETE FROM menu_items WHERE restaurant_id = ?')->execute([$restaurantId]);
    $pdo->prepare('DELETE FROM restaurants WHERE id = ?')->execute([$restaurantId]);
}

function deleteUserRecords(PDO $pdo, int $userId): void {
    $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);

    $restaurantIds = $pdo->prepare('SELECT id FROM restaurants WHERE owner_id = ?');
    $restaurantIds->execute([$userId]);
    foreach ($restaurantIds->fetchAll(PDO::FETCH_COLUMN, 0) as $restaurantId) {
        deleteRestaurantRecords($pdo, (int) $restaurantId);
    }

    $customerOrders = $pdo->prepare('SELECT id FROM orders WHERE customer_id = ?');
    $customerOrders->execute([$userId]);
    $orderIds = array_map('intval', $customerOrders->fetchAll(PDO::FETCH_COLUMN, 0));

    if ($orderIds !== []) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $pdo->prepare('DELETE FROM payments WHERE order_id IN (' . $placeholders . ')')->execute($orderIds);
        $pdo->prepare('DELETE FROM order_items WHERE order_id IN (' . $placeholders . ')')->execute($orderIds);
        $pdo->prepare('DELETE FROM orders WHERE id IN (' . $placeholders . ')')->execute($orderIds);
    }

    $pdo->prepare('DELETE FROM meal_schedules WHERE customer_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
}

$type = $_GET['type'] ?? 'restaurant';
$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'approve';

if ($id <= 0) {
    flash('danger', 'A valid record ID is required.');
    header('Location: dashboard.php');
    exit;
}

if ($type === 'user') {
    if ($id === 1) {
        flash('danger', 'The primary admin account cannot be deleted.');
    } elseif ($action === 'delete') {
        deleteUserRecords($pdo, $id);
        flash('success', 'User deleted successfully.');
    } else {
        flash('danger', 'Unsupported user action.');
    }
} else {
    if ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $stmt->execute(['approved', $id]);
        flash('success', 'Restaurant registration updated.');
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $stmt->execute(['rejected', $id]);
        flash('success', 'Restaurant rejected.');
    } elseif ($action === 'delete') {
        deleteRestaurantRecords($pdo, $id);
        flash('success', 'Restaurant deleted successfully.');
    } else {
        flash('danger', 'Unsupported restaurant action.');
    }
}

header('Location: dashboard.php');
exit;
