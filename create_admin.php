<?php
require __DIR__ . '/includes/db.php';

$email = 'admin@example.com';
$existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$existing->execute([$email]);

if (!$existing->fetchColumn()) {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, phone, location) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        'Admin User',
        $email,
        password_hash('password123', PASSWORD_DEFAULT),
        'admin',
        '0700000000',
        'Nairobi'
    ]);
}

echo 'Admin account ready';
