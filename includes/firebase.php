<?php
//firebase.php
// all firebase-related configuration and basic helper functions

// your firebase project settings
define('FIREBASE_PROJECT_URL', 'https://YOUR_PROJECT_ID.firebaseio.com/'); // end with '/'
define('FIREBASE_API_KEY', 'YOUR_FIREBASE_WEB_API_KEY'); // from firebase console

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
