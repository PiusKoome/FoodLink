<?php require_once __DIR__ . '/includes/db.php';
requireRole('admin');

function quoteSqlValue($value): string {
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value) . "'";
}

function buildInsertStatements(string $table, array $rows): string {
    if (empty($rows)) {
        return "-- No rows found for {$table}\n";
    }

    $columns = array_keys($rows[0]);
    $sql = [];
    foreach ($rows as $row) {
        $values = [];
        foreach ($columns as $column) {
            $values[] = quoteSqlValue($row[$column] ?? null);
        }
        $sql[] = 'INSERT INTO `' . str_replace('`', '``', $table) . '` (`' . implode('`, `', array_map(static fn($col) => str_replace('`', '``', $col), $columns)) . '`) VALUES (' . implode(', ', $values) . ');';
    }

    return implode("\n", $sql) . "\n";
}

function listRestaurantBackupData(PDO $pdo, int $restaurantId): array {
    $restaurantStmt = $pdo->prepare('SELECT * FROM restaurants WHERE id = ?');
    $restaurantStmt->execute([$restaurantId]);
    $restaurant = $restaurantStmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        return [];
    }

    $ownerId = (int) ($restaurant['owner_id'] ?? 0);
    $menuItems = $pdo->prepare('SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY id');
    $menuItems->execute([$restaurantId]);
    $menuItems = $menuItems->fetchAll(PDO::FETCH_ASSOC);

    $schedules = $pdo->prepare('SELECT * FROM meal_schedules WHERE restaurant_id = ? ORDER BY id');
    $schedules->execute([$restaurantId]);
    $schedules = $schedules->fetchAll(PDO::FETCH_ASSOC);

    $orders = $pdo->prepare('SELECT * FROM orders WHERE restaurant_id = ? ORDER BY id');
    $orders->execute([$restaurantId]);
    $orders = $orders->fetchAll(PDO::FETCH_ASSOC);

    $orderIds = array_map('intval', array_column($orders, 'id'));
    $customerIds = [];
    foreach ($orders as $order) {
        $customerIds[] = (int) ($order['customer_id'] ?? 0);
    }
    foreach ($schedules as $schedule) {
        $customerIds[] = (int) ($schedule['customer_id'] ?? 0);
    }
    $customerIds = array_values(array_unique(array_filter($customerIds, static fn($id) => $id > 0)));

    $orderItems = [];
    if ($orderIds !== []) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $orderItemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id IN (' . $placeholders . ') ORDER BY id');
        $orderItemsStmt->execute($orderIds);
        $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $payments = [];
    if ($orderIds !== []) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $paymentsStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id IN (' . $placeholders . ') ORDER BY id');
        $paymentsStmt->execute($orderIds);
        $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $users = [];
    if ($ownerId > 0) {
        $ownerStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $ownerStmt->execute([$ownerId]);
        $userRow = $ownerStmt->fetch(PDO::FETCH_ASSOC);
        if ($userRow) {
            $users[] = $userRow;
        }
    }

    if ($customerIds !== []) {
        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
        $customerStmt = $pdo->prepare('SELECT * FROM users WHERE id IN (' . $placeholders . ') ORDER BY id');
        $customerStmt->execute($customerIds);
        $users = array_merge($users, $customerStmt->fetchAll(PDO::FETCH_ASSOC));
    }

    $notifications = [];
    $notificationIds = array_map('intval', array_column($users, 'id'));
    if ($notificationIds !== []) {
        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        $notificationStmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id IN (' . $placeholders . ') ORDER BY id');
        $notificationStmt->execute($notificationIds);
        $notifications = $notificationStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $uniqueUsers = [];
    foreach ($users as $userRow) {
        $uniqueUsers[(int) ($userRow['id'] ?? 0)] = $userRow;
    }

    return [
        'users' => array_values($uniqueUsers),
        'restaurants' => [$restaurant],
        'menu_items' => $menuItems,
        'meal_schedules' => $schedules,
        'orders' => $orders,
        'order_items' => $orderItems,
        'payments' => $payments,
        'notifications' => $notifications,
    ];
}

function exportDatabaseSql(PDO $pdo): string {
    $tables = [];
    foreach ($pdo->query('SHOW TABLES') as $row) {
        $tables[] = $row[array_key_first($row)];
    }

    $preferredOrder = ['users', 'restaurants', 'menu_items', 'orders', 'order_items', 'payments', 'meal_schedules', 'notifications'];
    $orderedTables = [];
    foreach ($preferredOrder as $table) {
        if (in_array($table, $tables, true)) {
            $orderedTables[] = $table;
        }
    }
    foreach ($tables as $table) {
        if (!in_array($table, $orderedTables, true)) {
            $orderedTables[] = $table;
        }
    }

    $sqlParts = [];
    foreach ($orderedTables as $table) {
        $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC);
        $sqlParts[] = buildInsertStatements($table, $rows);
    }

    return implode("\n", $sqlParts) . "\n";
}

function exportRestaurantSql(PDO $pdo, int $restaurantId): string {
    $data = listRestaurantBackupData($pdo, $restaurantId);
    if ($data === []) {
        return "-- No backup data found for restaurant {$restaurantId}\n";
    }

    $sqlParts = [];
    foreach (['users', 'restaurants', 'menu_items', 'meal_schedules', 'orders', 'order_items', 'payments', 'notifications'] as $table) {
        if (isset($data[$table])) {
            $sqlParts[] = buildInsertStatements($table, $data[$table]);
        }
    }

    return implode("\n", $sqlParts) . "\n";
}

function createBackupFile(string $backupDir, string $prefix, string $content): string {
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Unable to create backup directory.');
        }
    }

    $backupFile = $backupDir . '/' . $prefix . '_' . date('Ymd_His') . '.sql';
    if (file_put_contents($backupFile, $content) === false) {
        throw new RuntimeException('Unable to write backup file.');
    }

    return $backupFile;
}

function tryMysqlDumpBackup(array $dbConfig, string $backupFile): bool {
    $candidates = [
        'mysqldump',
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
        'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
    ];

    foreach ($candidates as $binary) {
        $command = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s %s > "%s" 2>&1',
            str_replace('"', '""', $binary),
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['port']),
            escapeshellarg($dbConfig['user']),
            escapeshellarg($dbConfig['pass']),
            escapeshellarg($dbConfig['name']),
            str_replace('"', '""', $backupFile)
        );

        exec($command, $output, $exitCode);
        if ($exitCode === 0 && is_file($backupFile)) {
            return true;
        }

        if (is_file($backupFile)) {
            @unlink($backupFile);
        }
    }

    return false;
}

function listBackupFiles(string $backupDir): array {
    if (!is_dir($backupDir)) {
        return [];
    }

    $files = glob($backupDir . '/*.sql');
    if ($files === false) {
        return [];
    }

    usort($files, static fn(string $a, string $b): int => strnatcasecmp(basename($a), basename($b)));
    return array_values(array_filter($files, 'is_file'));
}

function extractTablesFromSql(string $sql): array {
    preg_match_all('/INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?\s*\(/i', $sql, $matches);
    $tables = array_map('strtolower', $matches[1] ?? []);
    return array_values(array_unique($tables));
}

function restoreSqlFile(PDO $pdo, string $backupFile): void {
    $sql = file_get_contents($backupFile);
    if ($sql === false) {
        throw new RuntimeException('Unable to read backup file.');
    }

    $tables = extractTablesFromSql($sql);
    $preferredOrder = ['notifications', 'meal_schedules', 'payments', 'order_items', 'orders', 'menu_items', 'restaurants', 'users'];
    $orderedTables = [];
    foreach ($preferredOrder as $table) {
        if (in_array($table, $tables, true)) {
            $orderedTables[] = $table;
        }
    }
    foreach ($tables as $table) {
        if (!in_array($table, $orderedTables, true)) {
            $orderedTables[] = $table;
        }
    }

    foreach ($orderedTables as $table) {
        $pdo->exec('DELETE FROM `' . str_replace('`', '``', $table) . '`');
    }

    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '' || preg_match('/^(?:--|#)/', $statement)) {
            continue;
        }

        $pdo->exec($statement);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'backup';
    $backupDir = __DIR__ . '/db';

    try {
        if ($action === 'restore') {
            $selectedFile = trim((string) ($_POST['backup_file'] ?? ''));
            if ($selectedFile === '') {
                throw new RuntimeException('A backup file must be selected for restore.');
            }

            $backupPath = realpath($backupDir . '/' . basename($selectedFile));
            if ($backupPath === false || !is_file($backupPath)) {
                throw new RuntimeException('The selected backup file does not exist.');
            }

            restoreSqlFile($pdo, $backupPath);
            flash('success', 'Backup restored successfully.');
        } else {
            $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
            $prefix = $restaurantId > 0 ? 'restaurant_' . $restaurantId : 'foodlink';

            if ($restaurantId > 0) {
                $backupSql = exportRestaurantSql($pdo, $restaurantId);
                $backupFile = createBackupFile($backupDir, $prefix, $backupSql);
            } else {
                $backupSql = exportDatabaseSql($pdo);
                $backupFile = createBackupFile($backupDir, $prefix, $backupSql);
            }

            if (is_file($backupFile)) {
                flash('success', $restaurantId > 0 ? 'Restaurant backup created successfully.' : 'Database backup created successfully.');
            } else {
                flash('danger', 'Backup could not be created. Ensure MySQL credentials are correct and the database is accessible.');
            }
        }
    } catch (Throwable $e) {
        flash('danger', 'Backup could not be created. Ensure MySQL credentials are correct and the database is accessible.');
        if ($action === 'restore') {
            flash('danger', 'Backup could not be restored. Verify the backup file and database permissions.');
        }
        error_log('Backup operation failed: ' . $e->getMessage());
    }

    header('Location: backup.php');
    exit;
}

$restaurants = $pdo->query('SELECT * FROM restaurants ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$backupFiles = listBackupFiles(__DIR__ . '/db');
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="margin-top:2rem;">
    <h2>System backup</h2>
    <p>Administrators can create a backup of the full FoodLink database or export data for each restaurant individually.</p>

    <form method="post" style="margin-bottom:1.5rem;">
        <input type="hidden" name="action" value="backup">
        <input type="hidden" name="restaurant_id" value="0">
        <button class="btn btn-primary" type="submit">Create full system backup</button>
    </form>

    <h3>Restaurant backups</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Restaurant</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($restaurants)): ?>
                <tr><td colspan="3">No restaurants exist yet.</td></tr>
            <?php else: ?>
                <?php foreach ($restaurants as $restaurant): ?>
                    <tr>
                        <td><?= e($restaurant['name']) ?></td>
                        <td><span class="badge"><?= e($restaurant['status']) ?></span></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="backup">
                                <input type="hidden" name="restaurant_id" value="<?= (int) $restaurant['id'] ?>">
                                <button class="btn btn-secondary" type="submit">Backup restaurant</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Restore from saved backup</h3>
    <form method="post">
        <input type="hidden" name="action" value="restore">
        <label>Select backup file</label>
        <select name="backup_file" required>
            <option value="">Choose a backup file</option>
            <?php foreach ($backupFiles as $backupFile): ?>
                <option value="<?= e(basename($backupFile)) ?>"><?= e(basename($backupFile)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit" style="margin-top:.75rem;">Restore backup</button>
    </form>

    <h3>Saved backups</h3>
    <table class="table">
        <thead>
            <tr>
                <th>File</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($backupFiles)): ?>
                <tr><td colspan="2">No backup files exist yet.</td></tr>
            <?php else: ?>
                <?php foreach ($backupFiles as $backupFile): ?>
                    <tr>
                        <td><?= e(basename($backupFile)) ?></td>
                        <td><a class="btn btn-secondary" href="db/<?= e(basename($backupFile)) ?>" download>Download</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
