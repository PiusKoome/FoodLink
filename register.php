<?php require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'customer');

    if ($name === '' || $email === '' || $password === '') {
        flash('danger', 'Please enter your full details.');
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, phone, location) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $passwordHash, $role, $phone, $location]);
            flash('success', 'Account created successfully. Please sign in.');
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            flash('danger', 'This email is already registered.');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2" style="margin-top:2rem;">
    <div class="card">
        <h2>Create your FoodLink account</h2>
        <p>Register as a customer, restaurant owner, or administrator.</p>
        <form method="post">
            <label>Name</label>
            <input name="name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Phone</label>
            <input name="phone">
            <label>Delivery location</label>
            <input name="location" placeholder="Nairobi">
            <label>Password</label>
            <input type="password" name="password" required>
            <label>Role</label>
            <select name="role">
                <option value="customer">Customer</option>
                <option value="restaurant_owner">Restaurant Owner</option>
            </select>
            <button class="btn btn-primary" type="submit">Register</button>
        </form>
    </div>
    <div class="card">
        <h3>Why FoodLink?</h3>
        <ul>
            <li>Register and manage profiles securely</li>
            <li>Browse restaurants by location</li>
            <li>Place food orders and make online payments</li>
            <li>Track deliveries and manage restaurant operations</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
