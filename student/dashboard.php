<?php
// student/dashboard.php
// Student main feed - view all posts

session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notifications.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../pages/login.php');
    exit;
}

// Redirect admin/staff to their dashboard
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Get filter and search
$filterCategory = $_GET['category'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Get all posts
$allPosts = get_all_posts();

// Apply search filter
if (!empty($searchQuery)) {
    $allPosts = search_posts($searchQuery);
}

// Apply category filter
if ($filterCategory !== 'all') {
    $allPosts = array_filter($allPosts, function($post) use ($filterCategory) {
        return isset($post['category']) && $post['category'] === $filterCategory;
    });
}

// Get user's unread notification count
$unreadCount = get_unread_count($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | USIC - UPTM Student Info Center</title>
    
    <!-- PWA Support -->
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        /* Mobile-first responsive styles */
        .post-card {
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .post-card img {
            height: 200px;
            object-fit: cover;
        }
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .notification-badge {
            position: relative;
            top: -2px;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .post-card img {
                height: 150px;
            }
            .navbar-brand {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Mobile-Friendly Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #19519D;">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> | USIC - UPTM Student Info Center
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house-fill"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="favourite.php">
                            <i class="bi bi-star-fill"></i> Favorites
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="notifications.php">
                            <i class="bi bi-bell-fill"></i> Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger notification-badge"><?= $unreadCount ?></span>
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
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col">
                <h4 class="mb-0">Welcome, <?= htmlspecialchars(explode('@', $_SESSION['email'])[0]) ?>!</h4>
                <p class="text-muted">Stay updated with the latest announcements</p>
            </div>
        </div>

        <!-- Search and Filter Bar (Mobile-Responsive) -->
        <div class="row mb-4">
            <div class="col-12">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search posts..." 
                               value="<?= htmlspecialchars($searchQuery) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Filter (Mobile-Optimized) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="btn-group w-100 d-flex flex-wrap" role="group">
                    <a href="?category=all&t=<?= time() ?>" 
                       class="btn btn-sm btn-outline-primary <?= $filterCategory === 'all' ? 'active' : '' ?> flex-fill">
                        All
                    </a>
                    <a href="?category=General&t=<?= time() ?>" 
                       class="btn btn-sm btn-outline-primary <?= $filterCategory === 'General' ? 'active' : '' ?> flex-fill">
                        General
                    </a>
                    <a href="?category=Finance&t=<?= time() ?>" 
                       class="btn btn-sm btn-outline-primary <?= $filterCategory === 'Finance' ? 'active' : '' ?> flex-fill">
                        Finance
                    </a>
                    <a href="?category=Academic&t=<?= time() ?>" 
                       class="btn btn-sm btn-outline-primary <?= $filterCategory === 'Academic' ? 'active' : '' ?> flex-fill">
                        Academic
                    </a>
                    <a href="?category=Events&t=<?= time() ?>" 
                       class="btn btn-sm btn-outline-primary <?= $filterCategory === 'Events' ? 'active' : '' ?> flex-fill">
                        Events
                    </a>
                </div>
            </div>
        </div>

        <!-- Posts Grid (Responsive Cards) -->
        <div class="row g-3">
            <?php if (empty($allPosts)): ?>
                <!-- Empty State -->
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <h5 class="text-muted mt-3">No posts found</h5>
                        <p class="text-muted">
                            <?php if (!empty($searchQuery)): ?>
                                Try a different search term
                            <?php elseif ($filterCategory !== 'all'): ?>
                                Try viewing all categories
                            <?php else: ?>
                                Check back later for updates
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($allPosts as $post): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card post-card border-0 shadow-sm">
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars($post['headerImage']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     loading="lazy">
                                <span class="badge category-badge" style="background-color: #19519D;">
                                    <?= htmlspecialchars($post['category']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h6>
                                <p class="card-text text-muted small">
                                    <?= substr(strip_tags($post['content']), 0, 80) ?>...
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= time_ago($post['createdAt']) ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-eye"></i> <?= $post['viewCount'] ?? 0 ?>
                                    </small>
                                </div>
                                <a href="post.php?id=<?= $post['id'] ?>" 
                                   class="btn btn-sm btn-primary w-100 mt-2">
                                    Read More
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom spacing for mobile -->
    <div class="pb-5 mb-5"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>