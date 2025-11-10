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
    // Handle deadline reminder update
    if (isset($_POST['deadline_date']) && !empty($_POST['deadline_date'])) {
        $deadlineDate = strtotime($_POST['deadline_date']);
        $reminderTitle = trim($_POST['reminder_title']);
        $reminderMessage = trim($_POST['reminder_message']) ?? '';
        $reminderDays = $_POST['reminder_days'] ?? [1, 3, 7];
        
        $reminderDays = array_map('intval', $reminderDays);
        
        if (!empty($reminderTitle) && $deadlineDate > time()) {
            $reminderData = [
                'deadlineDate' => $deadlineDate,
                'title' => $reminderTitle,
                'message' => $reminderMessage,
                'reminderDays' => $reminderDays
            ];
            
            set_post_reminder($postId, $reminderData);
        }
    } else {
        // Remove reminder if deadline is cleared
        delete_post_reminder($postId);
    }
    
    $message = 'Post updated successfully!';
    $messageType = 'success';
    $post = get_post($postId); // Refresh
    
    header("refresh:2;url=dashboard.php");
}
        
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

                            </div>
							// Get existing reminder if any
							<?php $existingReminder = get_post_reminder($postId); ?>
							
							<!-- Deadline Reminder -->
							<div class="mb-3">
								<div class="form-check form-switch">
									<input class="form-check-input" 
										type="checkbox" 
										id="enableReminder"
										<?= $existingReminder ? 'checked' : '' ?>
										onchange="toggleReminderFields()">
									<label class="form-check-label" for="enableReminder">
										<i class="bi bi-alarm"></i> Set Deadline Reminder
									</label>
								</div>
							</div>
							
							<!-- Reminder Fields -->
							<div id="reminderFields" style="display: <?= $existingReminder ? 'block' : 'none' ?>;">
								<div class="card bg-light mb-3">
									<div class="card-body">
										<h6 class="card-title"><i class="bi bi-calendar-event"></i> Deadline Settings</h6>
										
										<div class="mb-3">
											<label class="form-label">Deadline Date & Time</label>
											<input type="datetime-local" 
												name="deadline_date" 
												class="form-control"
												id="deadlineDate"
												value="<?= $existingReminder ? date('Y-m-d\TH:i', $existingReminder['deadlineDate']) : '' ?>"
												min="<?= date('Y-m-d\TH:i') ?>">
										</div>
										
										<div class="mb-3">
											<label class="form-label">Reminder Title</label>
											<input type="text" 
												name="reminder_title" 
												class="form-control"
												id="reminderTitle"
												value="<?= $existingReminder ? htmlspecialchars($existingReminder['title']) : '' ?>"
												maxlength="200">
										</div>
										
										<div class="mb-3">
											<label class="form-label">Reminder Message (Optional)</label>
											<textarea name="reminder_message" 
													class="form-control" 
													rows="2"
													maxlength="500"><?= $existingReminder ? htmlspecialchars($existingReminder['message']) : '' ?></textarea>
										</div>
										
										<div class="mb-3">
											<label class="form-label">Send Reminders</label>
											<?php 
											$selectedDays = $existingReminder ? ($existingReminder['reminderDays'] ?? [1, 3, 7]) : [1, 3, 7];
											?>
											<div class="form-check">
												<input class="form-check-input" type="checkbox" name="reminder_days[]" value="7" 
													<?= in_array(7, $selectedDays) ? 'checked' : '' ?>>
												<label class="form-check-label">7 days before deadline</label>
											</div>
											<div class="form-check">
												<input class="form-check-input" type="checkbox" name="reminder_days[]" value="3"
													<?= in_array(3, $selectedDays) ? 'checked' : '' ?>>
												<label class="form-check-label">3 days before deadline</label>
											</div>
											<div class="form-check">
												<input class="form-check-input" type="checkbox" name="reminder_days[]" value="1"
													<?= in_array(1, $selectedDays) ? 'checked' : '' ?>>
												<label class="form-check-label">1 day before deadline</label>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<script>
							function toggleReminderFields() {
								const checkbox = document.getElementById('enableReminder');
								const fields = document.getElementById('reminderFields');
								const deadlineDate = document.getElementById('deadlineDate');
								const reminderTitle = document.getElementById('reminderTitle');
								
								if (checkbox.checked) {
									fields.style.display = 'block';
									deadlineDate.required = true;
									reminderTitle.required = true;
								} else {
									fields.style.display = 'none';
									deadlineDate.required = false;
									reminderTitle.required = false;
								}
							}
							</script>

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