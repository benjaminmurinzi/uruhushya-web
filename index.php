<?php
require_once 'config.php';
require_once 'includes/language.php';

$is_logged_in = isLoggedIn();

// Load tests from JSON
$tests_file = __DIR__ . '/data/tests.json';
$tests = file_exists($tests_file) ? json_decode(file_get_contents($tests_file), true) : [];
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URUHUSHYA - <?php echo $current_lang === 'rw' ? 'Platform yo kwiga gutwara' : 'Driving Learning Platform'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">URUHUSHYA</a>
            
            <div class="header-buttons">
                <a href="#" onclick="openModal('registerModal'); return false;" class="btn btn-signup">
                    <?php echo $current_lang === 'rw' ? 'Iyandikishe' : 'Sign up'; ?>
                </a>
                <a href="#" onclick="openModal('loginModal'); return false;" class="btn btn-login">
                    <?php echo $current_lang === 'rw' ? 'Injira' : 'Log in'; ?>
                </a>
                <div class="lang-switcher">
                    <button class="btn btn-lang" onclick="toggleLang(event)">
                        <?php echo $current_lang === 'rw' ? 'Kinyarwanda' : 'English'; ?> ▼
                    </button>
                    <div class="lang-dropdown" id="langDropdown">
                        <a href="?lang=rw">Kinyarwanda</a>
                        <a href="?lang=en">English</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <h1><?php echo $current_lang === 'rw' ? 'Ukeneye uruhushya rwo gutwara?' : 'Need a driving license?'; ?></h1>
        <button class="scroll-btn" onclick="scrollToContent()">↓</button>
    </section>

    <!-- Main Content - Tests Section -->
    <section class="content" id="content">
        <div class="container">
            <!-- Tabs -->
            <div class="tabs">
                <a class="tab active" href="index.php">
                    <?php echo $current_lang === 'rw' ? 'Isuzumabumenyi' : 'Tests'; ?>
                </a>
                <a class="tab" href="lessons.php">
                    <?php echo $current_lang === 'rw' ? 'Amasomo' : 'Lessons'; ?>
                </a>
                <a class="tab" href="pricing.php">
                    <?php echo $current_lang === 'rw' ? 'Ibiciro' : 'Pricing'; ?>
                </a>
            </div>

            <!-- Tests Grid -->
            <div class="tests-grid">
                <?php foreach($tests as $test): ?>
                    <div class="test-card">
                        <div class="test-header">
                            <h3><?php echo $test['code']; ?></h3>
                            <?php if ($test['is_free']): ?>
                                <span class="badge-free">FREE</span>
                            <?php endif; ?>
                        </div>
                        <p><?php echo $current_lang === 'rw' ? $test['description_rw'] : $test['description_en']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="load-more">
                <button class="btn btn-view-more">
                    <?php echo $current_lang === 'rw' ? 'Reba ibindi' : 'View more'; ?>
                </button>
            </div>
        </div>
    </section>

    <!-- School & Agent Applications -->
    <section class="applications-section">
        <div class="container">
            <h2><?php echo $current_lang === 'rw' ? 'Ufite Ishuri cyangwa Ushaka kuba Agent?' : 'Have a School or Want to be an Agent?'; ?></h2>
            
            <div class="application-cards">
                <div class="application-card">
                    <h3><?php echo $current_lang === 'rw' ? 'Ishuri ry\'Ugutwara' : 'Driving School'; ?></h3>
                    <p><?php echo $current_lang === 'rw' 
                        ? 'Ufite ishuri ry\'ugutwara? Saba kwinjira kuri platform yacu ugacunga abanyeshuri bawe.'
                        : 'Have a driving school? Apply to join our platform and manage your students.'; ?>
                    </p>
                    <ul>
                        <li><?php echo $current_lang === 'rw' ? 'Gucunga abanyeshuri benshi' : 'Manage unlimited students'; ?></li>
                        <li><?php echo $current_lang === 'rw' ? 'Raporo n\'imikorere' : 'Reports and analytics'; ?></li>
                        <li><?php echo $current_lang === 'rw' ? 'Gukurikirana iterambere' : 'Track student progress'; ?></li>
                    </ul>
                    <a href="auth/school/apply.php" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Saba Kwinjira' : 'Apply Now'; ?>
                    </a>
                </div>

                <div class="application-card">
                    <h3><?php echo $current_lang === 'rw' ? 'Agent' : 'Agent'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Injiza abanyeshuri kuri platform yacu ubone commission kuri buri subscription!'
                        : 'Refer students to our platform and earn commission on every subscription!'; ?>
                    </p>
                    <ul>
                        <li><?php echo $current_lang === 'rw' ? 'Commission: 10-20%' : 'Commission: 10-20%'; ?></li>
                        <li><?php echo $current_lang === 'rw' ? 'Payout ya buri kwezi' : 'Monthly payouts'; ?></li>
                        <li><?php echo $current_lang === 'rw' ? 'Dashboard yawe' : 'Your own dashboard'; ?></li>
                    </ul>
                    <a href="auth/agent/apply.php" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Saba Kuba Agent' : 'Become an Agent'; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- How to Start Learning Section -->
    <section class="how-to-start-section">
        <div class="container">
            <h2><?php echo $current_lang === 'rw' ? 'Ni gute natangira kwiga?' : 'How do I start learning?'; ?></h2>
            <p class="subtitle"><?php echo $current_lang === 'rw' ? 'Injira igana ku intsinzi' : 'The journey to your success'; ?></p>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-icon">
                        <svg width="64" height="64" fill="#1d4ed8" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Fungura konti yo kwiga' : 'Create an account with us'; ?></h3>
                    <p><?php echo $current_lang === 'rw' 
                        ? 'Konti igufasha kubika amakuru y\'uko wiga n\'uko usubiramo ibyo wishe'
                        : 'The account helps you to resume where you left off and track your progress'; ?></p>
                    <a href="#" onclick="openModal('registerModal'); return false;" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Iyandikishe' : 'Sign up'; ?>
                    </a>
                </div>

                <div class="step-card">
                    <div class="step-icon">
                        <svg width="64" height="64" fill="#1d4ed8" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Ishyura ifatabuguzi' : 'Buy a subscription'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Nubwo isomo twarigize ubuntu, amasuzumabumenyi yo akenera ko wishyura'
                        : 'The course is free but the mock tests require a subscription'; ?></p>
                    <a href="#" onclick="openModal('loginModal'); return false;" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Reba Ibiciro' : 'Check Pricing'; ?>
                    </a>
                </div>

                <div class="step-card">
                    <div class="step-icon">
                        <svg width="64" height="64" fill="#1d4ed8" viewBox="0 0 24 24">
                            <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                        </svg>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Tangira kwiga utsinde' : 'Start learning and succeed'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Bona isomo n\'amasuzumabumenyi y\'ibibazo birenga 1000 mu ndimi eshatu'
                        : 'The course and tests have more than 1000 practice questions in 3 languages'; ?></p>
                    <a href="#" onclick="openModal('loginModal'); return false;" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Tangira Kwiga' : 'Go to Learn'; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2><?php echo $current_lang === 'rw' ? 'Bimwe bikunze kwibazwa' : 'Frequently Asked Questions'; ?></h2>
            <p class="subtitle"><?php echo $current_lang === 'rw' ? 'Mubigezwaho na IremboGov' : 'Brought to you by IremboGov'; ?></p>
            
            <div class="faq-list">
                <div class="faq-item">
                    <h3><?php echo $current_lang === 'rw' 
                        ? 'Uko wakwiyandikisha mu kizamini cy\'uruhushya rw\'agateganyo rwo gutwara ibinyabiziga'
                        : 'How to Register for the Provisional Driving Test'; ?></h3>
                    <span class="arrow">→</span>
                </div>
                <div class="faq-item">
                    <h3><?php echo $current_lang === 'rw'
                        ? 'Uko wareba amanota y\'ikizamini cyo gutwara ibinyabiziga'
                        : 'How to Check Driving Test Results'; ?></h3>
                    <span class="arrow">→</span>
                </div>
                <div class="faq-item">
                    <h3><?php echo $current_lang === 'rw'
                        ? 'Uko wakwiyandikisha gukora ikizamini cy\'uruhushya rwa burundu rwo gutwara ibinyabiziga'
                        : 'How to Register for the Definitive Driving Test'; ?></h3>
                    <span class="arrow">→</span>
                </div>
            </div>
            
            <div class="load-more">
                <button class="btn btn-view-more">
                    <?php echo $current_lang === 'rw' ? 'Reba ibindi' : 'View more'; ?>
                </button>
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
                            <p><?php echo $current_lang === 'rw' ? 'Duhamagare cg WhatsApp kuri' : 'Call or WhatsApp'; ?></p>
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
                            <p><?php echo $current_lang === 'rw' ? 'Twoherere kuri' : 'Email at'; ?></p>
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
                            <p><?php echo $current_lang === 'rw' ? 'Dukurikire kuri' : 'Connect with us on'; ?></p>
                            <a href="https://x.com/uruhushya" target="_blank">X</a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg width="32" height="32" fill="white" viewBox="0 0 24 24">
                                <path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/>
                            </svg>
                        </div>
                        <div>
                            <p><?php echo $current_lang === 'rw' ? 'Dukurikire kuri' : 'Follow us on'; ?></p>
                            <a href="https://instagram.com/uruhushya" target="_blank">Instagram</a>
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
                <a href="#" onclick="openForgotPassword(); return false;">
                    <?php echo $current_lang === 'rw' ? 'Wibagiwe password?' : 'Forgot your password?'; ?>
                </a>
                <span>|</span>
                <a href="#" onclick="switchModal('loginModal', 'registerModal'); return false;">
                    <?php echo $current_lang === 'rw' ? 'Ntufite konti? Iyandikishe' : 'Don\'t have account? Register'; ?>
                </a>
            </div>
            
            <a href="auth/google-login.php?type=student" class="btn-google-modal">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <?php echo $current_lang === 'rw' ? 'Injira na Google' : 'Sign in with Google'; ?>
            </a>
            
            <p class="modal-privacy">
                <?php echo $current_lang === 'rw' ? 'Mu kwinjira, uremera' : 'By logging in, you agree to our'; ?> 
                <a href="#"><?php echo $current_lang === 'rw' ? 'amabwiriza y\'ubuzima bwite' : 'privacy policy'; ?></a>
            </p>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal" id="registerModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('registerModal')">&times;</button>
            <h2><?php echo $current_lang === 'rw' ? 'Iyandikishe' : 'Sign up'; ?></h2>
            
            <?php
            if (isset($_GET['error'])) {
                $errors = [
                    'password_mismatch' => ($current_lang === 'rw' ? 'Passwords ntibatunganye' : 'Passwords do not match'),
                    'password_short' => ($current_lang === 'rw' ? 'Password irashakwa kuba 6 ikarakatari kuri rusange' : 'Password must be at least 6 characters'),
                    'email_exists' => ($current_lang === 'rw' ? 'Email iyo imaze gushyirwa imbere' : 'Email already registered'),
                    'missing_fields' => ($current_lang === 'rw' ? 'Imibare yose irashakwa' : 'All fields are required'),
                    'database_error' => ($current_lang === 'rw' ? 'Ikosa ryamakuru yarerekeranye' : 'Database error occurred'),
                    'registration_failed' => ($current_lang === 'rw' ? 'Iyandikishe ntiryakomeye' : 'Registration failed')
                ];
                $error = $errors[$_GET['error']] ?? ($current_lang === 'rw' ? 'Ikosa ryabaye' : 'An error occurred');
                echo "<div class='alert alert-danger' style='margin-bottom: 15px; padding: 10px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;'>$error</div>";
            }
            if (isset($_GET['success'])) {
                echo "<div class='alert alert-success' style='margin-bottom: 15px; padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;'>" . ($current_lang === 'rw' ? 'Iyandikishe ryakomeye! Injira na konti yawe' : 'Registration successful! Please log in') . "</div>";
            }
            ?>
            
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
            
            <a href="auth/google-login.php?type=student" class="btn-google-modal">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <?php echo $current_lang === 'rw' ? 'Iyandikishe na Google' : 'Sign up with Google'; ?>
            </a>
            
            <p class="modal-privacy">
                <?php echo $current_lang === 'rw' ? 'Mu kwiyandikisha, uremera' : 'By signing up, you agree to our'; ?> 
                <a href="#"><?php echo $current_lang === 'rw' ? 'amabwiriza y\'ubuzima bwite' : 'privacy policy'; ?></a>
            </p>
        </div>
    </div>

    <script>
        function toggleLang(e) {
            e.stopPropagation();
            document.getElementById('langDropdown').classList.toggle('show');
        }

        function scrollToContent() {
            document.getElementById('content').scrollIntoView({ behavior: 'smooth' });
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

        function openForgotPassword() {
            closeModal('loginModal');
            alert('Password reset feature coming soon!');
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
</body>
</html>