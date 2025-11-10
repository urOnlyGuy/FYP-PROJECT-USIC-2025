<?php
session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check if user is admin or staff
if (!is_logged_in() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: ../pages/login.php');
    exit;
}

$postId = $_GET['id'] ?? null;
if (!$postId) {
    header('Location: dashboard.php');
    exit;
}

$post = get_post($postId);
if (!$post) {
    header('Location: dashboard.php?error=post_not_found');
    exit;
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $category = $_POST['category'] ?? 'General';
    
    // Sanitize HTML content
    $content = sanitize_html_content($content);
    
    $headerImageUrl = $post['headerImage']; // Keep existing
    $attachmentUrl = $post['attachmentUrl'] ?? null;
    $attachmentName = $post['attachmentName'] ?? null;
    
    // Handle new header image upload
    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = upload_image_to_storage($_FILES['header_image'], 'posts/headers');
        
        if ($uploadResult['success']) {
            $headerImageUrl = $uploadResult['url'];
        } else {
            $message = 'Error uploading image: ' . $uploadResult['error'];
            $messageType = 'danger';
        }
    }
    
    // Handle new attachment upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = upload_file_to_storage($_FILES['attachment'], 'posts/attachments');
        
        if ($uploadResult['success']) {
            $attachmentUrl = $uploadResult['url'];
            $attachmentName = $uploadResult['originalName'];
        } else {
            $message = 'Error uploading attachment: ' . $uploadResult['error'];
            $messageType = 'danger';
        }
    }
    
    // Update post if no upload errors
    if (empty($message)) {
        $postData = [
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'headerImage' => $headerImageUrl,
            'attachmentUrl' => $attachmentUrl,
            'attachmentName' => $attachmentName
        ];
        
        $result = update_post($postId, $postData);
        
        if ($result['success']) {
            $message = 'Post updated successfully!';
            $messageType = 'success';
            $post = get_post($postId); // Refresh
            
            header("refresh:2;url=dashboard.php");
        } else {
            $message = 'Failed to update post: ' . ($result['error'] ?? 'Unknown error');
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
    <title>Edit Post | USIC Admin</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/<?= getenv('TINYMCE_API_KEY') ?: 'no-api-key' ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: [
                'lists', 'link', 'image', 'charmap', 'preview',
                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat | help',
            content_style: 'body { font-family:Arial,sans-serif; font-size:14px }'
        });
    </script>
    
    <style>
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> UPTM Info Center
            </a>
            <div class="navbar-nav ms-auto">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
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
                        <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Post</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="editPostForm">
                            
                            <!-- Title -->
                            <div class="mb-3">
                                <label class="form-label">Post Title <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="title" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($post['title']) ?>"
                                       required
                                       maxlength="200">
                            </div>

                            <!-- Current Header Image -->
                            <div class="mb-3">
                                <label class="form-label">Current Header Image</label>
                                <div>
                                    <img src="<?= htmlspecialchars($post['headerImage']) ?>" 
                                         alt="Current header" 
                                         class="image-preview"
                                         style="display: block;">
                                </div>
                            </div>

                            <!-- New Header Image -->
                            <div class="mb-3">
                                <label class="form-label">Upload New Header Image (Optional)</label>
                                <input type="file" 
                                       name="header_image" 
                                       class="form-control" 
                                       accept="image/*"
                                       id="headerImageInput"
                                       onchange="previewImage(this)">
                                <small class="text-muted">Leave empty to keep current image. Max 5MB.</small>
                                <img id="imagePreview" class="image-preview" alt="New preview" style="display: none;">
                            </div>

                            <!-- Content (Rich Text Editor) -->
                            <div class="mb-3">
                                <label class="form-label">Content <span class="text-danger">*</span></label>
                                <textarea id="content" name="content"><?= htmlspecialchars($post['content']) ?></textarea>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <option value="General" <?= $post['category'] === 'General' ? 'selected' : '' ?>>General</option>
                                    <option value="Finance" <?= $post['category'] === 'Finance' ? 'selected' : '' ?>>Finance</option>
                                    <option value="Academic" <?= $post['category'] === 'Academic' ? 'selected' : '' ?>>Academic</option>
                                    <option value="Events" <?= $post['category'] === 'Events' ? 'selected' : '' ?>>Events</option>
                                </select>
                            </div>

                            <!-- Current Attachment -->
                            <?php if (!empty($post['attachmentUrl'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Attachment</label>
                                    <div>
                                        <a href="<?= htmlspecialchars($post['attachmentUrl']) ?>" 
                                           class="btn btn-sm btn-outline-secondary" 
                                           target="_blank">
                                            <i class="bi bi-paperclip"></i> 
                                            <?= htmlspecialchars($post['attachmentName'] ?? 'Download File') ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- New Attachment -->
                            <div class="mb-4">
                                <label class="form-label">Upload New Attachment (Optional)</label>
                                <input type="file" 
                                       name="attachment" 
                                       class="form-control"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.txt"
                                       id="attachmentInput"
                                       onchange="showFileName(this)">
                                <small class="text-muted">
                                    Leave empty to keep current attachment. PDF, DOC, XLS, TXT. Max 10MB.
                                </small>
                                <div id="fileName" class="mt-2 text-success" style="display: none;"></div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Post
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
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
                                Created: <?= format_date($post['createdAt']) ?>
                                <?php if (isset($post['updatedAt']) && $post['updatedAt'] !== $post['createdAt']): ?>
                                    | Last Updated: <?= format_date($post['updatedAt']) ?>
                                <?php endif; ?>
                                | Views: <?= $post['viewCount'] ?? 0 ?>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview uploaded image
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Show attachment filename
        function showFileName(input) {
            const fileNameDiv = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileNameDiv.innerHTML = '<i class="bi bi-paperclip"></i> ' + input.files[0].name;
                fileNameDiv.style.display = 'block';
            }
        }

        // Form validation
        document.getElementById('editPostForm').addEventListener('submit', function(e) {
            const title = document.querySelector('[name="title"]').value.trim();
            if (!title) {
                e.preventDefault();
                alert('Please enter a post title');
                return false;
            }

            const editor = tinymce.get('content');
            if (editor && editor.getContent().trim() === '') {
                e.preventDefault();
                alert('Content cannot be empty.');
                editor.focus();
                return false;
            }
        });
    </script>
</body>
</html>