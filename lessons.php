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
    <title><?php echo $current_lang === 'rw' ? 'Amasomo' : 'Lessons'; ?> - URUHUSHYA</title>
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
                <a href="lessons.php" class="active"><?php echo $current_lang === 'rw' ? 'Amasomo' : 'Lessons'; ?></a>
                <a href="pricing.php"><?php echo $current_lang === 'rw' ? 'Ibiciro' : 'Pricing'; ?></a>
            </nav>
            
            <div class="header-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="student/dashboard.php" class="btn btn-primary">
                        <?php echo $current_lang === 'rw' ? 'Dashboard' : 'Dashboard'; ?>
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

    <!-- Lessons Hero -->
    <section class="page-hero">
        <div class="container">
            <h1><?php echo $current_lang === 'rw' ? 'Amasomo y\'Amategeko yo Mu Muhanda' : 'Traffic Rules Lessons'; ?></h1>
            <p><?php echo $current_lang === 'rw' 
                ? 'Wige amategeko yose y\'umuhanda mbere yo gukora isuzuma'
                : 'Learn all traffic rules before taking the test'; ?>
            </p>
        </div>
    </section>

    <!-- Lessons Content -->
    <section class="lessons-section">
        <div class="container">
            <div class="lessons-grid-cards">
                <!-- Lesson Card 1 -->
                <div class="lesson-card-modern">
                    <div class="lesson-card-header">
                        <div class="lesson-icon-large">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <span class="lesson-badge">Module 1</span>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Ibimenyetso by\'Umuhanda' : 'Traffic Signs'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Wige ibimenyetso byose by\'umuhanda: Itegeko, Umuburo, n\'Amakuru'
                        : 'Learn all traffic signs: Regulatory, Warning, and Information'; ?>
                    </p>
                    <ul class="lesson-features">
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? '50+ Ibimenyetso' : '50+ Signs'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Amashusho' : 'Visual Examples'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Ibizamini' : 'Quizzes'; ?></li>
                    </ul>
                    <button class="btn-lesson-modern" onclick="<?php echo $is_logged_in ? 'alert(\'Coming soon!\')' : 'openModal(\'loginModal\')'; ?>">
                        <?php echo $current_lang === 'rw' ? 'Tangira Isomo' : 'Start Lesson'; ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>

                <!-- Lesson Card 2 -->
                <div class="lesson-card-modern">
                    <div class="lesson-card-header">
                        <div class="lesson-icon-large">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </div>
                        <span class="lesson-badge">Module 2</span>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Amategeko Rusange' : 'General Rules'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Amategeko y\'ibanze akurikizwa mu muhanda'
                        : 'Basic traffic rules and regulations'; ?>
                    </p>
                    <ul class="lesson-features">
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Umuvuduko' : 'Speed Limits'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Kwiringaniza' : 'Right of Way'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Kunyura' : 'Overtaking'; ?></li>
                    </ul>
                    <button class="btn-lesson-modern" onclick="<?php echo $is_logged_in ? 'alert(\'Coming soon!\')' : 'openModal(\'loginModal\')'; ?>">
                        <?php echo $current_lang === 'rw' ? 'Tangira Isomo' : 'Start Lesson'; ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>

                <!-- Lesson Card 3 -->
                <div class="lesson-card-modern">
                    <div class="lesson-card-header">
                        <div class="lesson-icon-large">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <span class="lesson-badge">Module 3</span>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Umutekano' : 'Road Safety'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Wige uko wakwirinda impanuka'
                        : 'Learn how to prevent accidents'; ?>
                    </p>
                    <ul class="lesson-features">
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Umukandara' : 'Seatbelts'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Kwirinda' : 'Prevention'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Ubufasha' : 'First Aid'; ?></li>
                    </ul>
                    <button class="btn-lesson-modern" onclick="<?php echo $is_logged_in ? 'alert(\'Coming soon!\')' : 'openModal(\'loginModal\')'; ?>">
                        <?php echo $current_lang === 'rw' ? 'Tangira Isomo' : 'Start Lesson'; ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>

                <!-- Lesson Card 4 -->
                <div class="lesson-card-modern">
                    <div class="lesson-card-header">
                        <div class="lesson-icon-large">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        </div>
                        <span class="lesson-badge">Module 4</span>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Gutwara mu Mujyi' : 'City Driving'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Amategeko yo gutwara mu mujyi'
                        : 'Rules for driving in urban areas'; ?>
                    </p>
                    <ul class="lesson-features">
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Trafiki' : 'Traffic Lights'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Abagenzi' : 'Pedestrians'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Parking' : 'Parking'; ?></li>
                    </ul>
                    <button class="btn-lesson-modern" onclick="<?php echo $is_logged_in ? 'alert(\'Coming soon!\')' : 'openModal(\'loginModal\')'; ?>">
                        <?php echo $current_lang === 'rw' ? 'Tangira Isomo' : 'Start Lesson'; ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>

                <!-- Lesson Card 5 -->
                <div class="lesson-card-modern">
                    <div class="lesson-card-header">
                        <div class="lesson-icon-large">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <span class="lesson-badge">Module 5</span>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Ibihano' : 'Penalties'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Menya ibihano by\'ibyaha'
                        : 'Know the penalties for violations'; ?>
                    </p>
                    <ul class="lesson-features">
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Amende' : 'Fines'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Gutwara usikoye' : 'DUI Laws'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Ibyaha' : 'Violations'; ?></li>
                    </ul>
                    <button class="btn-lesson-modern" onclick="<?php echo $is_logged_in ? 'alert(\'Coming soon!\')' : 'openModal(\'loginModal\')'; ?>">
                        <?php echo $current_lang === 'rw' ? 'Tangira Isomo' : 'Start Lesson'; ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>

                <!-- Lesson Card 6 -->
                <div class="lesson-card-modern">
                    <div class="lesson-card-header">
                        <div class="lesson-icon-large">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <span class="lesson-badge">Module 6</span>
                    </div>
                    <h3><?php echo $current_lang === 'rw' ? 'Akaga' : 'Emergencies'; ?></h3>
                    <p><?php echo $current_lang === 'rw'
                        ? 'Uko wakora mu bihe by\'akaga'
                        : 'What to do in emergencies'; ?>
                    </p>
                    <ul class="lesson-features">
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Impanuka' : 'Accidents'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Guhamagara' : 'Emergency Calls'; ?></li>
                        <li><span class="check">✓</span> <?php echo $current_lang === 'rw' ? 'Amafoto' : 'Documentation'; ?></li>
                    </ul>
                    <button class="btn-lesson-modern" onclick="<?php echo $is_logged_in ? 'alert(\'Coming soon!\')' : 'openModal(\'loginModal\')'; ?>">
                        <?php echo $current_lang === 'rw' ? 'Tangira Isomo' : 'Start Lesson'; ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer (same as before) -->
    <!-- Include modals and scripts (same as before) -->
    
</body>
</html>