<?php include __DIR__ . '/includes/pwa_head.php'; ?>
<?php
// index.php — redirector page

require_once __DIR__ . '/includes/auth.php';; // include firebase auth config

// Check if user is logged in
if (is_logged_in()) {
    // Redirect based on role
    $role = $_SESSION['role'] ?? 'student';
    
    if ($role === 'admin' || $role === 'staff') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
} else {
    // User not logged in, redirect to login
    header("Location: pages/login.php");
    exit();
}
?>
