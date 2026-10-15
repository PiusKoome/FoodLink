<?php require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$userLocation = '';
if (isLoggedIn()) {
    $user = currentUser();
    $userLocation = trim((string)($user['location'] ?? ''));
}

$searchQuery = trim($_GET['search'] ?? '');
if ($searchQuery === '') {
    $searchQuery = trim($_GET['location'] ?? '');
}
if ($searchQuery === '') {
    $searchQuery = trim($_GET['restaurant'] ?? '');
}
if ($searchQuery === '' && $userLocation !== '') {
    $searchQuery = $userLocation;
}

if ($searchQuery !== '') {
    $searchTerm = '%' . strtolower($searchQuery) . '%';
    $stmt = $pdo->prepare('SELECT r.*, u.name AS owner_name FROM restaurants r JOIN users u ON u.id = r.owner_id WHERE r.status IN ("approved", "pending") AND (lower(trim(r.location)) LIKE ? OR lower(r.name) LIKE ?) ORDER BY CASE WHEN r.status = "approved" THEN 0 ELSE 1 END, r.created_at DESC');
    $stmt->execute([$searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->query('SELECT r.*, u.name AS owner_name FROM restaurants r JOIN users u ON u.id = r.owner_id WHERE r.status IN ("approved", "pending") ORDER BY CASE WHEN r.status = "approved" THEN 0 ELSE 1 END, r.created_at DESC');
}
$restaurants = $stmt->fetchAll();
?>

<div class="card" style="margin-top:2rem;">
    <h2>Nearby Restaurants</h2>
    <p>Search by area or restaurant name to find restaurants in the same location.</p>
    <form method="get" action="restaurants.php" style="display:grid;grid-template-columns:1fr auto;gap:0.75rem;align-items:end;">
        <div>
            <label>Search</label>
            <input name="search" value="<?= e($searchQuery) ?>" placeholder="Enter a location or restaurant name">
        </div>
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</div>

<div class="card" style="margin-top:1rem;">
    <?php if (!empty($restaurants)): ?>
        <div class="grid grid-3">
            <?php foreach ($restaurants as $restaurant): ?>
                <div class="card">
                    <h3><?= e($restaurant['name']) ?></h3>
                    <p><strong>Owner:</strong> <?= e($restaurant['owner_name']) ?></p>
                    <p><strong>Location:</strong> <?= e($restaurant['location']) ?></p>
                    <p><?= e($restaurant['description']) ?></p>
                    <a class="btn btn-primary" href="restaurant.php?id=<?= (int)$restaurant['id'] ?>">View Menu</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No restaurants were found for that location. Try another area name such as Nairobi, Kisumu, or Nakuru.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
