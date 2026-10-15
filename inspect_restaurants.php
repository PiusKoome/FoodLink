<?php
require __DIR__ . '/includes/db.php';
$stmt = $pdo->query('SELECT id, name, location, status FROM restaurants ORDER BY id');
foreach ($stmt->fetchAll() as $row) {
    echo $row['id'] . '|' . $row['name'] . '|' . $row['location'] . '|' . $row['status'] . PHP_EOL;
}
