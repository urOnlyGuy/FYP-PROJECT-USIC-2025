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

// Handle create category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name']);
    $emoji = trim($_POST['emoji']);
    $icon = trim($_POST['icon']);
    $order = intval($_POST['order']);
    $isActive = isset($_POST['is_active']);
    
    if (empty($name)) {
        $message = 'Category name is required.';
        $messageType = 'danger';
    } else {
        $result = create_faq_category([
            'name' => $name,
            'emoji' => $emoji ?: '❓',
            'icon' => $icon ?: 'bi-question-circle',
            'order' => $order,
            'isActive' => $isActive,
            'createdBy' => $_SESSION['user_id']
        ]);
        
        if ($result['success']) {
            $message = 'Category created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create category.';
            $messageType = 'danger';
        }
    }
}

// Handle delete category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $categoryId = $_GET['id'];
    $result = delete_faq_category($categoryId);
    
    if ($result) {
        $message = 'Category deleted successfully!';
        $messageType = 'success';
    } else if (is_array($result) && isset($result['error'])) {
        $message = $result['error'];
        $messageType = 'danger';
    } else {
        $message = 'Failed to delete category.';
        $messageType = 'danger';
    }
}

// Handle toggle active
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $categoryId = $_GET['id'];
    $category = get_faq_category($categoryId);
    
    if ($category) {
        $result = update_faq_category($categoryId, ['isActive' => !($category['isActive'] ?? true)]);
        
        if ($result['success']) {
            $message = 'Category status updated!';
            $messageType = 'success';
        }
    }
}

// Get all categories
$categories = get_all_faq_categories(false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQ Categories | USIC Admin</title>
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
                <a href="manage_faq.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to FAQs
                </a>
                <a href="../pages/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Category Form -->
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       class="form-control" 
                                       placeholder="e.g., Getting Started"
                                       required
                                       maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Emoji</label>
                                <input type="text" 
                                       name="emoji" 
                                       class="form-control" 
                                       placeholder="e.g., 🚀"
                                       maxlength="10">
                                <small class="text-muted">Single emoji character</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bootstrap Icon Class</label>
                                <input type="text" 
                                       name="icon" 
                                       class="form-control" 
                                       placeholder="e.g., bi-rocket-takeoff-fill"
                                       maxlength="100">
                                <small class="text-muted">
                                    <a href="https://icons.getbootstrap.com/" target="_blank">Browse Bootstrap Icons</a>
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" 
                                       name="order" 
                                       class="form-control" 
                                       value="999"
                                       min="1"
                                       max="9999">
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="is_active" 
                                           id="isActive"
                                           checked>
                                    <label class="form-check-label" for="isActive">
                                        Active
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Create Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Categories List -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">FAQ Categories (<?= count($categories) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($categories)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No categories found. Create your first category!</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="10%">Order</th>
                                            <th width="50%">Category</th>
                                            <th width="20%">Status</th>
                                            <th width="20%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $cat): ?>
                                            <tr class="<?= !($cat['isActive'] ?? true) ? 'opacity-50' : '' ?>">
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $cat['order'] ?? 999 ?></span>
                                                </td>
                                                <td>
                                                    <i class="<?= htmlspecialchars($cat['icon']) ?>"></i>
                                                    <?= htmlspecialchars($cat['emoji']) ?>
                                                    <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($cat['isActive'] ?? true): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=toggle&id=<?= $cat['id'] ?>" 
                                                           class="btn btn-outline-warning"
                                                           title="Toggle Status">
                                                            <i class="bi bi-toggle-<?= ($cat['isActive'] ?? true) ? 'on' : 'off' ?>"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?= $cat['id'] ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirm('Delete this category? This will fail if any FAQs use this category.')"
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>