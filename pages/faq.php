<?php
require_once '../includes/auth.php';
require_once '../includes/firebase.php';

// Optional: Check if user is logged in
$isLoggedIn = is_logged_in();
$userRole = $isLoggedIn ? ($_SESSION['role'] ?? 'student') : null;

// Get all active FAQs from database
$allFaqs = get_all_faqs(true); // Only active FAQs

// FIXED: Changed from get_faq_categories() to get_all_faq_categories()
$categories = get_all_faq_categories(true); // Only active categories

// Group FAQs by category
$faqsByCategory = [];
foreach ($allFaqs as $faq) {
    $faqsByCategory[$faq['category']][] = $faq;
}

// Create category lookup for easier access
$categoryLookup = [];
foreach ($categories as $cat) {
    $categoryLookup[$cat['id']] = $cat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ & Help | USIC - UPTM Student Info Center</title>
    <?php include __DIR__ . '/../includes/pwa_head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 3rem;
        }
        
        .faq-container {
            padding: 2rem 0;
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .faq-header {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .faq-header .app-logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #19519D 0%, #0d3164 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
        
        .faq-header h1 {
            color: #19519D;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .category-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .category-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        
        .category-title {
            color: #19519D;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .accordion-button {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 500;
            padding: 1rem 1.25rem;
            border-radius: 10px !important;
        }
        
        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #19519D 0%, #0d3164 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(25, 81, 157, 0.3);
        }
        
        .accordion-button:focus {
            box-shadow: none;
            border-color: #19519D;
        }
        
        .accordion-item {
            border: none;
            margin-bottom: 0.75rem;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .accordion-body {
            padding: 1.5rem;
            line-height: 1.8;
            background-color: #f8f9fa;
        }
        
        .accordion-body strong {
            color: #19519D;
        }
        
        .accordion-body a {
            color: #19519D;
            text-decoration: underline;
        }
        
        .back-button {
            background: white;
            color: #19519D;
            border: 2px solid white;
            border-radius: 10px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .back-button:hover {
            background: #19519D;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .search-box {
            margin-bottom: 1.5rem;
        }
        
        .search-box input {
            border-radius: 15px;
            padding: 0.9rem 1.5rem;
            border: 2px solid #e0e0e0;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: #19519D;
            box-shadow: 0 0 0 0.25rem rgba(25, 81, 157, 0.15);
        }
        
        .search-box .input-group-text {
            background: white;
            border: 2px solid #e0e0e0;
            border-left: none;
            border-radius: 0 15px 15px 0;
            color: #19519D;
        }
        
        .contact-section {
            background: linear-gradient(135deg, #19519D 0%, #0d3164 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .contact-section h3 {
            margin-bottom: 1rem;
        }
        
        .contact-section .btn {
            background: white;
            color: #19519D;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .contact-section .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .no-results {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }
        
        .no-results i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-category {
            text-align: center;
            padding: 2rem;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .faq-header {
                padding: 1.5rem 1rem;
            }
            
            .faq-header h1 {
                font-size: 1.5rem;
            }
            
            .category-section {
                padding: 1rem;
            }
            
            .category-title {
                font-size: 1.2rem;
            }
            
            .accordion-button {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="container faq-container">
        <!-- Back Button -->
        <div class="text-center mb-3">
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                    <a href="../admin/dashboard.php" class="back-button">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                <?php else: ?>
                    <a href="../student/dashboard.php" class="back-button">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="back-button">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Header -->
        <div class="faq-header">
            <div class="app-logo">
                <i class="bi bi-question-circle-fill"></i>
            </div>
            <h1>FAQ & Help Center</h1>
            <p class="text-muted mb-0">Find answers to common questions about USIC</p>
        </div>
        
        <!-- Search Box -->
        <div class="category-section">
            <div class="search-box">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           id="searchInput" 
                           placeholder="Search for questions..."
                           onkeyup="searchFAQ()">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- No Results Message (Hidden by default) -->
        <div class="category-section no-results" id="noResults" style="display: none;">
            <i class="bi bi-search"></i>
            <h5>No results found</h5>
            <p>Try different keywords or browse categories below</p>
        </div>
        
        <!-- FAQ Categories -->
        <?php if (empty($allFaqs)): ?>
            <div class="category-section text-center">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No FAQs Available</h5>
                <p class="text-muted">Check back soon for helpful information!</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <?php 
                $categoryId = $category['id'];
                if (isset($faqsByCategory[$categoryId]) && !empty($faqsByCategory[$categoryId])): 
                ?>
                    <div class="category-section" data-category="<?= $categoryId ?>">
                        <h2 class="category-title">
                            <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                            <?= htmlspecialchars($category['emoji']) ?> <?= htmlspecialchars($category['name']) ?>
                        </h2>
                        
                        <div class="accordion" id="accordion<?= $categoryId ?>">
                            <?php foreach ($faqsByCategory[$categoryId] as $index => $faq): ?>
                                <div class="accordion-item faq-item" 
                                     data-question="<?= strtolower($faq['question']) ?>"
                                     data-answer="<?= strtolower(strip_tags($faq['answer'])) ?>">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button collapsed" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?= $categoryId . $index ?>"
                                                aria-expanded="false">
                                            <i class="bi bi-chevron-right me-2"></i>
                                            <?= htmlspecialchars($faq['question']) ?>
                                        </button>
                                    </h3>
                                    <div id="collapse<?= $categoryId . $index ?>" 
                                         class="accordion-collapse collapse" 
                                         data-bs-parent="#accordion<?= $categoryId ?>">
                                        <div class="accordion-body">
                                            <?= $faq['answer'] ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Contact Support Section -->
        <div class="contact-section">
            <h3><i class="bi bi-headset"></i> Still Need Help?</h3>
            <p class="mb-3">Can't find the answer you're looking for?<br>Our support team is here to help!</p>
            <a href="mailto:support@uptm.edu.my" class="btn">
                <i class="bi bi-envelope-fill me-2"></i>Contact Support
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        function searchFAQ() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            const categories = document.querySelectorAll('.category-section[data-category]');
            const noResults = document.getElementById('noResults');
            
            let hasResults = false;
            
            faqItems.forEach(item => {
                const question = item.getAttribute('data-question');
                const answer = item.getAttribute('data-answer');
                
                if (question.includes(searchInput) || answer.includes(searchInput)) {
                    item.style.display = '';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Hide categories with no visible items
            categories.forEach(category => {
                const visibleItems = category.querySelectorAll('.faq-item:not([style*="display: none"])');
                if (visibleItems.length === 0 && searchInput !== '') {
                    category.style.display = 'none';
                } else {
                    category.style.display = '';
                }
            });
            
            // Show/hide no results message
            if (searchInput !== '' && !hasResults) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        }
        
        // Add icon animation when accordion opens
        document.querySelectorAll('.accordion-button').forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (this.classList.contains('collapsed')) {
                    icon.style.transform = 'rotate(90deg)';
                    icon.style.transition = 'transform 0.3s ease';
                } else {
                    icon.style.transform = 'rotate(0deg)';
                    icon.style.transition = 'transform 0.3s ease';
                }
            });
        });
    </script>
</body>
</html>