<?php require_once __DIR__ . '/includes/db.php';
requireRole('restaurant_owner');

$user = currentUser();
$restaurant = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ? ORDER BY created_at DESC LIMIT 1');
$restaurant->execute([$user['id']]);
$restaurant = $restaurant->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($restaurant) {
        $stmt = $pdo->prepare('UPDATE restaurants SET name = ?, location = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $location, $description, $restaurant['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO restaurants (owner_id, name, location, description, status) VALUES (?, ?, ?, ?, "pending")');
        $stmt->execute([$user['id'], $name, $location, $description]);
    }
    flash('success', 'Restaurant information saved.');
    header('Location: restaurant_management.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2" style="margin-top:2rem;">
    <div class="card">
        <h2>Restaurant management</h2>
        <p>Allow restaurant owners to register, update restaurant information, and manage their restaurant profile.</p>
        <form method="post">
            <label>Restaurant name</label>
            <input name="name" value="<?= e($restaurant['name'] ?? '') ?>" required>
            <label>Location</label>
            <input name="location" value="<?= e($restaurant['location'] ?? '') ?>" required>
            <label>Description</label>
            <textarea name="description"><?= e($restaurant['description'] ?? '') ?></textarea>
            <button class="btn btn-primary" type="submit">Save restaurant</button>
        </form>
    </div>
    <div class="card">
        <h3>Menu control</h3>
        <p>Add meals and update prices or availability from the menu table below.</p>
        <?php if ($restaurant): ?>
            <a class="btn btn-secondary" href="menu_management.php?restaurant_id=<?= (int)$restaurant['id'] ?>">Manage menu</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
