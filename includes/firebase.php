<?php
//firebase.php
// all firebase-related configuration and basic helper functions

//START of SecurityUpdate1- for safer api key approach
//create your own .env file with your own key please. refer .env.example for template

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found. Please create one based on .env.example');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        $value = trim($value, '"\'');
        
        // Set environment variable
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Get configuration from environment variables
define('FIREBASE_PROJECT_URL', getenv('FIREBASE_PROJECT_URL'));
define('FIREBASE_API_KEY', getenv('FIREBASE_API_KEY'));

// Validate that keys are loaded
if (!FIREBASE_PROJECT_URL || !FIREBASE_API_KEY) {
    throw new Exception('Firebase configuration not found. Check your .env file.');
}
//END of SecurityUpdate1

// base functions for firebase REST API interaction
function firebase_request($method, $path, $data = null) {
    $url = FIREBASE_PROJECT_URL . $path . '.json';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => curl_error($ch)];
    }
    curl_close($ch);
    return json_decode($response, true);
}

// firebase helper wrappers
function firebase_get($path) {
    return firebase_request('GET', $path);
}

function firebase_post($path, $data) {
    return firebase_request('POST', $path, $data);
}

function firebase_put($path, $data) {
    return firebase_request('PUT', $path, $data);
}

function firebase_delete($path) {
    return firebase_request('DELETE', $path);
}

// optional: firebase auth via REST
function firebase_signup($email, $password) {
    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=' . FIREBASE_API_KEY;
    $payload = json_encode(['email' => $email, 'password' => $password, 'returnSecureToken' => true]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function firebase_login($email, $password) {
    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . FIREBASE_API_KEY;
    $payload = json_encode(['email' => $email, 'password' => $password, 'returnSecureToken' => true]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
/* Explanation:
register_user() → Signs up user in Firebase Auth and stores user profile in Realtime Database under /users/{localId}.

login_user() → Verifies credentials via Firebase Auth, retrieves user info, and stores it in session.

logout_user() → Destroys session.

is_logged_in() and current_user() → For page-level access control and personalization. 
*/
?>