<?php
require_once '../config.php';
require_once '../includes/language.php';

// Check if user is logged in
if (!isLoggedIn() || getUserType() !== 'student') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$user = getCurrentUser();
$welcome = isset($_GET['welcome']) ? true : false;

// Get available tests
$stmt = $conn->prepare("SELECT * FROM test_templates ORDER BY test_code DESC");
$stmt->execute();
$tests = $stmt->fetchAll();

// Get student stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_attempts FROM student_test_attempts WHERE student_id = ?");
$stmt->execute([$user['id']]);
$total_attempts = $stmt->fetch()['total_attempts'];

$stmt = $conn->prepare("SELECT COUNT(*) as passed_tests FROM student_test_attempts WHERE student_id = ? AND passed = 1");
$stmt->execute([$user['id']]);
$passed_tests = $stmt->fetch()['passed_tests'];

$success_rate = $total_attempts > 0 ? round(($passed_tests / $total_attempts) * 100) : 0;
$subscription_days = 0; // TODO: Implement subscription
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - URUHUSHYA</title>
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Compact Blue Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="logo">URUHUSHYA</h1>
                </div>
                
                <div class="header-right">
                    <!-- Language Switcher -->
                    <div class="language-selector" onclick="toggleLanguage()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                        <span id="currentLang"><?php echo $current_lang === 'rw' ? 'Kinyarwanda' : 'English'; ?></span>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="user-profile">
                        <a href="profile.php" class="nav-link">
                             <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                 <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                 <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        </a>
                    </div>
                    <!-- Add History Link -->
    <a href="history.php" class="nav-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        <?php echo $current_lang === 'rw' ? 'Amateka' : 'History'; ?>
    </a>
                    <!-- Logout -->
                    <a href="../auth/logout.php" class="btn-logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <?php echo $current_lang === 'rw' ? 'Sohoka' : 'Logout'; ?>
                    </a>
                </div>
            </div>
            
            <!-- Compact Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card-compact">
                    <div class="stat-label"><?php echo $current_lang === 'rw' ? 'Ifatabuguzi - IMINSI' : 'Subscription - DAYS'; ?></div>
                    <div class="stat-number"><?php echo $subscription_days; ?></div>
                </div>
                
                <div class="stat-card-compact">
                    <div class="stat-label"><?php echo $current_lang === 'rw' ? 'Ikizere cyo gutsinda' : 'Success Rate'; ?></div>
                    <div class="stat-number"><?php echo $success_rate; ?>%</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="container">
            
            <?php if ($welcome): ?>
                <div class="welcome-message">
                    <h2><?php echo $current_lang === 'rw' ? 'Murakaza neza' : 'Welcome'; ?>, <?php echo htmlspecialchars($user['full_name']); ?>! 🎉</h2>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="tabs-navigation">
                <button class="tab-btn active" data-tab="tests">
                    <?php echo $current_lang === 'rw' ? 'Isuzumabumenyi' : 'Tests'; ?>
                </button>
                <button class="tab-btn" data-tab="courses">
                    <?php echo $current_lang === 'rw' ? 'Amasomo' : 'Courses'; ?>
                </button>
                <button class="tab-btn" data-tab="certificate">
                    <?php echo $current_lang === 'rw' ? 'Impamyabumenyi' : 'Certificate'; ?>
                </button>
                <button class="tab-btn" data-tab="pricing">
                    <?php echo $current_lang === 'rw' ? 'Ibiciro' : 'Pricing'; ?>
                </button>
            </div>

            <!-- Tests Tab -->
            <div class="tab-content active" id="tests-tab">
                <div class="tests-grid">
                    <?php foreach($tests as $test): ?>
                        <div class="test-card" onclick="startTest(<?php echo $test['id']; ?>, <?php echo $test['is_free'] ? 'true' : 'false'; ?>)">
                            <div class="test-header">
                                <h3><?php echo $test['test_code']; ?></h3>
                                <?php if ($test['is_free']): ?>
                                    <span class="badge-free">FREE</span>
                                <?php else: ?>
                                    <span class="badge-premium">PREMIUM</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="test-description">
                                <?php echo $current_lang === 'rw' ? $test['description_rw'] : $test['description_en']; ?>
                            </p>
                            
                            <div class="test-details">
                                <div class="detail-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span><?php echo $test['time_limit_minutes']; ?> min</span>
                                </div>
                                <div class="detail-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    <span><?php echo $test['total_questions']; ?> <?php echo $current_lang === 'rw' ? 'ibibazo' : 'questions'; ?></span>
                                </div>
                                <div class="detail-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span><?php echo $current_lang === 'rw' ? 'Gutsinza:' : 'Pass:'; ?> <?php echo $test['passing_score']; ?>/<?php echo $test['total_questions']; ?></span>
                                </div>
                            </div>
                            
                            <button class="btn-start-test">
                                <?php if ($test['is_free']): ?>
                                    <?php echo $current_lang === 'rw' ? 'Tangira Isuzuma' : 'Start Test'; ?>
                                <?php else: ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    <?php echo $current_lang === 'rw' ? 'Ifatabuguzi Risabwa' : 'Subscription Required'; ?>
                                <?php endif; ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Courses Tab -->
            <div class="tab-content" id="courses-tab">
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                    <h3><?php echo $current_lang === 'rw' ? 'Amasomo azaza vuba' : 'Courses Coming Soon'; ?></h3>
                </div>
            </div>

            <!-- Certificate Tab -->
            <div class="tab-content" id="certificate-tab">
                <div class="certificate-section">
                    <div class="certificate-card">
                        <h2><?php echo $current_lang === 'rw' ? 'Icyemezo cy\'Ubumenyi' : 'Certificate of Completion'; ?></h2>
                        
                        <?php if ($passed_tests >= 15): ?>
                            <div class="certificate-status success">
                                ✅ <?php echo $current_lang === 'rw' ? 'Ubishoboye! Icyemezo cyawe gihari.' : 'Congratulations! Your certificate is ready.'; ?>
                            </div>
                            <button class="btn btn-primary"><?php echo $current_lang === 'rw' ? 'Manura Icyemezo' : 'Download Certificate'; ?></button>
                        <?php else: ?>
                            <div class="certificate-status pending">
                                ❌ <?php echo $current_lang === 'rw' ? 'Icyemezo kiracyari kitaboneka' : 'Certificate not yet available'; ?>
                            </div>
                            <div class="progress-info">
                                <p><?php echo $current_lang === 'rw' ? 'Amasuzuma yatsindiwe:' : 'Tests passed:'; ?> <strong><?php echo $passed_tests; ?>/15</strong></p>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, ($passed_tests/15)*100); ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pricing Tab -->
            <!-- Tab Content: Pricing -->
<div class="tab-content" id="pricing-tab">
    <div class="pricing-intro-dashboard">
        <h2><?php echo $current_lang === 'rw' ? 'Hitamo Gahunda Ikubereye' : 'Choose Your Plan'; ?></h2>
        <p><?php echo $current_lang === 'rw'
            ? 'Kora ifatabuguzi kugira ngo ubone amasuzuma yose'
            : 'Subscribe to access all premium tests'; ?>
        </p>
    </div>

    <div class="pricing-cards-dashboard">
        
        <!-- Card 1: 1 Day -->
        <div class="pricing-card-dashboard">
            <h3 class="plan-name">1 Day</h3>
            <div class="plan-price">
                <span class="price-amount">500</span>
                <span class="price-currency">RWF</span>
            </div>
            <p class="plan-duration">For 1 day</p>
            
            <ul class="plan-features">
                <li>
                    <span class="icon-check">✓</span>
                    <span>All mock tests</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>1000+ questions</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Detailed explanations</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Certificate on passing</span>
                </li>
            </ul>
            
            <button class="btn-choose-plan-dashboard" 
                    onclick="initiatePayment(1, 500, '1 Day')"
                    data-plan-id="1" 
                    data-plan-price="500" 
                    data-plan-name="1 Day">
                <?php echo $current_lang === 'rw' ? 'Hitamo Gahunda' : 'Choose Plan'; ?>
            </button>
        </div>

        <!-- Card 2: 1 Week (Featured) -->
        <div class="pricing-card-dashboard featured">
            <div class="popular-badge-dashboard">MOST POPULAR</div>
            <h3 class="plan-name">1 Week</h3>
            <div class="plan-price">
                <span class="price-amount">2,000</span>
                <span class="price-currency">RWF</span>
            </div>
            <p class="plan-duration">For 1 week</p>
            
            <ul class="plan-features">
                <li>
                    <span class="icon-check">✓</span>
                    <span>All mock tests</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>1000+ questions</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Detailed explanations</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Certificate on passing</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Progress tracking</span>
                </li>
            </ul>
            
            <button class="btn-choose-plan-dashboard featured-btn" 
                    onclick="initiatePayment(2, 2000, '1 Week')"
                    data-plan-id="2" 
                    data-plan-price="2000" 
                    data-plan-name="1 Week">
                <?php echo $current_lang === 'rw' ? 'Hitamo Gahunda' : 'Choose Plan'; ?>
            </button>
        </div>

        <!-- Card 3: 1 Month -->
        <div class="pricing-card-dashboard">
            <h3 class="plan-name">1 Month</h3>
            <div class="plan-price">
                <span class="price-amount">5,000</span>
                <span class="price-currency">RWF</span>
            </div>
            <p class="plan-duration">For 1 month</p>
            
            <ul class="plan-features">
                <li>
                    <span class="icon-check">✓</span>
                    <span>1000+ questions</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>All mock tests</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Detailed explanations</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Certificate on passing</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>Progress tracking</span>
                </li>
                <li>
                    <span class="icon-check">✓</span>
                    <span>24/7 support</span>
                </li>
            </ul>
            
            <button class="btn-choose-plan-dashboard" 
                    onclick="alert('Payment integration coming soon!')"
                    data-plan-id="3" 
                    data-plan-price="5000" 
                    data-plan-name="1 Month">
                <?php echo $current_lang === 'rw' ? 'Hitamo Gahunda' : 'Choose Plan'; ?>
            </button>
        </div>

    </div>

    <!-- Payment Methods -->
    <div class="payment-methods-dashboard">
        <h3><?php echo $current_lang === 'rw' ? 'Uburyo bwo Kwishyura' : 'Payment Methods'; ?></h3>
        <div class="payment-methods-grid-dashboard">
            <div class="payment-method-dashboard">
                <span>MTN MoMo</span>
            </div>
            <div class="payment-method-dashboard">
                <span>Airtel Money</span>
            </div>
            <div class="payment-method-dashboard">
                <span>Bank Card</span>
            </div>
        </div>
    </div>
</div>
        </div>
    </main>

    <!-- Subscription Required Modal -->
    <div id="subscriptionModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeSubscriptionModal()">&times;</span>
            <h2>🔒 <?php echo $current_lang === 'rw' ? 'Ifatabuguzi Risabwa' : 'Subscription Required'; ?></h2>
            <p><?php echo $current_lang === 'rw' ? 'Ugomba kuba ufite ifatabuguzi kugirango ubone iki gisuzuma.' : 'You need an active subscription to access this test.'; ?></p>
            <button class="btn btn-primary" onclick="document.querySelector('[data-tab=pricing]').click(); closeSubscriptionModal();">
                <?php echo $current_lang === 'rw' ? 'Reba Ibiciro' : 'View Pricing'; ?>
            </button>
        </div>
    </div>

    <script src="../assets/js/student-dashboard.js"></script>
    <!-- Flutterwave Payment Integration -->
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script src="../assets/js/flutterwave-payment.js"></script>
    <script>
        // Language toggle
        function toggleLanguage() {
            window.location.href = '../includes/language.php?redirect=student/dashboard.php';
        }
        
        // Start test function
        function startTest(testId, isFree) {
            if (isFree) {
                window.location.href = 'take-test.php?test=' + testId;
            } else {
                // Check if user has subscription (placeholder)
                const hasSubscription = <?php echo $subscription_days > 0 ? 'true' : 'false'; ?>;
                
                if (hasSubscription) {
                    window.location.href = 'take-test.php?test=' + testId;
                } else {
                    document.getElementById('subscriptionModal').style.display = 'flex';
                }
            }
        }
        
        function closeSubscriptionModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
        }
    </script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script src="../assets/js/flutterwave-payment.js"></script>
</body>
</html>