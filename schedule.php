<?php require_once __DIR__ . '/includes/db.php';
requireLogin();

if ($_SESSION['role'] !== 'customer') {
    flash('danger', 'Only customers can manage meal schedules.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurantId = (int)($_POST['restaurant_id'] ?? 0);
    $scheduleType = 'weekly';
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $saved = 0;

    foreach ($days as $day) {
        foreach (['breakfast', 'lunch', 'supper'] as $mealType) {
            $menuItemId = (int)($_POST[$day . '_' . $mealType] ?? 0);
            if ($menuItemId > 0) {
                $stmt = $pdo->prepare('INSERT INTO meal_schedules (customer_id, restaurant_id, menu_item_id, meal_type, schedule_type, schedule_date, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$_SESSION['user_id'], $restaurantId, $menuItemId, $mealType, $scheduleType, $day, $quantity]);
                $saved++;
            }
        }
    }

    if ($saved > 0) {
        flash('success', 'Weekly meal timetable saved.');
    } else {
        flash('danger', 'Please choose at least one meal for the week.');
    }
    header('Location: schedule.php');
    exit;
}

$restaurants = $pdo->query('SELECT * FROM restaurants WHERE status IN ("approved", "pending") ORDER BY name')->fetchAll();
$menuItems = $pdo->query('SELECT * FROM menu_items ORDER BY name')->fetchAll();

$selectedRestaurant = (int)($_POST['restaurant_id'] ?? ($restaurants[0]['id'] ?? 0));

// Group menu items by restaurant for client-side filtering
$menuByRestaurant = [];
foreach ($menuItems as $mi) {
    $rid = (int)($mi['restaurant_id'] ?? 0);
    if (!isset($menuByRestaurant[$rid])) {
        $menuByRestaurant[$rid] = [];
    }
    $menuByRestaurant[$rid][] = $mi;
}

$schedules = $pdo->prepare('SELECT s.*, r.name AS restaurant_name, m.name AS item_name FROM meal_schedules s JOIN restaurants r ON r.id = s.restaurant_id JOIN menu_items m ON m.id = s.menu_item_id WHERE s.customer_id = ? ORDER BY s.created_at DESC');
$schedules->execute([$_SESSION['user_id']]);
$schedules = $schedules->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2" style="margin-top:2rem;">
    <div class="card">
        <h2>Meal schedules</h2>
        <p>Select a restaurant, then build a weekly food timetable by choosing breakfast, lunch, and supper for each day.</p>
        <form method="post">
            <label>Restaurant</label>
            <select name="restaurant_id" id="restaurant-select">
                <?php foreach ($restaurants as $restaurant): ?>
                    <option value="<?= (int)$restaurant['id'] ?>" <?= (int)$restaurant['id'] === $selectedRestaurant ? 'selected' : '' ?>><?= e($restaurant['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Quantity</label>
            <input type="number" name="quantity" min="1" value="1">
            <div class="grid grid-2">
                <?php foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day): ?>
                    <div class="card" style="margin-bottom:0.75rem;">
                        <h4><?= e(ucfirst($day)) ?></h4>
                        <label>Breakfast</label>
                        <select name="<?= e($day) ?>_breakfast" class="meal-select">
                            <option value="0">-- Select breakfast --</option>
                            <?php foreach ($menuItems as $item): ?>
                                <?php if ((int)$item['restaurant_id'] === $selectedRestaurant): ?>
                                    <option value="<?= (int)$item['id'] ?>"><?= e($item['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <label>Lunch</label>
                        <select name="<?= e($day) ?>_lunch" class="meal-select">
                            <option value="0">-- Select lunch --</option>
                            <?php foreach ($menuItems as $item): ?>
                                <?php if ((int)$item['restaurant_id'] === $selectedRestaurant): ?>
                                    <option value="<?= (int)$item['id'] ?>"><?= e($item['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <label>Supper</label>
                        <select name="<?= e($day) ?>_supper" class="meal-select">
                            <option value="0">-- Select supper --</option>
                            <?php foreach ($menuItems as $item): ?>
                                <?php if ((int)$item['restaurant_id'] === $selectedRestaurant): ?>
                                    <option value="<?= (int)$item['id'] ?>"><?= e($item['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary" type="submit">Save weekly timetable</button>
        </form>
    </div>
    <div class="card">
        <h3>Your saved schedules</h3>
        <table class="table">
            <thead><tr><th>Type</th><th>Restaurant</th><th>Item</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?= e(ucfirst($schedule['schedule_date'])) ?></td>
                        <td><?= e(ucfirst($schedule['meal_type'] ?? 'breakfast')) ?></td>
                        <td><?= e($schedule['restaurant_name']) ?></td>
                        <td><?= e($schedule['item_name']) ?></td>
                        <td><?= e($schedule['quantity']) ?></td>
                        <td><a class="btn btn-primary" href="place_order.php?schedule_id=<?= (int)$schedule['id'] ?>">Order</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function populateMealSelectsFromList(items) {
    const selects = document.querySelectorAll('.meal-select');
    selects.forEach(sel => {
        const placeholder = document.createElement('option');
        placeholder.value = 0;
        placeholder.textContent = sel.dataset.placeholder || '-- Select --';
        sel.innerHTML = '';
        sel.appendChild(placeholder);
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            sel.appendChild(opt);
        });
    });
}

function fetchMenuForRestaurant(restaurantId) {
    if (!restaurantId) {
        populateMealSelectsFromList([]);
        return;
    }
    fetch('api_get_menu.php?restaurant_id=' + encodeURIComponent(restaurantId))
        .then(r => r.json())
        .then(data => {
            populateMealSelectsFromList(data || []);
        })
        .catch(() => populateMealSelectsFromList([]));
}

document.addEventListener('DOMContentLoaded', function() {
    const restSel = document.getElementById('restaurant-select');
    if (!restSel) return;
    // set placeholders for each select
    document.querySelectorAll('.meal-select').forEach(sel => {
        sel.dataset.placeholder = sel.dataset.placeholder || sel.querySelector('option[value="0"]')?.textContent || '-- Select --';
    });
    // fetch initial
    fetchMenuForRestaurant(restSel.value ? parseInt(restSel.value, 10) : 0);
    restSel.addEventListener('change', function() {
        const rid = restSel.value ? parseInt(restSel.value, 10) : 0;
        fetchMenuForRestaurant(rid);
    });
});
</script>
