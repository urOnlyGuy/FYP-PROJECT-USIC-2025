<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
<?php
// index.php — redirector page

require_once __DIR__ . '/includes/auth.php';; // include firebase auth config

// check if user is logged in
if (isset($_SESSION['user'])) {
    // user is logged in, redirect to dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // user not logged in, redirect to login
    header("Location: login.php");
    exit();
}
?>
