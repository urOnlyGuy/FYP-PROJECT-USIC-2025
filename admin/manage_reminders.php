<?php
session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is admin or staff
if (!is_logged_in() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: ../pages/login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle manual reminder check
if (isset($_GET['action']) && $_GET['action'] === 'check_reminders') {
    $result = check_and_send_reminders();
    $message = 'Checked reminders. Sent ' . $result['sent'] . ' notification(s).';
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $reminderId = $_GET['id'];
    delete_post_reminder($reminderId);
    $message = 'Reminder deleted successfully!';
    $messageType = 'success';
}

// Handle toggle active
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $reminderId = $_GET['id'];
    $reminder = get_post_reminder($reminderId);
    
    if ($reminder) {
        update_post_reminder($reminderId, ['isActive' => !($reminder['isActive'] ?? true)]);
        $message = 'Reminder status updated!';
        $messageType = 'success';
    }
}

// Get all reminders
$allReminders = get_all_reminders(false);
$currentTime = time();

// Separate active and expired
$activeReminders = [];
$expiredReminders = [];

foreach ($allReminders as $reminder) {
    if ($reminder['deadlineDate'] > $currentTime) {
        $activeReminders[] = $reminder;
    } else {
        $expiredReminders[] = $reminder;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reminders | USIC Admin</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" style="background-color: #16519E !important;">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> USIC - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-grid"></i> Dashboard
                </a>
                <a href="../pages/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <h4><i class="bi bi-alarm-fill"></i> Manage Reminders</h4>
            </div>
            <div class="col-md-6 text-end">
                <a href="?action=check_reminders" class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise"></i> Check & Send Due Reminders
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h3 class="mb-0"><?= count($activeReminders) ?></h3>
                        <small>Active Reminders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-secondary">
                    <div class="card-body">
                        <h3 class="mb-0"><?= count($expiredReminders) ?></h3>
                        <small>Expired Reminders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h3 class="mb-0"><?= count($allReminders) ?></h3>
                        <small>Total Reminders</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Reminders -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i> Active Reminders (<?= count($activeReminders) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeReminders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No active reminders</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Post</th>
                                    <th>Reminder Title</th>
                                    <th>Deadline</th>
                                    <th>Days Left</th>
                                    <th>Intervals</th>
                                    <th>Sent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeReminders as $reminder): ?>
                                    <?php 
                                    $post = get_post($reminder['id']);
                                    $daysLeft = ceil(($reminder['deadlineDate'] - $currentTime) / (60 * 60 * 24));
                                    $sentCount = count($reminder['sentReminders'] ?? []);
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="../student/post.php?id=<?= $reminder['id'] ?>" target="_blank">
                                                <?= $post ? htmlspecialchars(substr($post['title'], 0, 50)) : 'Post Deleted' ?>...
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($reminder['title']) ?></td>
                                        <td>
                                            <small><?= date('d M Y, h:i A', $reminder['deadlineDate']) ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $badgeClass = $daysLeft <= 1 ? 'bg-danger' : ($daysLeft <= 3 ? 'bg-warning text-dark' : 'bg-info');
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= $daysLeft ?> day<?= $daysLeft != 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                <?php 
                                                $days = $reminder['reminderDays'] ?? [1, 3, 7];
                                                sort($days);
                                                echo implode(', ', $days) . ' days';
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $sentCount ?> sent</span>
                                        </td>
                                        <td>
                                            <?php if ($reminder['isActive'] ?? true): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_post.php?id=<?= $reminder['id'] ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="Edit Post & Reminder">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?action=toggle&id=<?= $reminder['id'] ?>" 
                                                   class="btn btn-outline-warning"
                                                   title="Toggle Status">
                                                    <i class="bi bi-toggle-<?= ($reminder['isActive'] ?? true) ? 'on' : 'off' ?>"></i>
                                                </a>
                                                <a href="?action=delete&id=<?= $reminder['id'] ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Delete this reminder?')"
                                                   title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expired Reminders -->
        <?php if (!empty($expiredReminders)): ?>
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-clock"></i> Expired Reminders (<?= count($expiredReminders) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 opacity-75">
                        <thead class="table-light">
                            <tr>
                                <th>Post</th>
                                <th>Reminder Title</th>
                                <th>Deadline</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiredReminders as $reminder): ?>
                                <?php $post = get_post($reminder['id']); ?>
                                <tr>
                                    <td>
                                        <?= $post ? htmlspecialchars(substr($post['title'], 0, 50)) : 'Post Deleted' ?>...
                                    </td>
                                    <td><?= htmlspecialchars($reminder['title']) ?></td>
                                    <td>
                                        <small class="text-muted"><?= date('d M Y', $reminder['deadlineDate']) ?> (Expired)</small>
                                    </td>
                                    <td>
                                        <a href="?action=delete&id=<?= $reminder['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this reminder?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Info Box -->
        <div class="alert alert-info mt-4">
            <h6><i class="bi bi-info-circle"></i> How Reminders Work</h6>
            <ul class="mb-0">
                <li>Reminders are automatically checked and sent based on the intervals you set (1, 3, 7 days before deadline)</li>
                <li>Students receive both in-app notifications and email reminders</li>
                <li>Click "Check & Send Due Reminders" to manually trigger the reminder check</li>
                <li>For automatic reminders, set up a cron job to call the check_and_send_reminders() function daily</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>