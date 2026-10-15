<?php require_once __DIR__ . '/db.php'; $flash = getFlash();
$notifications = [];
$unreadNotificationCount = 0;
if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'restaurant_owner') {
    try {
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $unreadNotificationCount = count(array_filter($notifications, function ($notification) {
            return (int)($notification['is_read'] ?? 0) === 0;
        }));
    } catch (PDOException $e) {
        $notifications = [];
        $unreadNotificationCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodLink</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* Ensure dropdown text is readable across browsers */
        select, select option, .meal-select, .meal-select option {
            color: var(--text) !important;
            background: #fff !important;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notification-bell {
            position: relative;
        }

        .notification-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #fff4ee;
            color: var(--primary);
            border: 1px solid #ffd5c1;
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
            cursor: pointer;
            font-weight: 700;
        }

        .notification-count {
            background: var(--primary);
            color: #fff;
            border-radius: 999px;
            padding: 0.15rem 0.45rem;
            font-size: 0.8rem;
            min-width: 1.35rem;
            text-align: center;
        }

        .notification-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 0.5rem);
            width: 280px;
            max-height: 320px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            z-index: 20;
            padding: 0.5rem;
        }

        .notification-menu a {
            display: block;
            padding: 0.7rem;
            border-radius: 8px;
            color: var(--text);
            border-bottom: 1px solid #f3f4f6;
        }

        .notification-menu a:last-child {
            border-bottom: 0;
        }

        .notification-menu a.unread {
            background: #fff7ed;
        }

        .notification-empty {
            padding: 0.8rem;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .notification-item strong {
            display: block;
            margin-bottom: 0.2rem;
        }

        .notification-item small {
            color: var(--muted);
            display: block;
            margin-top: 0.2rem;
        }

        .notification-bell[open] .notification-menu {
            display: block;
        }

        .notification-bell:not([open]) .notification-menu {
            display: none;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container nav">
            <a class="brand" href="index.php">FoodLink</a>
            <nav>
                <a href="index.php">Home</a>
                <a href="restaurants.php">Restaurants</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="profile.php">Profile</a>
                    <a href="schedule.php">Schedules</a>
                    <?php if ($_SESSION['role'] === 'restaurant_owner'): ?>
                        <a href="restaurant_management.php">My Restaurant</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="backup.php">Backup</a>
                    <?php endif; ?>
                    <div class="nav-actions">
                        <?php if (($_SESSION['role'] ?? '') === 'restaurant_owner'): ?>
                            <details class="notification-bell">
                                <summary class="notification-toggle">
                                    <span>🔔</span>
                                    <?php if ($unreadNotificationCount > 0): ?>
                                        <span class="notification-count"><?= (int)$unreadNotificationCount ?></span>
                                    <?php endif; ?>
                                </summary>
                                <div class="notification-menu">
                                    <?php if (empty($notifications)): ?>
                                        <div class="notification-empty">No notifications yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $notification): ?>
                                            <a class="notification-item <?= (int)($notification['is_read'] ?? 0) === 0 ? 'unread' : '' ?>" href="mark_notification.php?id=<?= (int)$notification['id'] ?>&redirect=<?= rawurlencode($notification['link'] ?? 'dashboard.php') ?>">
                                                <strong><?= e($notification['title']) ?></strong>
                                                <div><?= e($notification['message']) ?></div>
                                                <small><?= e(date('M d, H:i', strtotime($notification['created_at']))) ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (!empty($flash)): ?>
            <?php foreach ($flash as $type => $message): ?>
                <div class="alert alert-<?= e($type) ?>"><?= e($message) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
