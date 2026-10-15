<?php require_once __DIR__ . '/includes/db.php';
requireLogin();
require_once __DIR__ . '/includes/header.php';

$user = currentUser();
$userId = (int) $user['id'];
$role = $user['role'];
$search = [
    'from_date' => trim((string)($_GET['from_date'] ?? '')),
    'to_date' => trim((string)($_GET['to_date'] ?? '')),
    'customer_name' => trim((string)($_GET['customer_name'] ?? '')),
    'food_name' => trim((string)($_GET['food_name'] ?? '')),
    'address' => trim((string)($_GET['address'] ?? '')),
];

if ($role === 'admin') {
    $restaurants = $pdo->query('SELECT * FROM restaurants ORDER BY CASE WHEN status = "pending" THEN 0 WHEN status = "approved" THEN 1 ELSE 2 END, created_at DESC')->fetchAll();
    $users = $pdo->query('SELECT * FROM users WHERE role != "admin" ORDER BY created_at DESC')->fetchAll();
    $orders = $pdo->query('SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id ORDER BY o.created_at DESC LIMIT 10')->fetchAll();
    $payments = $pdo->query('SELECT p.*, o.id AS order_id FROM payments p JOIN orders o ON o.id = p.order_id ORDER BY p.created_at DESC LIMIT 10')->fetchAll();
} elseif ($role === 'restaurant_owner') {
    $restaurants = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ? ORDER BY CASE WHEN status = "pending" THEN 0 WHEN status = "approved" THEN 1 ELSE 2 END, created_at DESC');
    $restaurants->execute([$userId]);
    $restaurants = $restaurants->fetchAll();

    $conditions = ['r.owner_id = ?'];
    $params = [$userId];

    if ($search['from_date'] !== '') {
        $conditions[] = 'DATE(o.created_at) >= ?';
        $params[] = $search['from_date'];
    }
    if ($search['to_date'] !== '') {
        $conditions[] = 'DATE(o.created_at) <= ?';
        $params[] = $search['to_date'];
    }
    if ($search['customer_name'] !== '') {
        $conditions[] = 'LOWER(u.name) LIKE ?';
        $params[] = '%' . strtolower($search['customer_name']) . '%';
    }
    if ($search['food_name'] !== '') {
        $conditions[] = 'LOWER(mi.name) LIKE ?';
        $params[] = '%' . strtolower($search['food_name']) . '%';
    }
    if ($search['address'] !== '') {
        $conditions[] = 'LOWER(o.delivery_address) LIKE ?';
        $params[] = '%' . strtolower($search['address']) . '%';
    }

    $sql = 'SELECT o.id, o.customer_id, o.restaurant_id, o.delivery_address, o.total_amount, o.status, o.payment_status, o.delivery_person, o.restaurant_order_number, o.created_at, r.name AS restaurant_name, u.name AS customer_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id JOIN users u ON u.id = o.customer_id WHERE ' . implode(' AND ', $conditions) . ' ORDER BY o.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} else {
    $orders = $pdo->prepare('SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 10');
    $orders->execute([$userId]);
    $orders = $orders->fetchAll();
    $schedules = $pdo->prepare('SELECT s.*, r.name AS restaurant_name, m.name AS item_name FROM meal_schedules s JOIN restaurants r ON r.id = s.restaurant_id JOIN menu_items m ON m.id = s.menu_item_id WHERE s.customer_id = ? ORDER BY s.created_at DESC');
    $schedules->execute([$userId]);
    $schedules = $schedules->fetchAll();
}
?>

<div class="card" style="margin-top:2rem;">
    <h2>Welcome, <?= e($user['name']) ?>!</h2>
    <p>This is your FoodLink customer dashboard. From here you can manage your profile, location, restaurants, meal schedules, orders, payments, and order history.</p>
</div>

<?php if ($role === 'customer'): ?>
    <div class="grid grid-3" style="margin-top:1rem;">
        <div class="card">
            <h3>Profile</h3>
            <p>Update your account details, phone number, and personal information.</p>
            <a class="btn btn-secondary" href="profile.php">Edit profile</a>
        </div>
        <div class="card">
            <h3>Delivery location</h3>
            <p>Register or update your delivery location so restaurants can be displayed based on your area.</p>
            <a class="btn btn-secondary" href="profile.php">Set location</a>
        </div>
        <div class="card">
            <h3>Restaurants</h3>
            <p>Browse restaurants in your selected location and view their menus.</p>
            <a class="btn btn-primary" href="restaurants.php">Find restaurants</a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-top:1rem;">
        <div class="card">
            <h3>Meal schedules</h3>
            <p>Create daily or weekly meal schedules for recurring orders.</p>
            <a class="btn btn-secondary" href="schedule.php">Manage schedules</a>
        </div>
        <div class="card">
            <h3>Orders</h3>
            <p>Place food orders, pay securely, and cancel pending orders before confirmation.</p>
            <a class="btn btn-secondary" href="order_history.php">See orders</a>
        </div>
        <div class="card">
            <h3>Payments</h3>
            <p>View your payment status and transaction records for every order.</p>
            <a class="btn btn-primary" href="order_history.php">View payment history</a>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <h3>Recent orders</h3>
        <table class="table"><thead><tr><th>Restaurant</th><th>Status</th><th>Amount</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?>
            <tr><td><?= e($order['restaurant_name']) ?></td><td><span class="badge"><?= e($order['status']) ?></span></td><td><?= formatCurrency($order['total_amount']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
<?php elseif ($role === 'restaurant_owner'): ?>
    <div class="grid grid-2" style="margin-top:1rem;">
        <div class="card">
            <h3>Restaurant login & account</h3>
            <p>Secure login, profile updates, and restaurant information management.</p>
            <a class="btn btn-primary" href="restaurant_management.php">Open restaurant dashboard</a>
        </div>
        <div class="card">
            <h3>Menu & orders</h3>
            <p>Manage food menus, edit prices and availability, receive orders, and accept or reject requests.</p>
            <a class="btn btn-secondary" href="menu_management.php?restaurant_id=<?= (int)($restaurants[0]['id'] ?? 0) ?>">Manage menu</a>
        </div>
    </div>
    <div class="grid grid-2" style="margin-top:1rem;">
        <div class="card">
            <h3>Delivery & payments</h3>
            <p>Assign deliveries, update delivery status, track online payments, and keep order progress current.</p>
            <a class="btn btn-secondary" href="update_order.php?id=<?= (int)($orders[0]['id'] ?? 0) ?>">Update order status</a>
        </div>
        <div class="card">
            <h3>Daily & weekly orders</h3>
            <p>Monitor recurring daily and weekly orders and review sales activity for your restaurant.</p>
            <a class="btn btn-primary" href="dashboard.php">Review order activity</a>
        </div>
    </div>
    <div class="card" style="margin-top:1rem;">
        <h3>Incoming orders</h3>
        <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;margin-bottom:1rem;">
            <input type="date" name="from_date" value="<?= e($search['from_date']) ?>" placeholder="From date">
            <input type="date" name="to_date" value="<?= e($search['to_date']) ?>" placeholder="To date">
            <input type="text" name="customer_name" value="<?= e($search['customer_name']) ?>" placeholder="Customer name">
            <input type="text" name="food_name" value="<?= e($search['food_name']) ?>" placeholder="Food type">
            <input type="text" name="address" value="<?= e($search['address']) ?>" placeholder="Address">
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
        <table class="table"><thead><tr><th>Order</th><th>Customer</th><th>Food</th><th>Address</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="6">No orders matched your search.</td></tr>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <tr><td>#<?= (int)($order['restaurant_order_number'] ?: $order['id']) ?></td><td><?= e($order['customer_name']) ?></td><td><?= e($order['food_items'] ?? '') ?></td><td><?= e($order['delivery_address'] ?? '') ?></td><td><span class="badge"><?= e($order['status']) ?></span></td><td><a href="update_order.php?id=<?= (int)$order['id'] ?>">Update</a></td></tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody></table>
    </div>
<?php else: ?>
    <?php
    $restaurants = $restaurants ?? [];
    $users = $users ?? [];
    $payments = $payments ?? [];
    ?>
    <div class="grid grid-2" style="margin-top:1rem;">
        <div class="card" id="users-panel"><h3>Customer account management</h3><p>Manage customer profiles, monitor user activities, and maintain account security.</p><a class="btn btn-secondary" href="#users-list">View users</a></div>
        <div class="card" id="restaurants-panel"><h3>Restaurant account management</h3><p>Manage restaurant registrations, approve or reject business accounts, and review records.</p><a class="btn btn-primary" href="#restaurant-approvals">Review restaurants</a></div>
    </div>
    <div class="grid grid-2" style="margin-top:1rem;">
        <div class="card" id="transactions-panel"><h3>Payments & delivery</h3><p>Monitor online payment transactions, delivery information, and order progress across the platform.</p><a class="btn btn-secondary" href="#payments-list">Review transactions</a></div>
        <div class="card"><h3>Reports & security</h3><p>Generate system reports, manage permissions, maintain security, and backup or restore data.</p><a class="btn btn-primary" href="backup.php">Backup data</a></div>
    </div>
    <div class="card" id="restaurant-approvals" style="margin-top:1rem;">
        <h3>Restaurant approvals</h3>
        <table class="table"><thead><tr><th>Name</th><th>Status</th><th>Owner</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($restaurants as $restaurant): ?>
            <tr>
                <td><?= e($restaurant['name']) ?></td>
                <td><span class="badge"><?= e($restaurant['status']) ?></span></td>
                <td><?= e($restaurant['owner_id']) ?></td>
                <td>
                    <?php if ($restaurant['status'] === 'pending'): ?>
                        <a href="manage_restaurant.php?id=<?= (int)$restaurant['id'] ?>&action=approve">Approve</a>
                        &nbsp;|&nbsp;
                        <a href="manage_restaurant.php?id=<?= (int)$restaurant['id'] ?>&action=reject">Reject</a>
                    <?php else: ?>
                        <span>Reviewed</span>
                    <?php endif; ?>
                    &nbsp;|&nbsp;
                    <a href="manage_restaurant.php?type=restaurant&id=<?= (int)$restaurant['id'] ?>&action=delete" onclick="return confirm('Delete this restaurant and all related records?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
    <div class="card" id="users-list" style="margin-top:1rem;"><h3>System activity</h3><table class="table"><thead><tr><th>User</th><th>Role</th><th>Location</th><th>Action</th></tr></thead><tbody><?php foreach ($users as $userRow): ?><tr><td><?= e($userRow['name']) ?></td><td><?= e($userRow['role']) ?></td><td><?= e($userRow['location']) ?></td><td><?php if ((int)$userRow['id'] !== 1): ?><a href="manage_restaurant.php?type=user&id=<?= (int)$userRow['id'] ?>&action=delete" onclick="return confirm('Delete this user and related records?');">Delete</a><?php else: ?><span>Protected</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="card" id="payments-list" style="margin-top:1rem;"><h3>Latest payments</h3><table class="table"><thead><tr><th>Order</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead><tbody><?php foreach ($payments as $payment): ?><tr><td>#<?= (int)$payment['order_id'] ?></td><td><?= formatCurrency($payment['amount']) ?></td><td><?= e($payment['method']) ?></td><td><span class="badge"><?= e($payment['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
