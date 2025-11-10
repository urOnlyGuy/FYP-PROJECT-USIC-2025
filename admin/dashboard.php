<?php
// admin/dashboard.php
// Admin dashboard to view and manage all posts

session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check if user is admin or staff
if (!is_logged_in() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: ../pages/login.php');
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $postId = $_GET['id'];
    $result = delete_post($postId);
    
    if ($result) {
        $message = 'Post deleted successfully!';
        $messageType = 'success';
    } else {
        $message = 'Failed to delete post.';
        $messageType = 'danger';
    }
}

// Get all posts
$allPosts = get_all_posts();
$posts = $allPosts;

// Get filter
$filterCategory = $_GET['category'] ?? 'all';
if ($filterCategory !== 'all') {
    $posts = array_filter($allPosts, function($post) use ($filterCategory) {
        return isset($post['category']) && $post['category'] === $filterCategory;
    });
}

// Get statistics
$totalPosts = count($allPosts);
$totalViews = array_sum(array_column($allPosts, 'viewCount'));
$users = firebase_get('users');
$totalStudents = 0;
if ($users) {
    foreach ($users as $user) {
        if (isset($user['role']) && $user['role'] === 'student') {
            $totalStudents++;
        }
    }
}

// Get statistics
$totalPosts = count(get_all_posts());
$totalViews = array_sum(array_column(get_all_posts(), 'viewCount'));
$users = firebase_get('users');
$totalStudents = 0;
if ($users) {
    foreach ($users as $user) {
        if (isset($user['role']) && $user['role'] === 'student') {
            $totalStudents++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | USIC - UPTM Student Info Center</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> USIC - Admin 
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['email']) ?>
                    <span class="badge bg-warning text-dark"><?= strtoupper($_SESSION['role']) ?></span>
                </span>
                <a href="../pages/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Posts</h6>
                                <h2 class="mb-0"><?= $totalPosts ?></h2>
                            </div>
                            <i class="bi bi-file-earmark-text" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Views</h6>
                                <h2 class="mb-0"><?= $totalViews ?></h2>
                            </div>
                            <i class="bi bi-eye" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Students</h6>
                                <h2 class="mb-0"><?= $totalStudents ?></h2>
                            </div>
                            <i class="bi bi-people" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <a href="create_post.php" class="btn btn-light btn-lg w-100">
                            <i class="bi bi-plus-circle"></i> Create New Post
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col">
                <div class="btn-group" role="group">
                    <a href="create_post.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Post
                    </a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="manage_users.php" class="btn btn-secondary">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Category Filter -->
                <div class="btn-group ms-3" role="group">
                    <a href="?category=all" class="btn btn-outline-primary <?= $filterCategory === 'all' ? 'active' : '' ?>">All</a>
                    <a href="?category=General" class="btn btn-outline-primary <?= $filterCategory === 'General' ? 'active' : '' ?>">General</a>
                    <a href="?category=Finance" class="btn btn-outline-primary <?= $filterCategory === 'Finance' ? 'active' : '' ?>">Finance</a>
                    <a href="?category=Academic" class="btn btn-outline-primary <?= $filterCategory === 'Academic' ? 'active' : '' ?>">Academic</a>
                    <a href="?category=Events" class="btn btn-outline-primary <?= $filterCategory === 'Events' ? 'active' : '' ?>">Events</a>
                </div>
            </div>
        </div>

        <!-- Posts Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Posts</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No posts found. Create your first post!</p>
                        <a href="create_post.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Post
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">Image</th>
                                    <th>Title</th>
                                    <th style="width: 120px;">Category</th>
                                    <th style="width: 100px;">Views</th>
                                    <th style="width: 150px;">Created</th>
                                    <th style="width: 100px;">Notification</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= htmlspecialchars($post['headerImage']) ?>" 
                                                 alt="Thumbnail" 
                                                 class="img-thumbnail"
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($post['title']) ?></strong>
                                            <?php if ($post['attachmentUrl']): ?>
                                                <br><small class="text-muted">
                                                    <i class="bi bi-paperclip"></i> Has attachment
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($post['category']) ?></span>
                                        </td>
                                        <td>
                                            <i class="bi bi-eye"></i> <?= $post['viewCount'] ?? 0 ?>
                                        </td>
                                        <td>
                                            <small><?= time_ago($post['createdAt']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($post['notificationSent']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Sent
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-x-circle"></i> Not sent
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../student/post.php?id=<?= $post['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="View Post">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $post['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this post?')"
                                               title="Delete Post">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>