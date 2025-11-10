<?php
// admin/create_post.php
// Admin/Staff page to create new posts

session_start();
require_once __DIR__ . '/../includes/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notifications.php';

// Check if user is admin or staff
if (!is_logged_in() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: ../pages/login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content']; // Rich text content
    $category = $_POST['category'] ?? 'General';
    $sendNotification = isset($_POST['send_notification']);
    
    // Sanitize HTML content
    $content = sanitize_html_content($content);
    
    $headerImageUrl = null;
    $attachmentUrl = null;
    $attachmentName = null;
    
    // Handle header image upload
    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = upload_image_to_storage($_FILES['header_image'], 'posts/headers');
        
        if ($uploadResult['success']) {
            $headerImageUrl = $uploadResult['url'];
        } else {
            $message = 'Error uploading image: ' . $uploadResult['error'];
            $messageType = 'danger';
        }
    }
    
    // Handle attachment upload
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
    
    // If no image uploaded, use category placeholder
    if (!$headerImageUrl) {
        $headerImageUrl = get_category_placeholder($category);
    }
    
    // Create post if no upload errors
    if (empty($message)) {
        $postData = [
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'headerImage' => $headerImageUrl,
            'attachmentUrl' => $attachmentUrl,
            'attachmentName' => $attachmentName,
            'createdBy' => $_SESSION['user_id'],
            'notificationSent' => $sendNotification
        ];
        
        $result = create_post($postData);
        
        if ($result['success']) {
            // Send notifications if enabled
            if ($sendNotification) {
                send_notification($result['postId'], $title, $content);
            }
            
            $message = 'Post created successfully!';
            $messageType = 'success';
            
            // Redirect to dashboard after 2 seconds
            header("refresh:2;url=dashboard.php");
        } else {
            $message = 'Failed to create post: ' . ($result['error'] ?? 'Unknown error');
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
    <title>Create Post | USIC - UPTM Student Info Center</title>
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
            display: none;
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

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Post</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="createPostForm">
                            
                            <!-- Title -->
                            <div class="mb-3">
                                <label class="form-label">Post Title <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="title" 
                                       class="form-control" 
                                       placeholder="Enter post title"
                                       required
                                       maxlength="200">
                            </div>

                            <!-- Header Image -->
                            <div class="mb-3">
                                <label class="form-label">Header Image / Thumbnail</label>
                                <input type="file" 
                                       name="header_image" 
                                       class="form-control" 
                                       accept="image/*"
                                       id="headerImageInput"
                                       onchange="previewImage(this)">
                                <small class="text-muted">
                                    Optional. Max 5MB. If not uploaded, a category-based placeholder will be used.
                                </small>
                                <img id="imagePreview" class="image-preview" alt="Preview">
                            </div>

                            <!-- Content (Rich Text Editor) -->
                            <div class="mb-3">
                                <label class="form-label">Content <span class="text-danger">*</span></label>
                                <textarea id="content" name="content"></textarea>
                                <small class="text-muted">Use the editor toolbar to format your content.</small>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <option value="General" selected>General</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Academic">Academic</option>
                                    <option value="Events">Events</option>
                                </select>
                            </div>

                            <!-- Attachment -->
                            <div class="mb-3">
                                <label class="form-label">Attachment (Optional)</label>
                                <input type="file" 
                                       name="attachment" 
                                       class="form-control"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.txt"
                                       id="attachmentInput"
                                       onchange="showFileName(this)">
                                <small class="text-muted">
                                    PDF, DOC, XLS, TXT files only. Max 10MB.
                                </small>
                                <div id="fileName" class="mt-2 text-success" style="display: none;"></div>
                            </div>

                            <!-- Push Notification Toggle -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="send_notification" 
                                           id="notificationToggle"
                                           checked>
                                    <label class="form-check-label" for="notificationToggle">
                                        <i class="bi bi-bell-fill"></i> Send push notification to all students
                                    </label>
                                </div>
                                <small class="text-muted">
                                    When enabled, all students will receive an email notification about this post.
                                </small>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Publish Post
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
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

        // Form validation before submit
        document.getElementById('createPostForm').addEventListener('submit', function(e) {
            const title = document.querySelector('[name="title"]').value.trim();
            if (!title) {
                e.preventDefault();
                alert('Please enter a post title');
                return false;
            }

            // START **NEW: Validate TinyMCE content**
            // tinymce.get('content') gets the editor instance
            const editor = tinymce.get('content');
            if (editor && editor.getContent().trim() === '') {
                e.preventDefault();
                alert('Content cannot be empty.');
                editor.focus(); // Optionally focus the editor for the user
                return false;
            }
            // END **NEW: Validate...
        });
    </script>
</body>
</html>