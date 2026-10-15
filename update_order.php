<?php require_once __DIR__ . '/includes/db.php';
requireRole(['restaurant_owner', 'admin']);

$orderId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? 'pending');
    $deliveryPerson = trim($_POST['delivery_person'] ?? '');
    $stmt = $pdo->prepare('UPDATE orders SET status = ?, delivery_person = ? WHERE id = ?');
    $stmt->execute([$status, $deliveryPerson, $orderId]);
    flash('success', 'Order status updated.');
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT o.*, r.name AS restaurant_name, u.name AS customer_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id JOIN users u ON u.id = o.customer_id WHERE o.id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();
$deliveryPersonValue = $order['delivery_person'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="margin-top:2rem;">
    <h2>Update order #<?= (int)($order['restaurant_order_number'] ?: $order['id']) ?></h2>
    <p><strong>Customer:</strong> <?= e($order['customer_name']) ?> | <strong>Restaurant:</strong> <?= e($order['restaurant_name']) ?></p>
    <p><strong>Delivery address:</strong> <?= e($order['delivery_address']) ?></p>
    <form method="post">
        <label>Status</label>
        <select name="status">
            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="delivering" <?= $order['status'] === 'delivering' ? 'selected' : '' ?>>Delivering</option>
            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        <label>Delivery person</label>
        <input name="delivery_person" value="<?= e($deliveryPersonValue) ?>">
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
