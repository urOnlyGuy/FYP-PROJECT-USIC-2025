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

// =========================
// BASE FIREBASE FUNCTIONS
// =========================

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

// =========================
// FIREBASE AUTH FUNCTIONS
// =========================

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
1.register_user() → Signs up user in Firebase Auth and stores user profile in Realtime Database under /users/{localId}.
2.login_user() → Verifies credentials via Firebase Auth, retrieves user info, and stores it in session.
3.logout_user() → Destroys session.
4.is_logged_in() and current_user() → For page-level access control and personalization. 
*/

// ==========================
// POST MANAGEMENT FUNCTIONS
// 8 functions
// ==========================

/**
 * Create a new post
 */
function create_post($postData) {
    $postId = 'post_' . uniqid() . '_' . time();
    
    $post = [
        'title' => $postData['title'],
        'content' => $postData['content'],
        'category' => $postData['category'] ?? 'General',
        'headerImage' => $postData['headerImage'] ?? null,
        'attachmentUrl' => $postData['attachmentUrl'] ?? null,
        'attachmentName' => $postData['attachmentName'] ?? null,
        'createdBy' => $postData['createdBy'],
        'createdAt' => time(),
        'viewCount' => 0,
        'notificationSent' => $postData['notificationSent'] ?? false
    ];
    
    $result = firebase_put('posts/' . $postId, $post);
    
    if ($result) {
        return ['success' => true, 'postId' => $postId];
    }
    
    return ['success' => false, 'error' => 'Failed to create post'];
}

/**
 * Get all posts
 */
function get_all_posts($limit = null) {
    $posts = firebase_get('posts');
    
    if (!$posts) return [];
    
    // Convert to array with IDs
    $postArray = [];
    foreach ($posts as $id => $post) {
        $post['id'] = $id;
        $postArray[] = $post;
    }
    
    // Sort by date (newest first)
    usort($postArray, function($a, $b) {
        return $b['createdAt'] - $a['createdAt'];
    });
    
    if ($limit) {
        return array_slice($postArray, 0, $limit);
    }
    
    return $postArray;
}

/**
 * Get posts by category
 */
function get_posts_by_category($category) {
    $allPosts = get_all_posts();
    
    return array_filter($allPosts, function($post) use ($category) {
        return $post['category'] === $category;
    });
}

/**
 * Get single post by ID
 */
function get_post($postId) {
    $post = firebase_get('posts/' . $postId);
    
    if ($post) {
        $post['id'] = $postId;
    }
    
    return $post;
}

//Update post
function update_post($postId, $postData) {
    $existingPost = get_post($postId);
    
    if (!$existingPost) {
        return ['success' => false, 'error' => 'Post not found'];
    }
    
    // Preserve certain fields
    $postData['createdAt'] = $existingPost['createdAt'];
    $postData['createdBy'] = $existingPost['createdBy'];
    $postData['viewCount'] = $existingPost['viewCount'] ?? 0;
    $postData['updatedAt'] = time();
    
    $result = firebase_put('posts/' . $postId, $postData);
    
    if ($result) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Failed to update post'];
}

//Delete post
function delete_post($postId) {
    $post = get_post($postId);
    
    if (!$post) {
        return false;
    }
    
    // Optional: Delete associated files from storage
    // if (isset($post['headerImage'])) {
    //     // Extract storage path and delete
    // }
    // if (isset($post['attachmentUrl'])) {
    //     // Extract storage path and delete
    // }
    
    return firebase_delete('posts/' . $postId);
}

//Increment view count
function increment_view_count($postId) {
    $post = get_post($postId);
    
    if ($post) {
        $newCount = ($post['viewCount'] ?? 0) + 1;
        firebase_put('posts/' . $postId . '/viewCount', $newCount);
        return $newCount;
    }
    
    return 0;
}

/**
 * Search posts
 */
function search_posts($query) {
    $allPosts = get_all_posts();
    $query = strtolower($query);
    
    return array_filter($allPosts, function($post) use ($query) {
        return strpos(strtolower($post['title']), $query) !== false ||
               strpos(strtolower($post['content']), $query) !== false;
    });
}

// =====================
// FAVORITES FUNCTIONS
// 4 functions
// =====================

//Add post to favorites
function add_to_favorites($userId, $postId) {
    return firebase_put('favorites/' . $userId . '/' . $postId, true);
}

//Remove from favorites
function remove_from_favorites($userId, $postId) {
    return firebase_delete('favorites/' . $userId . '/' . $postId);
}

//Check if post is favorited
function is_favorited($userId, $postId) {
    $result = firebase_get('favorites/' . $userId . '/' . $postId);
    return $result === true;
}

 //Get user's favorite posts
function get_user_favorites($userId) {
    $favorites = firebase_get('favorites/' . $userId);
    
    if (!$favorites) return [];
    
    $favoritePosts = [];
    foreach ($favorites as $postId => $value) {
        $post = get_post($postId);
        if ($post) {
            $favoritePosts[] = $post;
        }
    }
    
    return $favoritePosts;
}

// =====================
// FAQ CATEGORY MANAGEMENT
// =====================

/**
 * Create FAQ category
 */
function create_faq_category($categoryData) {
    $categoryId = 'faq_cat_' . uniqid() . '_' . time();
    
    $category = [
        'name' => $categoryData['name'],
        'icon' => $categoryData['icon'] ?? 'bi-question-circle',
        'emoji' => $categoryData['emoji'] ?? '❓',
        'order' => $categoryData['order'] ?? 999,
        'isActive' => $categoryData['isActive'] ?? true,
        'createdAt' => time(),
        'createdBy' => $categoryData['createdBy']
    ];
    
    $result = firebase_put('faq_categories/' . $categoryId, $category);
    
    if ($result) {
        return ['success' => true, 'categoryId' => $categoryId];
    }
    
    return ['success' => false, 'error' => 'Failed to create category'];
}

/**
 * Get all FAQ categories
 */
function get_all_faq_categories($activeOnly = false) {
    $categories = firebase_get('faq_categories');
    
    if (!$categories) return [];
    
    $categoryArray = [];
    foreach ($categories as $id => $category) {
        if ($activeOnly && !($category['isActive'] ?? true)) {
            continue;
        }
        $category['id'] = $id;
        $categoryArray[] = $category;
    }
    
    // Sort by order
    usort($categoryArray, function($a, $b) {
        return ($a['order'] ?? 999) - ($b['order'] ?? 999);
    });
    
    return $categoryArray;
}

/**
 * Get single FAQ category
 */
function get_faq_category($categoryId) {
    $category = firebase_get('faq_categories/' . $categoryId);
    
    if ($category) {
        $category['id'] = $categoryId;
    }
    
    return $category;
}

/**
 * Update FAQ category
 */
function update_faq_category($categoryId, $categoryData) {
    $existing = get_faq_category($categoryId);
    
    if (!$existing) {
        return ['success' => false, 'error' => 'Category not found'];
    }
    
    $result = firebase_put('faq_categories/' . $categoryId, array_merge($existing, $categoryData));
    
    if ($result) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Failed to update category'];
}

/**
 * Delete FAQ category
 */
function delete_faq_category($categoryId) {
    // Check if any FAQs use this category
    $faqs = get_all_faqs(false);
    foreach ($faqs as $faq) {
        if ($faq['category'] === $categoryId) {
            return ['success' => false, 'error' => 'Cannot delete category with existing FAQs'];
        }
    }
    
    return firebase_delete('faq_categories/' . $categoryId);
}

// =====================
// FAQ MANAGEMENT FUNCTIONS
// =====================

/**
 * Create a new FAQ entry
 */
function create_faq($faqData) {
    $faqId = 'faq_' . uniqid() . '_' . time();
    
    $faq = [
        'question' => $faqData['question'],
        'answer' => $faqData['answer'],
        'category' => $faqData['category'],
        'order' => $faqData['order'] ?? 999,
        'isActive' => $faqData['isActive'] ?? true,
        'createdAt' => time(),
        'createdBy' => $faqData['createdBy'],
        'updatedAt' => time()
    ];
    
    $result = firebase_put('faqs/' . $faqId, $faq);
    
    if ($result) {
        return ['success' => true, 'faqId' => $faqId];
    }
    
    return ['success' => false, 'error' => 'Failed to create FAQ'];
}

/**
 * Get all FAQs
 */
function get_all_faqs($activeOnly = true) {
    $faqs = firebase_get('faqs');
    
    if (!$faqs) return [];
    
    $faqArray = [];
    foreach ($faqs as $id => $faq) {
        if ($activeOnly && !($faq['isActive'] ?? true)) {
            continue;
        }
        $faq['id'] = $id;
        $faqArray[] = $faq;
    }
    
    // Sort by order and then by creation date
    usort($faqArray, function($a, $b) {
        if ($a['order'] == $b['order']) {
            return $a['createdAt'] - $b['createdAt'];
        }
        return $a['order'] - $b['order'];
    });
    
    return $faqArray;
}

/**
 * Get FAQs by category
 */
function get_faqs_by_category($category, $activeOnly = true) {
    $allFaqs = get_all_faqs($activeOnly);
    
    return array_filter($allFaqs, function($faq) use ($category) {
        return $faq['category'] === $category;
    });
}

/**
 * Get single FAQ by ID
 */
function get_faq($faqId) {
    $faq = firebase_get('faqs/' . $faqId);
    
    if ($faq) {
        $faq['id'] = $faqId;
    }
    
    return $faq;
}

/**
 * Update FAQ
 */
function update_faq($faqId, $faqData) {
    $existingFaq = get_faq($faqId);
    
    if (!$existingFaq) {
        return ['success' => false, 'error' => 'FAQ not found'];
    }
    
    $faqData['updatedAt'] = time();
    
    $result = firebase_put('faqs/' . $faqId, array_merge($existingFaq, $faqData));
    
    if ($result) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Failed to update FAQ'];
}

/**
 * Delete FAQ
 */
function delete_faq($faqId) {
    return firebase_delete('faqs/' . $faqId);
}
?>