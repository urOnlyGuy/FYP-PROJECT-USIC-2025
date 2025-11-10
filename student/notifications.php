<?php
// student/notifications.php
// View all notifications

session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../pages/login.php');
    exit;
}

// Handle mark as read
if (isset($_POST['mark_read'])) {
    $notificationId = $_POST['notification_id'];
    mark_notification_read($_SESSION['user_id'], $notificationId);
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    mark_all_notifications_read($_SESSION['user_id']);
    $message = 'All notifications marked as read';
}

// Get user's notifications
$notifications = get_user_notifications($_SESSION['user_id'], 50);
$unreadCount = get_unread_count($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | USIC - UPTM Info Center</title>
    
    <!-- PWA Support -->
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .notification-item {
            transition: background-color 0.2s;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-unread {
            background-color: #e7f3ff;
            border-left: 4px solid #19519D;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Mobile-Friendly Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #19519D;">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> UPTM Info
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house-fill"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="favourites.php">
                            <i class="bi bi-star-fill"></i> Favorites
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="notifications.php">
                            <i class="bi bi-bell-fill"></i> Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h4 class="mb-0">
                            <i class="bi bi-bell-fill"></i> Notifications
                        </h4>
                        <p class="text-muted mb-0">
                            <?= $unreadCount > 0 ? "$unreadCount unread" : "All caught up!" ?>
                        </p>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-check-all"></i> Mark all as read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="card border-0 shadow-sm">
            <?php if (empty($notifications)): ?>
                <!-- Empty State -->
                <div class="card-body text-center py-5">
                    <i class="bi bi-bell-slash" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="text-muted mt-3">No notifications yet</h5>
                    <p class="text-muted">We'll notify you when there are new posts</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item notification-item <?= !$notification['isRead'] ? 'notification-unread' : '' ?>">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <?= !$notification['isRead'] ? '<span class="badge bg-primary me-2">New</span>' : '' ?>
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?= htmlspecialchars($notification['message']) ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= time_ago($notification['createdAt']) ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2 ms-3">
                                    <a href="post.php?id=<?= $notification['postId'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                    <?php if (!$notification['isRead']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                            <button type="submit" 
                                                    name="mark_read" 
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Mark as read">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom spacing for mobile -->
    <div class="pb-5 mb-5"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>