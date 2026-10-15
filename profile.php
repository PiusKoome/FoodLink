<?php require_once __DIR__ . '/includes/db.php';
requireLogin();

$user = currentUser();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, location = ? WHERE id = ?');
    $stmt->execute([$name, $phone, $location, $user['id']]);
    flash('success', 'Profile updated.');
    header('Location: profile.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="margin-top:2rem;">
    <h2>Update profile</h2>
    <p>Allow customers to update their personal profile and delivery location.</p>
    <form method="post">
        <label>Name</label>
        <input name="name" value="<?= e($user['name']) ?>" required>
        <label>Phone</label>
        <input name="phone" value="<?= e($user['phone']) ?>">
        <label>Location</label>
        <input name="location" value="<?= e($user['location']) ?>">
        <button class="btn btn-primary" type="submit">Save profile</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
