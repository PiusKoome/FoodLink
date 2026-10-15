<?php require_once __DIR__ . '/includes/db.php';
requireRole('restaurant_owner');

$user = currentUser();
$restaurantId = (int)($_GET['restaurant_id'] ?? 0);

$restaurant = $pdo->prepare('SELECT * FROM restaurants WHERE id = ? AND owner_id = ? LIMIT 1');
if ($restaurantId > 0) {
    $restaurant->execute([$restaurantId, $user['id']]);
    $restaurant = $restaurant->fetch();
} else {
    $restaurant = null;
}

if ($restaurantId <= 0 || !$restaurant) {
    $fallbackRestaurant = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ? ORDER BY created_at DESC LIMIT 1');
    $fallbackRestaurant->execute([$user['id']]);
    $restaurant = $fallbackRestaurant->fetch();
    if ($restaurant) {
        $restaurantId = (int) $restaurant['id'];
    }
}

if (!$restaurant) {
    flash('danger', 'Please create or save your restaurant profile before adding menu items.');
    header('Location: restaurant_management.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurantId = (int)($_POST['restaurant_id'] ?? $restaurant['id']);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $availability = trim($_POST['availability'] ?? 'Available');
    $itemId = (int)($_POST['item_id'] ?? 0);

    $imagePath = '';
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . '/uploads/menu/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = 'uploads/menu/' . $fileName;
        }
    }

    if ($name === '') {
        flash('danger', 'Food name is required.');
        header('Location: menu_management.php?restaurant_id=' . $restaurantId);
        exit;
    }

    $ownedRestaurant = $pdo->prepare('SELECT id FROM restaurants WHERE id = ? AND owner_id = ? LIMIT 1');
    $ownedRestaurant->execute([$restaurantId, $user['id']]);
    if (!$ownedRestaurant->fetch()) {
        flash('danger', 'This restaurant does not belong to your account.');
        header('Location: restaurant_management.php');
        exit;
    }

    if ($itemId > 0) {
        $fields = ['name' => $name, 'category' => $category, 'price' => $price, 'description' => $description, 'availability' => $availability];
        if ($imagePath !== '') {
            $fields['image_path'] = $imagePath;
        }
        $set = [];
        $params = [];
        foreach ($fields as $key => $value) {
            $set[] = $key . ' = ?';
            $params[] = $value;
        }
        $params[] = $itemId;
        $stmt = $pdo->prepare('UPDATE menu_items SET ' . implode(', ', $set) . ' WHERE id = ?');
        $stmt->execute($params);
        flash('success', 'Menu item updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO menu_items (restaurant_id, name, category, price, description, availability, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$restaurantId, $name, $category, $price, $description, $availability, $imagePath]);
        flash('success', 'Menu item added.');
    }

    header('Location: menu_management.php?restaurant_id=' . $restaurantId);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY name');
$stmt->execute([$restaurantId]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2" style="margin-top:2rem;">
    <div class="card">
        <h2>Manage menu</h2>
        <p>Allow restaurant owners to add new food items, edit prices, and update availability.</p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="restaurant_id" value="<?= (int)$restaurantId ?>">
            <input type="hidden" name="item_id" value="0">
            <label>Food name</label>
            <input name="name" required>
            <label>Category</label>
            <input name="category" required>
            <label>Price</label>
            <input type="number" step="0.01" name="price" required>
            <label>Description</label>
            <textarea name="description"></textarea>
            <label>Food image</label>
            <input type="file" name="image" accept="image/*">
            <label>Availability</label>
            <select name="availability">
                <option value="Available">Available</option>
                <option value="Unavailable">Unavailable</option>
            </select>
            <button class="btn btn-primary" type="submit">Add item</button>
        </form>
    </div>
    <div class="card">
        <h3>Current menu</h3>
        <table class="table">
            <thead><tr><th>Name</th><th>Price</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?= e($item['name']) ?>
                            <?php if (!empty($item['image_path'])): ?>
                                <br><img src="<?= e($item['image_path']) ?>" alt="<?= e($item['name']) ?>" style="width:80px;height:80px;object-fit:cover;margin-top:0.4rem;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" enctype="multipart/form-data" style="display:grid;gap:0.5rem;">
                                <input type="hidden" name="restaurant_id" value="<?= (int)$restaurantId ?>">
                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                <input type="number" step="0.01" name="price" value="<?= e($item['price']) ?>" required>
                                <input type="file" name="image" accept="image/*">
                                <button class="btn btn-secondary" type="submit">Update</button>
                            </form>
                        </td>
                        <td><?= e($item['availability']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
