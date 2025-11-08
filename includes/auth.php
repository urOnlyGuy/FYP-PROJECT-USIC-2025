<?php
// auth.php
// handles signup, login, logout, and session management using firebase

session_start();
require_once 'firebase.php';

// UPDATE1- function to validate email domain (part1/2)
function validate_email_domain($email, $allowed_domains = ['.edu', '.edu.my']) //ATTENTION-over here can add other domain for email verification
	{
		$email = strtolower($email);
		
		foreach ($allowed_domains as $domain) {
			if (strpos($email, strtolower($domain)) !== false) {
				return true;
			}
		}
		
		return false;
	}//End of Update1-part2/2
	
// signup function
function register_user($email, $password, $role = 'student') {
    // UPDATE1- validate email domain (part2/2)
    if (!validate_email_domain($email)) {
        return ['success' => false, 'message' => 'Only .edu email addresses are allowed for registration.'];
    }// End of UPDATE1-Part2/2
	
    $result = firebase_signup($email, $password);

    if (isset($result['error'])) {
        return ['success' => false, 'message' => $result['error']['message']];
    }

    if (!isset($result['localId'])) {
        return ['success' => false, 'message' => 'Failed to create account.'];
    }

    // store user info in firebase database
    $userData = [
        'email' => $email,
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    firebase_put('users/' . $result['localId'], $userData);

    return ['success' => true, 'message' => 'Account created successfully!'];
}

// login function
function login_user($email, $password) {
    $result = firebase_login($email, $password);

    if (isset($result['error'])) {
        return ['success' => false, 'message' => $result['error']['message']];
    }

    if (!isset($result['idToken'])) {
        return ['success' => false, 'message' => 'Login failed.'];
    }

    // get user info from firebase db
    $userInfo = firebase_get('users/' . $result['localId']);

    // store session
    $_SESSION['user_id'] = $result['localId'];
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $userInfo['role'] ?? 'student';
    $_SESSION['idToken'] = $result['idToken'];

    return ['success' => true, 'message' => 'Login successful!'];
}

// logout function
function logout_user() {
    session_unset();
    session_destroy();
}

// check login status
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// get current user info
function current_user() {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}
?>