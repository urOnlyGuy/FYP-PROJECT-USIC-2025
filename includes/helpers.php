<?php
// includes/helpers.php
// Helper functions for file uploads (with firebase), html sanitization, and others

/**
 * Upload image to Firebase Storage
 * @param array $file - $_FILES['input_name']
 * @param string $folder - Storage folder (e.g., 'posts', 'thumbnails')
 * @return array - ['success' => bool, 'url' => string, 'error' => string]
 */
function upload_image_to_storage($file, $folder = 'posts') {
    // Validate file
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Check if it's an image
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Only image files are allowed'];
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Image size must be less than 5MB'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $storagePath = $folder . '/' . $filename;
    
    // Upload to Firebase Storage using REST API
    $result = upload_to_firebase_storage($file['tmp_name'], $storagePath);
    
    return $result;
}

/**
 * Upload file (PDF, DOC, etc.) to Firebase Storage
 */
function upload_file_to_storage($file, $folder = 'attachments') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Allowed file types
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];
    
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed. Only PDF, DOC, XLS, TXT allowed'];
    }
    
    // Check file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size must be less than 10MB'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $storagePath = $folder . '/' . $filename;
    
    $result = upload_to_firebase_storage($file['tmp_name'], $storagePath);
    
    if ($result['success']) {
        $result['originalName'] = $file['name'];
    }
    
    return $result;
}

/**
 * Upload to Firebase Storage using REST API
 */
function upload_to_firebase_storage($localFilePath, $storagePath) {
    $bucket = getenv('FIREBASE_STORAGE_BUCKET');
    
    if (!$bucket) {
        return ['success' => false, 'error' => 'Firebase Storage not configured'];
    }
    
    // Read file content
    $fileContent = file_get_contents($localFilePath);
    $contentType = mime_content_type($localFilePath);
    
    // Upload URL
    $url = "https://firebasestorage.googleapis.com/v0/b/{$bucket}/o/" . urlencode($storagePath);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: ' . $contentType,
        'Content-Length: ' . strlen($fileContent)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Failed to upload to Firebase Storage'];
    }
    
    $data = json_decode($response, true);
    
    // Generate public URL
    $downloadUrl = "https://firebasestorage.googleapis.com/v0/b/{$bucket}/o/" . 
                   urlencode($storagePath) . "?alt=media";
    
    return [
        'success' => true,
        'url' => $downloadUrl,
        'path' => $storagePath
    ];
}

/**
 * Delete file from Firebase Storage
 */
function delete_from_firebase_storage($storagePath) {
    $bucket = getenv('FIREBASE_STORAGE_BUCKET');
    $url = "https://firebasestorage.googleapis.com/v0/b/{$bucket}/o/" . urlencode($storagePath);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 204;
}

/**
 * Get category placeholder image
 */
function get_category_placeholder($category) {
    $placeholders = [
        'Finance' => '/assets/placeholders/finance.jpg',
        'Academic' => '/assets/placeholders/academic.jpg',
        'Events' => '/assets/placeholders/events.jpg',
        'General' => '/assets/placeholders/general.jpg'
    ];
    
    return $placeholders[$category] ?? $placeholders['General'];
}

/**
 * Sanitize HTML content from rich text editor
 */
function sanitize_html_content($html) {
    // Allow only safe HTML tags
    $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><ul><ol><li><a><img><blockquote><code><pre>';
    return strip_tags($html, $allowed_tags);
}

/**
 * Generate unique post ID
 */
function generate_post_id() {
    return 'post_' . uniqid() . '_' . time();
}

/**
 * Format timestamp for display
 */
function format_date($timestamp) {
    return date('F j, Y \a\t g:i A', $timestamp);
}

/**
 * Time ago format (e.g., "2 hours ago")
 */
function time_ago($timestamp) {
    $time = time() - $timestamp;
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 604800) return floor($time / 86400) . ' days ago';
    
    return date('M j, Y', $timestamp);
}
?>