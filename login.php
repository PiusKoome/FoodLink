<?php require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        flash('success', 'Welcome back to FoodLink.');
        header('Location: dashboard.php');
        exit;
    }

    flash('danger', 'Invalid email or password.');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2" style="margin-top:2rem;">
    <div class="card">
        <h2>Login to FoodLink</h2>
        <form method="post">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button class="btn btn-primary" type="submit">Login</button>
        </form>
    </div>
    <div class="card">
        <h3>New here?</h3>
        <p>Create an account to start ordering food, scheduling meals, and managing restaurant services.</p>
        <a class="btn btn-secondary" href="register.php">Register now</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
