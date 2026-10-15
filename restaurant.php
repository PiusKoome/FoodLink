<?php require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$restaurantId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE id = ? AND status = "approved"');
$stmt->execute([$restaurantId]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    flash('danger', 'Restaurant not found.');
    header('Location: restaurants.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY name');
$stmt->execute([$restaurantId]);
$menuItems = $stmt->fetchAll();
?>

<div class="card" style="margin-top:2rem;">
    <h2><?= e($restaurant['name']) ?></h2>
    <p><strong>Location:</strong> <?= e($restaurant['location']) ?></p>
    <p><?= e($restaurant['description']) ?></p>
</div>

<div class="grid grid-3" style="margin-top:1rem;">
    <?php foreach ($menuItems as $item): ?>
        <div class="card">
            <?php if (!empty($item['image_path'])): ?>
                <img src="<?= e($item['image_path']) ?>" alt="<?= e($item['name']) ?>" style="width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:0.75rem;">
            <?php endif; ?>
            <h3><?= e($item['name']) ?></h3>
            <p><strong>Category:</strong> <?= e($item['category']) ?></p>
            <p><?= e($item['description']) ?></p>
            <p><strong>Availability:</strong> <?= e($item['availability']) ?></p>
            <p><strong><?= formatCurrency($item['price']) ?></strong></p>
            <?php if (isLoggedIn() && $_SESSION['role'] === 'customer'): ?>
                <a class="btn btn-primary" href="place_order.php?item_id=<?= (int)$item['id'] ?>">Order Now</a>
            <?php elseif (isLoggedIn()): ?>
                <span class="badge">Restaurant owner/admin view</span>
            <?php else: ?>
                <a class="btn btn-secondary" href="login.php">Login to Order</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
