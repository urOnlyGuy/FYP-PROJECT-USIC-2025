<?php
// student/post.php
// View single post with full details

session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../pages/login.php');
    exit;
}

// Get post ID
$postId = $_GET['id'] ?? null;

if (!$postId) {
    header('Location: dashboard.php');
    exit;
}

// Get post
$post = get_post($postId);

if (!$post) {
    header('Location: dashboard.php');
    exit;
}

//Increment view count
increment_view_count($postId);

//check if favourited
$isFavorited = is_favorited($_SESSION['user_id'], $postId);

// handle favourite toggle
if (isset($_POST['toggle_favorite'])) {
    if ($isFavorited) {
        remove_from_favorites($_SESSION['user_id'], $postId);
        $isFavorited = false;
        $message = 'Removed from favorites';
    } else {
        add_to_favorites($_SESSION['user_id'], $postId);
        $isFavorited = true;
        $message = 'Added to favorites';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> | USIC - UPTM Info Center</title>
    
    <!-- PWA Support -->
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .post-header-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .post-content img {
            max-width: 100%;
            height: auto;
        }
        .favorite-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 768px) {
            .post-header-image {
                max-height: 250px;
            }
            .post-content {
                font-size: 1rem;
            }
            .favorite-btn {
                width: 50px;
                height: 50px;
                bottom: 15px;
                right: 15px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-dark" style="background-color: #19519D;">
        <div class="container">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <span class="navbar-brand mx-auto">Post Details</span>
            <div style="width: 80px;"></div> <!-- Spacer for centering -->
        </div>
    </nav>

    <div class="container mt-4 mb-5 pb-5">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Post Card -->
        <div class="card border-0 shadow-sm">
            <!-- Header Image -->
            <img src="<?= htmlspecialchars($post['headerImage']) ?>" 
                 class="post-header-image" 
                 alt="<?= htmlspecialchars($post['title']) ?>">
            
            <div class="card-body">
                <!-- Category Badge -->
                <span class="badge mb-2" style="background-color: #19519D;">
                    <?= htmlspecialchars($post['category']) ?>
                </span>
                
                <!-- Title -->
                <h2 class="card-title mb-3"><?= htmlspecialchars($post['title']) ?></h2>
                
                <!-- Meta Info -->
                <div class="d-flex flex-wrap gap-3 mb-4 text-muted">
                    <small>
                        <i class="bi bi-clock"></i> 
                        <?= format_date($post['createdAt']) ?>
                    </small>
                    <small>
                        <i class="bi bi-eye"></i> 
                        <?= $post['viewCount'] ?? 0 ?> views
                    </small>
                </div>
                
                <hr>
                
                <!-- Content -->
                <div class="post-content">
                    <?= $post['content'] ?>
                </div>
                
                <!-- Attachment -->
                <?php if (!empty($post['attachmentUrl'])): ?>
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6><i class="bi bi-paperclip"></i> Attachment</h6>
                        <a href="<?= htmlspecialchars($post['attachmentUrl']) ?>" 
                           class="btn btn-outline-primary" 
                           download
                           target="_blank">
                            <i class="bi bi-download"></i> 
                            Download <?= htmlspecialchars($post['attachmentName'] ?? 'File') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Floating Favorite Button -->
    <form method="POST" style="display: inline;">
        <button type="submit" 
                name="toggle_favorite" 
                class="btn favorite-btn <?= $isFavorited ? 'btn-warning' : 'btn-outline-warning' ?>"
                title="<?= $isFavorited ? 'Remove from favorites' : 'Add to favorites' ?>">
            <i class="bi bi-star<?= $isFavorited ? '-fill' : '' ?>" style="font-size: 1.5rem;"></i>
        </button>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>