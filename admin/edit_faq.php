<?php
session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is admin or staff
if (!is_logged_in() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: ../pages/login.php');
    exit;
}

$faqId = $_GET['id'] ?? null;
if (!$faqId) {
    header('Location: manage_faq.php');
    exit;
}

$faq = get_faq($faqId);
if (!$faq) {
    header('Location: manage_faq.php?error=not_found');
    exit;
}

$message = '';
$messageType = '';
$categories = get_faq_categories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $category = $_POST['category'];
    $order = intval($_POST['order']);
    $isActive = isset($_POST['is_active']);
    
    if (empty($question) || empty($answer)) {
        $message = 'Question and Answer are required.';
        $messageType = 'danger';
    } else {
        $faqData = [
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'order' => $order,
            'isActive' => $isActive
        ];
        
        $result = update_faq($faqId, $faqData);
        
        if ($result['success']) {
            $message = 'FAQ updated successfully!';
            $messageType = 'success';
            $faq = get_faq($faqId); // Refresh data
            
            // Redirect after 2 seconds
            header("refresh:2;url=manage_faq.php");
        } else {
            $message = 'Failed to update FAQ.';
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit FAQ | USIC Admin</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> USIC Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a href="manage_faq.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to FAQ List
                </a>
                <a href="../pages/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit FAQ</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <?php foreach ($categories as $catId => $cat): ?>
                                        <option value="<?= $catId ?>" <?= $faq['category'] === $catId ? 'selected' : '' ?>>
                                            <?= $cat['emoji'] ?> <?= $cat['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Question -->
                            <div class="mb-3">
                                <label class="form-label">Question <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="question" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($faq['question']) ?>"
                                       required
                                       maxlength="500">
                            </div>

                            <!-- Answer -->
                            <div class="mb-3">
                                <label class="form-label">Answer <span class="text-danger">*</span></label>
                                <textarea name="answer" 
                                          class="form-control" 
                                          rows="8"
                                          required><?= htmlspecialchars($faq['answer']) ?></textarea>
                                <small class="text-muted">
                                    You can use basic HTML: &lt;strong&gt;, &lt;br&gt;, &lt;a href=""&gt;, &lt;ul&gt;, &lt;li&gt;
                                </small>
                            </div>

                            <!-- Order -->
                            <div class="mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" 
                                       name="order" 
                                       class="form-control" 
                                       value="<?= $faq['order'] ?? 999 ?>"
                                       min="1"
                                       max="9999">
                            </div>

                            <!-- Active Status -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="is_active" 
                                           id="isActive"
                                           <?= ($faq['isActive'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">
                                        <i class="bi bi-toggle-on"></i> Active (visible to users)
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update FAQ
                                </button>
                                <a href="manage_faq.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            <small>
                                <i class="bi bi-info-circle"></i>
                                Created: <?= date('d M Y H:i', $faq['createdAt']) ?>
                                <?php if (isset($faq['updatedAt']) && $faq['updatedAt'] !== $faq['createdAt']): ?>
                                    | Last Updated: <?= date('d M Y H:i', $faq['updatedAt']) ?>
                                <?php endif; ?>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>