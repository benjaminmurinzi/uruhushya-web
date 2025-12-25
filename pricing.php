<?php
require_once 'config.php';
require_once 'includes/language.php';

$is_logged_in = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang === 'rw' ? 'Ibiciro' : 'Pricing'; ?> - URUHUSHYA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header with Navigation -->
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">URUHUSHYA</a>
            
            <!-- Navigation Menu -->
            <nav class="main-nav">
                <a href="index.php"><?php echo $current_lang === 'rw' ? 'Ahabanza' : 'Home'; ?></a>
                <a href="lessons.php"><?php echo $current_lang === 'rw' ? 'Amasomo' : 'Lessons'; ?></a>
                <a href="pricing.php" class="active"><?php echo $current_lang === 'rw' ? 'Ibiciro' : 'Pricing'; ?></a>
            </nav>
            
            <div class="header-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="student/dashboard.php" class="btn btn-primary">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="#" onclick="openModal('loginModal'); return false;" class="btn btn-login">
                        <?php echo $current_lang === 'rw' ? 'Injira' : 'Log in'; ?>
                    </a>
                    <a href="#" onclick="openModal('registerModal'); return false;" class="btn btn-signup">
                        <?php echo $current_lang === 'rw' ? 'Iyandikishe' : 'Sign up'; ?>
                    </a>
                <?php endif; ?>
                <div class="lang-switcher">
                    <button class="btn btn-lang" onclick="toggleLang(event)">
                        <?php echo $current_lang === 'rw' ? 'RW' : 'EN'; ?> ▼
                    </button>
                    <div class="lang-dropdown" id="langDropdown">
                        <a href="?lang=rw">Kinyarwanda</a>
                        <a href="?lang=en">English</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Pricing Hero -->
    <section class="pricing-hero">
        <div class="container">
            <h1><?php echo $current_lang === 'rw' ? 'Hitamo Gahunda Ikubereye' : 'Choose Your Plan'; ?></h1>
            <p><?php echo $current_lang === 'rw'
                ? 'Hitamo gahunda ikubereye kandi utangire kwiga uyu munsi'
                : 'Select the perfect plan for your needs and start learning today'; ?>
            </p>
        </div>
    </section>

    <!-- Pricing Content -->
    <section class="pricing-section-modern">
        <div class="container">
            
            <!-- Pricing Tabs -->
            <div class="pricing-tabs">
                <button class="pricing-tab active" onclick="switchPricingTab('students')">
                    <?php echo $current_lang === 'rw' ? 'Abanyeshuri' : 'Students'; ?>
                </button>
                <button class="pricing-tab" onclick="switchPricingTab('schools')">
                    <?php echo $current_lang === 'rw' ? 'Amashuri' : 'Schools'; ?>
                </button>
            </div>

            <!-- STUDENTS PRICING -->
            <div class="pricing-tab-content active" id="students-pricing">
                <div class="pricing-cards-modern">
                    
                    <!-- Card 1: 1 Day -->
                    <div class="pricing-card-modern">
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
                        
                        <button class="btn-choose-plan" 
                                onclick="initiatePayment(1, 500, '1 Day')"
                                data-plan-id="1" 
                                data-plan-price="500" 
                                data-plan-name="1 Day">
                            <?php echo $current_lang === 'rw' ? 'Hitamo Gahunda' : 'Choose Plan'; ?>
                        </button>
                    </div>

                    <!-- Card 2: 1 Week (Featured) -->
                    <div class="pricing-card-modern featured">
                        <div class="popular-badge">MOST POPULAR</div>
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
                        
                        <button class="btn-choose-plan featured-btn" 
                                onclick="initiatePayment(2, 2000, '1 Week')"
                                data-plan-id="2" 
                                data-plan-price="2000" 
                                data-plan-name="1 Week">
                            <?php echo $current_lang === 'rw' ? 'Hitamo Gahunda' : 'Choose Plan'; ?>
                        </button>
                    </div>

                    <!-- Card 3: 1 Month -->
                    <div class="pricing-card-modern">
                        <h3 class="plan-name">1 Month</h3>
                        <div class="plan-price">
                            <span class="price-amount">5,000</span>
                            <span class="price-currency">RWF</span>
                        </div>
                        <p class="plan-duration">For 1 month</p>
                        
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
                            <li>
                                <span class="icon-check">✓</span>
                                <span>24/7 support</span>
                            </li>
                        </ul>
                        
                        <button class="btn-choose-plan" 
                                onclick="initiatePayment(3, 5000, '1 Month')"
                                data-plan-id="3" 
                                data-plan-price="5000" 
                                data-plan-name="1 Month">
                            <?php echo $current_lang === 'rw' ? 'Hitamo Gahunda' : 'Choose Plan'; ?>
                        </button>
                    </div>

                </div>
            </div>

            <!-- SCHOOLS PRICING -->
            <div class="pricing-tab-content" id="schools-pricing">
                <div class="pricing-cards-modern">
                    
                    <!-- Card 1: 1 Month -->
                    <div class="pricing-card-modern">
                        <h3 class="plan-name">1 Month</h3>
                        <div class="plan-price">
                            <span class="price-amount">50,000</span>
                            <span class="price-currency">RWF</span>
                        </div>
                        <p class="plan-duration">Per month</p>
                        
                        <ul class="plan-features">
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Unlimited students</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Bulk registration</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Reports & analytics</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Student tracking</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Card 2: 3 Months (Featured) -->
                    <div class="pricing-card-modern featured">
                        <div class="popular-badge">MOST POPULAR</div>
                        <h3 class="plan-name">3 Months</h3>
                        <div class="plan-price">
                            <span class="price-amount">120,000</span>
                            <span class="price-currency">RWF</span>
                        </div>
                        <p class="plan-duration">For 3 months</p>
                        <div class="save-badge">Save 20%</div>
                        
                        <ul class="plan-features">
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Unlimited students</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Bulk registration</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Reports & analytics</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Student tracking</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Priority support</span>
                            </li>
                        </ul>
                        
                        <button class="btn-choose-plan featured-btn" onclick="openModal('loginModal')">
                            Apply Now
                        </button>
                    </div>

                    <!-- Card 3: Custom -->
                    <div class="pricing-card-modern">
                        <h3 class="plan-name">6+ Months</h3>
                        <div class="plan-price">
                            <span class="price-amount" style="font-size: 42px;">Custom</span>
                        </div>
                        <p class="plan-duration">Custom pricing</p>
                        
                        <ul class="plan-features">
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Everything included</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Training included</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Dedicated account manager</span>
                            </li>
                            <li>
                                <span class="icon-check">✓</span>
                                <span>Maximum savings</span>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods-section">
                <h3><?php echo $current_lang === 'rw' ? 'Uburyo bwo Kwishyura' : 'Payment Methods'; ?></h3>
                <div class="payment-methods-grid">
                    <div class="payment-method">MTN MoMo</div>
                    <div class="payment-method">Airtel Money</div>
                    <div class="payment-method">Bank Card</div>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <h3><?php echo $current_lang === 'rw' ? 'Twandikire kugira ngo tuguhe ubufasha buhuse' : 'Reach out to us for instant support'; ?></h3>
                    <p><?php echo $current_lang === 'rw' 
                        ? 'Turi hano gufasha binyuze muburyo butandukanye. Hitamo uburyo bukubereye!'
                        : 'We are here to help you through multiple platforms. Select a method that works best for you!'; ?></p>
                </div>
                
                <div class="footer-right">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="32" height="32" fill="white" viewBox="0 0 24 24">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                        </div>
                        <div>
                            <p><?php echo $current_lang === 'rw' ? 'Duhamagare' : 'Call us'; ?></p>
                            <a href="tel:+250789733274">+250 789 733 274</a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="32" height="32" fill="white" viewBox="0 0 24 24">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </div>
                        <div>
                            <p><?php echo $current_lang === 'rw' ? 'Twoherere' : 'Email'; ?></p>
                            <a href="mailto:info@uruhushya.com">info@uruhushya.com</a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="32" height="32" fill="white" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </div>
                        <div>
                            <p>X (Twitter)</p>
                            <a href="https://x.com/uruhushya" target="_blank">@uruhushya</a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="32" height="32" fill="white" viewBox="0 0 24 24">
                                <path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/>
                            </svg>
                        </div>
                        <div>
                            <p>Instagram</p>
                            <a href="https://instagram.com/uruhushya" target="_blank">@uruhushya</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p><a href="#">Privacy Policy</a></p>
                <div class="footer-logo">
                    <span class="logo-badge">URUHUSHYA</span>
                </div>
                <p>&copy; Copyright URUHUSHYA Solutions Ltd.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('loginModal')">&times;</button>
            <h2><?php echo $current_lang === 'rw' ? 'Injira' : 'Log in'; ?></h2>
            
            <form method="POST" action="auth/login-handler.php" class="modal-form">
                <input type="hidden" name="user_type" value="student">
                
                <div class="form-group">
                    <input type="text" name="email_or_phone" placeholder="07xx xxx xxx" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <?php echo $current_lang === 'rw' ? 'Injira' : 'Log in'; ?>
                </button>
            </form>
            
            <div class="modal-links">
                <a href="#" onclick="switchModal('loginModal', 'registerModal'); return false;">
                    <?php echo $current_lang === 'rw' ? 'Ntufite konti? Iyandikishe' : 'Don\'t have account? Register'; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal" id="registerModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('registerModal')">&times;</button>
            <h2><?php echo $current_lang === 'rw' ? 'Iyandikishe' : 'Sign up'; ?></h2>
            
            <form method="POST" action="auth/register-handler.php" class="modal-form">
                <input type="hidden" name="user_type" value="student">
                
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="<?php echo $current_lang === 'rw' ? 'Amazina yawe yose' : 'Full Name'; ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="07xx xxx xxx" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="<?php echo $current_lang === 'rw' ? 'Emeza password' : 'Confirm Password'; ?>" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <?php echo $current_lang === 'rw' ? 'Iyandikishe' : 'Sign up'; ?>
                </button>
            </form>
            
            <div class="modal-links">
                <a href="#" onclick="switchModal('registerModal', 'loginModal'); return false;">
                    <?php echo $current_lang === 'rw' ? 'Usanzwe ufite konti? Injira' : 'Already have account? Log in'; ?>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Language Switcher
        function toggleLang(e) {
            e.stopPropagation();
            document.getElementById('langDropdown').classList.toggle('show');
        }

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function switchModal(closeId, openId) {
            closeModal(closeId);
            setTimeout(() => openModal(openId), 300);
        }

        // Pricing Tab Switching
        function switchPricingTab(tab) {
            // Remove active from all tabs
            document.querySelectorAll('.pricing-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.pricing-tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active to clicked tab
            event.target.classList.add('active');
            document.getElementById(tab + '-pricing').classList.add('active');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
            
            if (!e.target.closest('.lang-switcher')) {
                document.getElementById('langDropdown').classList.remove('show');
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });
    </script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script src="assets/js/flutterwave-payment.js"></script>
</body>
</html>