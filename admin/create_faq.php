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
$categories = get_all_faq_categories(true); // Get active categories only

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question']);
    $answer = $_POST['answer']; // Rich text from TinyMCE
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
            'isActive' => $isActive,
            'createdBy' => $_SESSION['user_id']
        ];
        
        $result = create_faq($faqData);
        
        if ($result['success']) {
            $message = 'FAQ created successfully!';
            $messageType = 'success';
            
            // Redirect after 2 seconds
            header("refresh:2;url=manage_faq.php");
        } else {
            $message = 'Failed to create FAQ.';
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
    <title>Create FAQ | USIC Admin</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/<?= getenv('TINYMCE_API_KEY') ?: 'no-api-key' ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#answer',
            height: 400,
            menubar: false,
            plugins: [
                'lists', 'link', 'charmap', 'preview',
                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | removeformat | code | help',
            content_style: 'body { font-family:Arial,sans-serif; font-size:14px }'
        });
    </script>
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
                        <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Create New FAQ</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="createFaqForm">
                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php if (empty($categories)): ?>
                                        <option value="" disabled>No categories available. Please create one first.</option>
                                    <?php else: ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>">
                                                <?= htmlspecialchars($cat['emoji']) ?> <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">
                                    <a href="manage_faq_categories.php" target="_blank">Manage Categories</a>
                                </small>
                            </div>

                            <!-- Question -->
                            <div class="mb-3">
                                <label class="form-label">Question <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="question" 
                                       class="form-control" 
                                       placeholder="Enter the question"
                                       required
                                       maxlength="500">
                                <small class="text-muted">Keep it clear and concise</small>
                            </div>

                            <!-- Answer (TinyMCE) -->
                            <div class="mb-3">
                                <label class="form-label">Answer <span class="text-danger">*</span></label>
                                <textarea id="answer" name="answer"></textarea>
                                <small class="text-muted">Use the editor toolbar to format your answer.</small>
                            </div>

                            <!-- Order -->
                            <div class="mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" 
                                       name="order" 
                                       class="form-control" 
                                       value="999"
                                       min="1"
                                       max="9999">
                                <small class="text-muted">Lower numbers appear first (default: 999)</small>
                            </div>

                            <!-- Active Status -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="is_active" 
                                           id="isActive"
                                           checked>
                                    <label class="form-check-label" for="isActive">
                                        <i class="bi bi-toggle-on"></i> Active (visible to users)
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Create FAQ
                                </button>
                                <a href="manage_faq.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('createFaqForm').addEventListener('submit', function(e) {
            const question = document.querySelector('[name="question"]').value.trim();
            const editor = tinymce.get('answer');
            
            if (!question) {
                e.preventDefault();
                alert('Please enter a question');
                return false;
            }
            
            if (editor && editor.getContent().trim() === '') {
                e.preventDefault();
                alert('Answer cannot be empty.');
                editor.focus();
                return false;
            }
        });
    </script>
</body>
</html>