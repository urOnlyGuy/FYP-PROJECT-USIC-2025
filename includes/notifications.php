<?php
// includes/notifications.php
// Notification system: In-app + Email + OneSignal (future)

require_once __DIR__ . '/firebase.php';

/**
 * MAIN NOTIFICATION FUNCTION
 * Sends notifications via all enabled channels
 */
function send_notification($postId, $postTitle, $postContent = '') {
    // PHASE 1: In-app notification (ALWAYS)
    create_in_app_notification($postId, $postTitle);
    
    // PHASE 1: Email notification (ALWAYS)
    send_email_notifications($postId, $postTitle, $postContent);
    
    // PHASE 2: OneSignal push notification (WHEN ENABLED)
    if (defined('ONESIGNAL_ENABLED') && getenv('ONESIGNAL_ENABLED') === 'true') {
        send_onesignal_push($postTitle, $postContent, $postId);
    }
}

/**
 * PHASE 1: Create in-app notification for all students
 */
function create_in_app_notification($postId, $postTitle) {
    $notification = [
        'postId' => $postId,
        'title' => $postTitle,
        'message' => 'New post available',
        'createdAt' => time(),
        'isRead' => false
    ];
    
    // Get all users
    $users = firebase_get('users');
    
    if (!$users) return false;
    
    // Send to all students
    foreach ($users as $userId => $user) {
        if (isset($user['role']) && $user['role'] === 'student') {
            firebase_post('notifications/' . $userId, $notification);
        }
    }
    
    return true;
}

/**
 * PHASE 1: Send email notifications to all students
 */
function send_email_notifications($postId, $postTitle, $postContent) {
    $users = firebase_get('users');
    
    if (!$users) return false;
    
    $siteUrl = getenv('SITE_URL') ?: 'http://localhost';
    $postUrl = $siteUrl . '/student/post.php?id=' . $postId;
    
    $successCount = 0;
    
    foreach ($users as $userId => $user) {
        if (isset($user['role']) && $user['role'] === 'student' && isset($user['email'])) {
            $sent = send_email_notification(
                $user['email'],
                $postTitle,
                $postContent,
                $postUrl
            );
            
            if ($sent) $successCount++;
        }
    }
    
    return $successCount;
}

/**
 * Send individual email notification
 */
function send_email_notification($toEmail, $postTitle, $postContent, $postUrl) {
    $subject = "New Announcement: " . $postTitle;
    
    // Extract plain text from HTML content (first 200 chars)
    $plainContent = strip_tags($postContent);
    $preview = substr($plainContent, 0, 200);
    if (strlen($plainContent) > 200) $preview .= '...';
    
    // Email headers
    $headers = "From: UPTM Student Info Center - USIC<noreply@uptm.edu.my>\r\n";
    $headers .= "Reply-To: noreply@uptm.edu.my\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Email body
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; margin: 20px 0; }
            .button { 
                display: inline-block; 
                padding: 12px 30px; 
                background: #007bff; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px;
                margin-top: 15px;
            }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>📢 New Announcement</h2>
            </div>
            <div class='content'>
                <h3>{$postTitle}</h3>
                <p>{$preview}</p>
                <a href='{$postUrl}' class='button'>View Full Post</a>
            </div>
            <div class='footer'>
                <p>UPTM Student Information Center - USIC</p>
                <p>You received this email because you're subscribed to university announcements.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email
    return mail($toEmail, $subject, $message, $headers);
}

/**
 * PHASE 2: OneSignal push notification (FUTURE)
 */
function send_onesignal_push($title, $content, $postId) {
    $appId = getenv('ONESIGNAL_APP_ID');
    $restApiKey = getenv('ONESIGNAL_REST_API_KEY');
    
    if (!$appId || !$restApiKey) {
        return ['success' => false, 'error' => 'OneSignal not configured'];
    }
    
    $siteUrl = getenv('SITE_URL') ?: 'http://localhost';
    $postUrl = $siteUrl . '/student/post.php?id=' . $postId;
    
    // Strip HTML from content
    $plainContent = strip_tags($content);
    $message = substr($plainContent, 0, 100);
    if (strlen($plainContent) > 100) $message .= '...';
    
    $fields = [
        'app_id' => $appId,
        'included_segments' => ['Subscribed Users'],
        'headings' => ['en' => $title],
        'contents' => ['en' => $message],
        'url' => $postUrl
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $restApiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $httpCode === 200,
        'response' => $result
    ];
}

/**
 * Get unread notification count for a user
 */
function get_unread_count($userId) {
    $notifications = firebase_get('notifications/' . $userId);
    
    if (!$notifications) return 0;
    
    $count = 0;
    foreach ($notifications as $notif) {
        if (isset($notif['isRead']) && !$notif['isRead']) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Get all notifications for a user
 */
function get_user_notifications($userId, $limit = 20) {
    $notifications = firebase_get('notifications/' . $userId);
    
    if (!$notifications) return [];
    
    // Convert to array and sort by date (newest first)
    $notifArray = [];
    foreach ($notifications as $id => $notif) {
        $notif['id'] = $id;
        $notifArray[] = $notif;
    }
    
    usort($notifArray, function($a, $b) {
        return $b['createdAt'] - $a['createdAt'];
    });
    
    return array_slice($notifArray, 0, $limit);
}

/**
 * Mark notification as read
 */
function mark_notification_read($userId, $notificationId) {
    return firebase_put('notifications/' . $userId . '/' . $notificationId . '/isRead', true);
}

/**
 * Mark all notifications as read for a user
 */
function mark_all_notifications_read($userId) {
    $notifications = firebase_get('notifications/' . $userId);
    
    if (!$notifications) return false;
    
    foreach ($notifications as $notifId => $notif) {
        firebase_put('notifications/' . $userId . '/' . $notifId . '/isRead', true);
    }
    
    return true;
}

/**
 * Delete notification
 */
function delete_notification($userId, $notificationId) {
    return firebase_delete('notifications/' . $userId . '/' . $notificationId);
}
?>