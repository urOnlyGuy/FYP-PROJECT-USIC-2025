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

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $faqId = $_GET['id'];
    $result = delete_faq($faqId);
    
    if ($result) {
        $message = 'FAQ deleted successfully!';
        $messageType = 'success';
    } else {
        $message = 'Failed to delete FAQ.';
        $messageType = 'danger';
    }
}

// Handle toggle active status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $faqId = $_GET['id'];
    $faq = get_faq($faqId);
    
    if ($faq) {
        $result = update_faq($faqId, ['isActive' => !($faq['isActive'] ?? true)]);
        
        if ($result['success']) {
            $message = 'FAQ status updated!';
            $messageType = 'success';
        }
    }
}

// Get all FAQs (including inactive)
$allFaqs = get_all_faqs(false);
$categories = get_faq_categories();

// Get filter
$filterCategory = $_GET['category'] ?? 'all';
if ($filterCategory !== 'all') {
    $allFaqs = array_filter($allFaqs, function($faq) use ($filterCategory) {
        return $faq['category'] === $filterCategory;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQ | USIC Admin</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .faq-item {
            transition: all 0.3s ease;
        }
        .faq-item:hover {
            background-color: #f8f9fa;
        }
        .inactive-faq {
            opacity: 0.6;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" style="background-color: #16519E !important;">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> USIC - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['email']) ?>
                </span>
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
                <h4><i class="bi bi-question-circle-fill"></i> Manage FAQ</h4>
            </div>
            <div class="col-md-6 text-end">
                <a href="create_faq.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New FAQ
                </a>
                <a href="../pages/faq.php" class="btn btn-outline-secondary" target="_blank">
                    <i class="bi bi-eye"></i> View Public FAQ
                </a>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="btn-group d-flex flex-wrap" role="group">
                    <a href="?category=all" 
                       class="btn btn-sm btn-outline-primary <?= $filterCategory === 'all' ? 'active' : '' ?> flex-fill">
                        All Categories
                    </a>
                    <?php foreach ($categories as $catId => $cat): ?>
                        <a href="?category=<?= $catId ?>" 
                           class="btn btn-sm btn-outline-primary <?= $filterCategory === $catId ? 'active' : '' ?> flex-fill">
                            <?= $cat['emoji'] ?> <?= $cat['name'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- FAQ List -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    FAQ Entries (<?= count($allFaqs) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($allFaqs)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No FAQs found. Create your first FAQ entry!</p>
                        <a href="create_faq.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add New FAQ
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">Order</th>
                                    <th width="10%">Category</th>
                                    <th width="35%">Question</th>
                                    <th width="35%">Answer</th>
                                    <th width="8%">Status</th>
                                    <th width="7%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allFaqs as $faq): ?>
                                    <tr class="faq-item <?= !($faq['isActive'] ?? true) ? 'inactive-faq' : '' ?>">
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= $faq['order'] ?? 999 ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $cat = $categories[$faq['category']] ?? ['emoji' => '❓', 'name' => 'Unknown'];
                                            echo $cat['emoji'] . ' ' . $cat['name'];
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($faq['question']) ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr(strip_tags($faq['answer']), 0, 100)) ?>...
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($faq['isActive'] ?? true): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_faq.php?id=<?= $faq['id'] ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?action=toggle&id=<?= $faq['id'] ?>" 
                                                   class="btn btn-outline-warning"
                                                   title="Toggle Active Status">
                                                    <i class="bi bi-toggle-<?= ($faq['isActive'] ?? true) ? 'on' : 'off' ?>"></i>
                                                </a>
                                                <a href="?action=delete&id=<?= $faq['id'] ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this FAQ?')"
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>