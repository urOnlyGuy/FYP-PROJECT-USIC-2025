<?php
// admin/manage_users.php
// Admin page to manage all users

session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/firebase.php';


// Check if user is admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle role change
if (isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];
    
    $result = firebase_put('users/' . $userId . '/role', $newRole);
    
    if ($result) {
        $message = 'User role updated successfully!';
        $messageType = 'success';
    } else {
        $message = 'Failed to update user role.';
        $messageType = 'danger';
    }
}

// Handle delete user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $userId = $_GET['id'];
    
    // Prevent deleting self
    if ($userId === $_SESSION['user_id']) {
        $message = 'You cannot delete your own account!';
        $messageType = 'warning';
    } else {
        $result = firebase_delete('users/' . $userId);
        
        if ($result) {
            $message = 'User deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete user.';
            $messageType = 'danger';
        }
    }
}

// Get all users
$users = firebase_get('users');
$usersArray = [];

if ($users) {
    foreach ($users as $id => $user) {
        $user['id'] = $id;
        $usersArray[] = $user;
    }
    
    // Sort by creation date (newest first)
    usort($usersArray, function($a, $b) {
        return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
    });
}

// Filter by role
$filterRole = $_GET['role'] ?? 'all';
if ($filterRole !== 'all') {
    $usersArray = array_filter($usersArray, function($user) use ($filterRole) {
        return ($user['role'] ?? 'student') === $filterRole;
    });
}

// Statistics
$allUsers = firebase_get('users');
$stats = ['student' => 0, 'staff' => 0, 'admin' => 0];
if ($allUsers) {
    foreach ($allUsers as $user) {
        $role = $user['role'] ?? 'student';
        if (isset($stats[$role])) {
            $stats[$role]++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | USIC - UPTM Student Info Center</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-megaphone-fill"></i> UPTM Info Center - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['email']) ?>
                    <span class="badge bg-warning text-dark">ADMIN</span>
                </span>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6 class="card-title">Total Users</h6>
                        <h2><?= array_sum($stats) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6 class="card-title">Students</h6>
                        <h2><?= $stats['student'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6 class="card-title">Staff</h6>
                        <h2><?= $stats['staff'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">Admins</h6>
                        <h2><?= $stats['admin'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                
                <!-- Role Filter -->
                <div class="btn-group ms-3" role="group">
                    <a href="?role=all" class="btn btn-outline-primary <?= $filterRole === 'all' ? 'active' : '' ?>">All Users</a>
                    <a href="?role=student" class="btn btn-outline-primary <?= $filterRole === 'student' ? 'active' : '' ?>">Students</a>
                    <a href="?role=staff" class="btn btn-outline-primary <?= $filterRole === 'staff' ? 'active' : '' ?>">Staff</a>
                    <a href="?role=admin" class="btn btn-outline-primary <?= $filterRole === 'admin' ? 'active' : '' ?>">Admins</a>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-people"></i> User Management</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($usersArray)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-person-x" style="font-size: 4rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No users found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersArray as $user): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-person-circle"></i> 
                                            <?= htmlspecialchars($user['email']) ?>
                                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                <span class="badge bg-info">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $role = $user['role'] ?? 'student';
                                            $badgeColor = [
                                                'admin' => 'danger',
                                                'staff' => 'success',
                                                'student' => 'primary'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $badgeColor[$role] ?>">
                                                <?= strtoupper($role) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= isset($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A' ?></small>
                                        </td>
                                        <td>
                                            <!-- Change Role Button -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#roleModal<?= $user['id'] ?>"
                                                    <?= $user['id'] === $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                                <i class="bi bi-pencil"></i> Change Role
                                            </button>
                                            
                                            <!-- Delete Button -->
                                            <a href="?action=delete&id=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this user?')"
                                               <?= $user['id'] === $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                                <i class="bi bi-trash"></i>
                                            </a>

                                            <!-- Role Change Modal -->
                                            <div class="modal fade" id="roleModal<?= $user['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Change User Role</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                                                <p><strong>Current Role:</strong> <?= strtoupper($role) ?></p>
                                                                
                                                                <input type="hidden" name="action" value="change_role">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                
                                                                <label class="form-label">New Role:</label>
                                                                <select name="new_role" class="form-select" required>
                                                                    <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                                                                    <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option>
                                                                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                                </select>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update Role</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
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