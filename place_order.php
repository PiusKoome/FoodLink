<?php require_once __DIR__ . '/includes/db.php';
requireLogin();

if ($_SESSION['role'] !== 'customer') {
    flash('danger', 'Only customers can place orders.');
    header('Location: dashboard.php');
    exit;
}

$user = currentUser();
$defaultAddress = trim((string)($user['location'] ?? ''));

$itemId = (int)($_GET['item_id'] ?? 0);
$scheduleId = (int)($_GET['schedule_id'] ?? 0);
$item = null;
$address = $defaultAddress;
$menuItemId = 0;

if ($scheduleId > 0) {
    $stmt = $pdo->prepare('SELECT s.*, m.id AS menu_item_id, m.name AS menu_item_name, m.price, r.id AS restaurant_id, r.name AS restaurant_name, r.owner_id, r.status AS restaurant_status FROM meal_schedules s JOIN menu_items m ON m.id = s.menu_item_id JOIN restaurants r ON r.id = s.restaurant_id WHERE s.id = ? AND s.customer_id = ?');
    $stmt->execute([$scheduleId, $_SESSION['user_id']]);
    $item = $stmt->fetch();
    if ($item) {
        $menuItemId = (int)$item['menu_item_id'];
        $item['id'] = $menuItemId;
        $item['name'] = $item['menu_item_name'];
        $item['unit_price'] = $item['price'];
    }
}

if (!$item && $itemId > 0) {
    $stmt = $pdo->prepare('SELECT m.*, r.id AS restaurant_id, r.name AS restaurant_name, r.owner_id FROM menu_items m JOIN restaurants r ON r.id = m.restaurant_id WHERE m.id = ? AND r.status = "approved"');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if ($item) {
        $menuItemId = (int)$item['id'];
    }
}

if (!$item) {
    flash('danger', 'Menu item not found.');
    header('Location: restaurants.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = max(1, (int)($_POST['quantity'] ?? ($item['quantity'] ?? 1)));
    $address = trim($_POST['address'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'M-Pesa');

    if ($address === '') {
        flash('danger', 'Please provide a delivery address.');
    } else {
        $pdo->beginTransaction();
        try {
            $numberStmt = $pdo->prepare('SELECT COALESCE(MAX(restaurant_order_number), 0) + 1 FROM orders WHERE restaurant_id = ?');
            $numberStmt->execute([$item['restaurant_id']]);
            $restaurantOrderNumber = (int)$numberStmt->fetchColumn();

            $stmt = $pdo->prepare('INSERT INTO orders (customer_id, restaurant_id, delivery_address, total_amount, status, payment_status, restaurant_order_number) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $item['restaurant_id'], $address, $item['price'] * $quantity, 'pending', 'pending', $restaurantOrderNumber]);
            $orderId = (int)$pdo->lastInsertId();

            $unitPrice = (float)($item['price'] ?? $item['unit_price'] ?? 0);
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$orderId, $menuItemId, $quantity, $unitPrice, $unitPrice * $quantity]);

            $stmt = $pdo->prepare('INSERT INTO payments (order_id, amount, method, transaction_code, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$orderId, $unitPrice * $quantity, $paymentMethod, 'TXN-' . $orderId, 'paid']);

            $restaurantOwnerId = (int)($item['owner_id'] ?? 0);
            if ($restaurantOwnerId > 0) {
                $notifyStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, link, is_read) VALUES (?, ?, ?, ?, 0)');
                $notifyStmt->execute([
                    $restaurantOwnerId,
                    'New order received',
                    trim((string)($user['name'] ?? 'A customer')) . ' placed a new order for ' . trim((string)($item['name'] ?? 'your menu item')) . '.',
                    'update_order.php?id=' . $orderId
                ]);
            }

            $pdo->commit();
            flash('success', 'Order placed successfully. The restaurant will confirm it shortly.');
            header('Location: order_history.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            flash('danger', 'Could not place order.');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2" style="margin-top:2rem;">
    <div class="card">
        <h2>Place Your Order</h2>
        <p><strong>Restaurant:</strong> <?= e($item['restaurant_name']) ?></p>
        <p><strong>Item:</strong> <?= e($item['name']) ?></p>
        <p><strong>Price:</strong> <?= formatCurrency($item['price']) ?></p>
        <form method="post">
            <label>Quantity</label>
            <input type="number" name="quantity" min="1" value="<?= e((int)($item['quantity'] ?? 1)) ?>">
            <label>Delivery Address</label>
            <textarea name="address" required><?= e($address) ?></textarea>
            <label>Payment Method</label>
            <select name="payment_method">
                <option value="M-Pesa">M-Pesa</option>
                <option value="Card">Card</option>
                <option value="Cash">Cash</option>
            </select>
            <button class="btn btn-primary" type="submit">Confirm Order</button>
        </form>
    </div>
    <div class="card">
        <h3>What happens next?</h3>
        <ul>
            <li>Your order is stored securely.</li>
            <li>The restaurant receives the request.</li>
            <li>Payment confirmation is generated instantly.</li>
            <li>Delivery status can be monitored from your dashboard.</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
