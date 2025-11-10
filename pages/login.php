<?php
require_once '../includes/auth.php';

$message = '';
$messageType = 'info'; // success, danger, warning, info
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        $message = 'Please fill in all fields.';
        $messageType = 'warning';
    } else {
        $result = login_user($email, $password);
        $message = $result['message'];
        
        if ($result['success']) {
            $messageType = 'success';
            
            // Role-based redirect
            $role = $_SESSION['role'] ?? 'student';
            switch ($role) {
                case 'admin':
                case 'staff':
                    header('Location: /admin/dashboard.php');
                    break;
                case 'student':
                default:
                    header('Location: /student/dashboard.php');
                    break;
            }
            exit;
        } else {
            $messageType = 'danger';
            
            // Improve error messages
            if (strpos($message, 'EMAIL_NOT_FOUND') !== false) {
                $message = 'No account found with this email address.';
            } elseif (strpos($message, 'INVALID_PASSWORD') !== false || strpos($message, 'INVALID_LOGIN_CREDENTIALS') !== false) {
                $message = 'Incorrect password. Please try again.';
            } elseif (strpos($message, 'TOO_MANY_ATTEMPTS') !== false) {
                $message = 'Too many failed login attempts. Please try again later.';
            } elseif (strpos($message, 'USER_DISABLED') !== false) {
                $message = 'This account has been disabled. Please contact support.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | USIC - UPTM Student Info Center</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #19519D 0%, #0d3164 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .app-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: white;
            border-radius: 20px;
            padding: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .app-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .app-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.25rem 0;
            letter-spacing: 0.5px;
        }
        
        .app-subtitle {
            font-size: 0.85rem;
            opacity: 0.95;
            margin: 0;
            font-weight: 400;
        }
        
        .welcome-text {
            font-size: 1.1rem;
            margin: 1rem 0 0 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2rem;
            background: white;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #19519D;
            box-shadow: 0 0 0 0.2rem rgba(25, 81, 157, 0.15);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .input-group .form-control {
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .password-toggle {
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #19519D;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #19519D 0%, #0d3164 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(25, 81, 157, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            animation: slideDown 0.4s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            color: #666;
            font-size: 0.9rem;
        }
        
        .signup-link {
            text-align: center;
            color: #666;
        }
        
        .signup-link a {
            color: #19519D;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .signup-link a:hover {
            color: #0d3164;
            text-decoration: underline;
        }
        
        .footer-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
        }
        
        @media (max-width: 576px) {
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .app-logo {
                width: 70px;
                height: 70px;
            }
            
            .app-title {
                font-size: 1.3rem;
            }
            
            .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4 login-container">
                <div class="login-card card border-0">
                    <div class="login-header">
                        <!-- App Logo -->
                        <div class="app-logo">
                            <img src="../assets/icons/icon-192x192.png" alt="USIC Logo">
                        </div>
                        
                        <!-- App Title -->
                        <h1 class="app-title">USIC</h1>
                        <p class="app-subtitle">UPTM Student Information Center</p>
                        
                        <!-- Welcome Message -->
                        <p class="welcome-text">Welcome Back! 👋</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> d-flex align-items-center" role="alert">
                                <i class="bi bi-<?= $messageType === 'danger' ? 'exclamation-circle' : ($messageType === 'success' ? 'check-circle' : 'info-circle') ?>-fill me-2"></i>
                                <div><?= htmlspecialchars($message) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-1"></i>Email Address
                                </label>
                                <input type="email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="your.email@example.com"
                                       value="<?= htmlspecialchars($email) ?>"
                                       required 
                                       autofocus>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-lock me-1"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           name="password" 
                                           id="password"
                                           class="form-control" 
                                           placeholder="Enter your password"
                                           required>
                                    <span class="input-group-text">
                                        <i class="bi bi-eye password-toggle" 
                                           id="togglePassword"
                                           onclick="togglePasswordVisibility()"></i>
                                    </span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login btn-primary w-100" id="loginBtn">
                                <span id="btnText">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </span>
                                <span id="btnLoading" style="display: none;">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Logging in...
                                </span>
                            </button>
                        </form>

                        <div class="divider">
                            <span>New to USIC?</span>
                        </div>

                        <p class="signup-link mb-0">
                            <a href="register.php">
                                <i class="bi bi-person-plus me-1"></i>Create an account
                            </a>
                        </p>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <div class="footer-badge">
                        <small class="text-white">
                            <i class="bi bi-shield-check me-1"></i>
                            Secure login powered by Firebase
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            btn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
        });
    </script>
</body>
</html>