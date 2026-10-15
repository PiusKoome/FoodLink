<?php
require_once __DIR__ . '/includes/db.php';
requireLogin();

$notificationId = (int)($_GET['id'] ?? 0);
$redirect = trim((string)($_GET['redirect'] ?? 'dashboard.php'));

if ($notificationId > 0) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $_SESSION['user_id']]);
}

header('Location: ' . ($redirect !== '' ? $redirect : 'dashboard.php'));
exit;
