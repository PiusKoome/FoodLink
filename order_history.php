<?php require_once __DIR__ . '/includes/db.php';
requireLogin();
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['cancel']) && $_SESSION['role'] === 'customer') {
    $orderId = (int)$_GET['cancel'];
    $stmt = $pdo->prepare('UPDATE orders SET status = "cancelled" WHERE id = ? AND customer_id = ? AND status = "pending"');
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    flash('success', 'Order cancelled before confirmation.');
    header('Location: order_history.php');
    exit;
}

$stmt = $pdo->prepare('SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id WHERE o.customer_id = ? ORDER BY o.created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="card" style="margin-top:2rem;">
    <h2>Order history</h2>
    <p>Customers can review placements, track delivery progress, and cancel pending orders before confirmation.</p>
</div>

<div class="card" style="margin-top:1rem;">
    <table class="table">
        <thead><tr><th>Order</th><th>Restaurant</th><th>Address</th><th>Status</th><th>Payment</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= (int)($order['restaurant_order_number'] ?: $order['id']) ?></td>
                    <td><?= e($order['restaurant_name']) ?></td>
                    <td><?= e($order['delivery_address']) ?></td>
                    <td><span class="badge"><?= e($order['status']) ?></span></td>
                    <td><?= e($order['payment_status']) ?></td>
                    <td>
                        <?php if ($order['status'] === 'pending'): ?>
                            <a href="order_history.php?cancel=<?= (int)$order['id'] ?>">Cancel</a>
                        <?php else: ?>
                            <span>Tracked</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
