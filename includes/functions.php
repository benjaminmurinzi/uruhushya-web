<?php
/**
 * =====================================================
 * URUHUSHYA - HELPER FUNCTIONS
 * =====================================================
 * This file contains all the reusable functions used
 * throughout the platform
 * 
 * Categories:
 * 1. Authentication & User Management
 * 2. Subscription Management
 * 3. Agent Commission Functions
 * 4. School Management
 * 5. Test & Course Functions
 * 6. Notification Functions
 * 7. File Upload Functions
 * 8. Utility Functions
 * =====================================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db-connect.php';

/**
 * ==========================================================================
 * 1. AUTHENTICATION & USER MANAGEMENT
 * ==========================================================================
 */

/**
 * Register a new user (student, school, or agent)
 * 
 * @param array $data User data
 * @param string $user_type 'student', 'school', or 'agent'
 * @return array Success/error response
 */
function registerUser($data, $user_type = 'student') {
    global $conn;
    
    // Validate email
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Check if email already exists
    $check = db_select_one("SELECT id FROM users WHERE email = ?", [$data['email']]);
    if ($check) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    
    try {
        if ($user_type === 'student') {
            // Register student
            $query = "INSERT INTO users (
                user_type, email, password, phone, full_name,
                date_of_birth, gender, id_number, registered_by,
                registered_by_id, agent_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                'student',
                $data['email'],
                $hashed_password,
                $data['phone'],
                $data['full_name'],
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null,
                $data['id_number'] ?? null,
                $data['registered_by'] ?? 'self',
                $data['registered_by_id'] ?? null,
                $data['agent_id'] ?? null
            ];
            
        } elseif ($user_type === 'school') {
            // Register school (pending approval)
            $query = "INSERT INTO users (
                user_type, email, password, phone, full_name,
                school_name, school_type, tin_number, license_number,
                school_address, district, sector, director_name, director_phone,
                school_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $params = [
                'school',
                $data['email'],
                $hashed_password,
                $data['phone'],
                $data['full_name'],
                $data['school_name'],
                $data['school_type'] ?? null,
                $data['tin_number'] ?? null,
                $data['license_number'] ?? null,
                $data['school_address'] ?? null,
                $data['district'] ?? null,
                $data['sector'] ?? null,
                $data['director_name'] ?? null,
                $data['director_phone'] ?? null
            ];
            
        } elseif ($user_type === 'agent') {
            // Generate unique agent code
            $agent_code = generateAgentCode();
            
            // Register agent (pending approval)
            $query = "INSERT INTO users (
                user_type, email, password, phone, full_name,
                id_number, district, sector, agent_code,
                bank_name, bank_account_number, bank_account_name,
                mobile_money_number, mobile_money_provider,
                referred_by_agent_id, agent_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $params = [
                'agent',
                $data['email'],
                $hashed_password,
                $data['phone'],
                $data['full_name'],
                $data['id_number'] ?? null,
                $data['district'] ?? null,
                $data['sector'] ?? null,
                $agent_code,
                $data['bank_name'] ?? null,
                $data['bank_account_number'] ?? null,
                $data['bank_account_name'] ?? null,
                $data['mobile_money_number'] ?? null,
                $data['mobile_money_provider'] ?? null,
                $data['referred_by_agent_id'] ?? null
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid user type'];
        }
        
        db_execute($query, $params);
        $user_id = db_last_insert_id();
        
        // Log the registration
        logAudit($user_id, $user_type, 'registration', "$user_type registered successfully");
        
        // Send welcome email (if email is configured)
        // sendWelcomeEmail($data['email'], $data['full_name'], $user_type);
        
        return [
            'success' => true,
            'message' => ucfirst($user_type) . ' registered successfully',
            'user_id' => $user_id,
            'agent_code' => $agent_code ?? null
        ];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Login user
 * 
 * @param string $email Email or phone
 * @param string $password Password
 * @param string $user_type Expected user type
 * @return array Success/error response
 */
function loginUser($email, $password, $user_type = null) {
    // Find user by email or phone
    $user = db_select_one(
        "SELECT * FROM users WHERE (email = ? OR phone = ?) AND account_status = 'active'",
        [$email, $email]
    );
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Incorrect password'];
    }
    
    // Check user type if specified
    if ($user_type && $user['user_type'] !== $user_type) {
        return ['success' => false, 'message' => 'Invalid login portal'];
    }
    
    // Check if school/agent is approved
    if ($user['user_type'] === 'school' && $user['school_status'] !== 'approved') {
        return ['success' => false, 'message' => 'School account pending approval'];
    }
    
    if ($user['user_type'] === 'agent' && $user['agent_status'] !== 'approved') {
        return ['success' => false, 'message' => 'Agent account pending approval'];
    }
    
    // Update last login
    db_execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'user_type' => $user['user_type']
    ];
    
    // Log the login
    logAudit($user['id'], $user['user_type'], 'login', 'User logged in');
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user_type' => $user['user_type'],
        'redirect' => getDashboardUrl($user['user_type'])
    ];
}

/**
 * Login admin user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return array Success/error response
 */
function loginAdmin($username, $password) {
    $admin = db_select_one(
        "SELECT * FROM admin_users WHERE username = ? AND status = 'active'",
        [$username]
    );
    
    if (!$admin) {
        return ['success' => false, 'message' => 'Admin not found'];
    }
    
    if (!password_verify($password, $admin['password'])) {
        return ['success' => false, 'message' => 'Incorrect password'];
    }
    
    // Update last login
    db_execute("UPDATE admin_users SET last_login = NOW() WHERE id = ?", [$admin['id']]);
    
    // Set session
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['user_type'] = 'admin';
    $_SESSION['admin'] = [
        'id' => $admin['id'],
        'username' => $admin['username'],
        'full_name' => $admin['full_name'],
        'role' => $admin['role']
    ];
    
    logAudit($admin['id'], 'admin', 'login', 'Admin logged in');
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'redirect' => SITE_URL . '/admin/index.php'
    ];
}

/**
 * Logout user
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logAudit($_SESSION['user_id'], getUserType(), 'logout', 'User logged out');
    }
    
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

/**
 * Get dashboard URL based on user type
 */
function getDashboardUrl($user_type) {
    $urls = [
        'student' => SITE_URL . '/student/dashboard.php',
        'school' => SITE_URL . '/school/dashboard.php',
        'agent' => SITE_URL . '/agent/dashboard.php',
        'admin' => SITE_URL . '/admin/index.php'
    ];
    
    return $urls[$user_type] ?? SITE_URL . '/index.php';
}

/**
 * Generate unique agent code
 */
function generateAgentCode() {
    $year = date('Y');
    
    // Get last agent number for this year
    $last_agent = db_select_one(
        "SELECT agent_code FROM users WHERE user_type = 'agent' 
        AND agent_code LIKE ? ORDER BY id DESC LIMIT 1",
        ["AGT-$year-%"]
    );
    
    if ($last_agent) {
        // Extract number and increment
        $last_number = (int)substr($last_agent['agent_code'], -3);
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return sprintf("AGT-%s-%03d", $year, $new_number);
}

/**
 * Get user by ID
 */
function getUserById($user_id) {
    return db_select_one("SELECT * FROM users WHERE id = ?", [$user_id]);
}

/**
 * Update user profile
 */
function updateUserProfile($user_id, $data) {
    $allowed_fields = ['full_name', 'phone', 'date_of_birth', 'gender', 'id_number'];
    $update_fields = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $update_fields[] = "$key = ?";
            $params[] = $value;
        }
    }
    
    if (empty($update_fields)) {
        return ['success' => false, 'message' => 'No valid fields to update'];
    }
    
    $params[] = $user_id;
    $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    if (db_execute($query, $params)) {
        return ['success' => true, 'message' => 'Profile updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Update failed'];
}

/**
 * ==========================================================================
 * 2. SUBSCRIPTION MANAGEMENT
 * ==========================================================================
 */

/**
 * Check if user has active subscription
 */
function hasActiveSubscription($user_id) {
    $user = getUserById($user_id);
    
    if (!$user) return false;
    
    if ($user['subscription_type'] === 'free') return false;
    
    if (empty($user['subscription_end'])) return false;
    
    // Check if subscription hasn't expired
    return strtotime($user['subscription_end']) >= strtotime('today');
}

/**
 * Get subscription status
 */
function getSubscriptionStatus($user_id) {
    $user = getUserById($user_id);
    
    if (!$user) return ['status' => 'unknown'];
    
    if ($user['subscription_type'] === 'free') {
        return [
            'status' => 'free',
            'type' => 'free',
            'message' => 'No active subscription'
        ];
    }
    
    $end_date = strtotime($user['subscription_end']);
    $today = strtotime('today');
    
    if ($end_date < $today) {
        return [
            'status' => 'expired',
            'type' => $user['subscription_type'],
            'expired_on' => $user['subscription_end'],
            'message' => 'Subscription expired'
        ];
    }
    
    $days_remaining = ceil(($end_date - $today) / 86400);
    
    return [
        'status' => 'active',
        'type' => $user['subscription_type'],
        'start_date' => $user['subscription_start'],
        'end_date' => $user['subscription_end'],
        'days_remaining' => $days_remaining,
        'auto_renew' => (bool)$user['subscription_auto_renew'],
        'message' => "Active ($days_remaining days remaining)"
    ];
}

/**
 * Create subscription
 */
function createSubscription($user_id, $plan_type, $amount, $agent_id = null) {
    global $conn;
    
    $user = getUserById($user_id);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Calculate dates based on plan
    $start_date = date('Y-m-d');
    $duration_days = getSubscriptionDuration($plan_type);
    $end_date = date('Y-m-d', strtotime("+$duration_days days"));
    
    try {
        db_begin_transaction();
        
        // Create subscription record
        $query = "INSERT INTO subscriptions (
            user_id, user_type, plan_type, amount,
            start_date, end_date, payment_status, agent_id
        ) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)";
        
        db_execute($query, [
            $user_id,
            $user['user_type'],
            $plan_type,
            $amount,
            $start_date,
            $end_date,
            $agent_id
        ]);
        
        $subscription_id = db_last_insert_id();
        
        // Update user subscription info
        $capacity = null;
        if ($user['user_type'] === 'school') {
            $capacity = getSchoolCapacity($plan_type);
        }
        
        db_execute("UPDATE users SET 
            subscription_type = ?,
            subscription_start = ?,
            subscription_end = ?,
            max_students = ?
            WHERE id = ?",
            [$plan_type, $start_date, $end_date, $capacity, $user_id]
        );
        
        // Calculate and record agent commission if applicable
        if ($agent_id) {
            $commission = calculateCommission($plan_type, $amount, $user['user_type']);
            
            if ($commission > 0) {
                // Record referral
                db_execute("INSERT INTO agent_referrals (
                    agent_id, referred_user_id, referred_type,
                    subscription_id, commission_amount, commission_percentage
                ) VALUES (?, ?, ?, ?, ?, ?)",
                    [$agent_id, $user_id, 'student', $subscription_id, $commission, ($commission/$amount)*100]
                );
                
                // Update agent earnings
                db_execute("UPDATE users SET 
                    total_earnings = total_earnings + ?,
                    pending_payout = pending_payout + ?
                    WHERE id = ?",
                    [$commission, $commission, $agent_id]
                );
            }
        }
        
        db_commit();
        
        // Send confirmation notification
        createNotification($user_id, $user['user_type'], 
            'Subscription Activated', 
            "Your $plan_type subscription is now active until $end_date"
        );
        
        logAudit($user_id, $user['user_type'], 'subscription_created', 
            "Subscription created: $plan_type - " . formatCurrency($amount));
        
        return [
            'success' => true,
            'message' => 'Subscription created successfully',
            'subscription_id' => $subscription_id,
            'end_date' => $end_date
        ];
        
    } catch(Exception $e) {
        db_rollback();
        return ['success' => false, 'message' => 'Subscription creation failed: ' . $e->getMessage()];
    }
}

/**
 * Get subscription duration in days
 */
function getSubscriptionDuration($plan_type) {
    $durations = [
        '1_day' => 1,
        '1_week' => 7,
        '1_month' => 30,
        'monthly' => 30,
        '3_month' => 90,
        '6_month' => 180,
        'annual' => 365
    ];
    
    return $durations[$plan_type] ?? 30;
}

/**
 * Get school capacity by plan
 */
function getSchoolCapacity($plan_type) {
    $capacities = [
        'monthly' => CAPACITY_SCHOOL_MONTHLY,
        '3_month' => CAPACITY_SCHOOL_3MONTH,
        '6_month' => CAPACITY_SCHOOL_6MONTH,
        'annual' => CAPACITY_SCHOOL_ANNUAL
    ];
    
    return $capacities[$plan_type] ?? 50;
}

/**
 * Calculate agent commission
 */
function calculateCommission($plan_type, $amount, $user_type) {
    $rate = db_select_one(
        "SELECT commission_percentage FROM commission_rates WHERE plan_type = ? AND user_type = ?",
        [$plan_type, $user_type]
    );
    
    if (!$rate) return 0;
    
    return ($amount * $rate['commission_percentage']) / 100;
}

/**
 * ==========================================================================
 * 3. AGENT COMMISSION FUNCTIONS
 * ==========================================================================
 */

/**
 * Get agent statistics
 */
function getAgentStats($agent_id) {
    // Total students referred
    $total_students = db_select_one(
        "SELECT COUNT(*) as count FROM users WHERE agent_id = ?",
        [$agent_id]
    );
    
    // Active subscriptions
    $active_subs = db_select_one(
        "SELECT COUNT(*) as count FROM users 
        WHERE agent_id = ? AND subscription_end >= CURDATE()",
        [$agent_id]
    );
    
    // This month registrations
    $this_month = db_select_one(
        "SELECT COUNT(*) as count FROM users 
        WHERE agent_id = ? AND MONTH(created_at) = MONTH(CURDATE())",
        [$agent_id]
    );
    
    // Get earnings info
    $agent = getUserById($agent_id);
    
    return [
        'total_students' => $total_students['count'],
        'active_subscriptions' => $active_subs['count'],
        'this_month_new' => $this_month['count'],
        'total_earnings' => $agent['total_earnings'],
        'pending_payout' => $agent['pending_payout']
    ];
}

/**
 * Request payout
 */
function requestPayout($agent_id, $amount, $method, $account_details) {
    $agent = getUserById($agent_id);
    
    if (!$agent) {
        return ['success' => false, 'message' => 'Agent not found'];
    }
    
    if ($amount < MIN_PAYOUT_AMOUNT) {
        return ['success' => false, 'message' => 'Minimum payout is ' . formatCurrency(MIN_PAYOUT_AMOUNT)];
    }
    
    if ($amount > $agent['pending_payout']) {
        return ['success' => false, 'message' => 'Insufficient balance'];
    }
    
    // Generate payout ID
    $payout_id = 'PAY-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Create payout request
    db_execute("INSERT INTO agent_payouts (
        agent_id, payout_id, amount, payout_method, account_details, status
    ) VALUES (?, ?, ?, ?, ?, 'pending')",
        [$agent_id, $payout_id, $amount, $method, $account_details]
    );
    
    createNotification($agent_id, 'agent', 
        'Payout Requested', 
        "Your payout request of " . formatCurrency($amount) . " is being processed"
    );
    
    logAudit($agent_id, 'agent', 'payout_requested', 
        "Payout requested: " . formatCurrency($amount));
    
    return [
        'success' => true,
        'message' => 'Payout request submitted successfully',
        'payout_id' => $payout_id
    ];
}

/**
 * ==========================================================================
 * 4. SCHOOL MANAGEMENT FUNCTIONS
 * ==========================================================================
 */

/**
 * Create batch
 */
function createBatch($school_id, $data) {
    $query = "INSERT INTO batches (
        school_id, batch_name, batch_label, start_date,
        max_students, instructor, description
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    db_execute($query, [
        $school_id,
        $data['batch_name'],
        $data['batch_label'] ?? '',
        $data['start_date'],
        $data['max_students'] ?? 30,
        $data['instructor'] ?? '',
        $data['description'] ?? ''
    ]);
    
    $batch_id = db_last_insert_id();
    
    logAudit($school_id, 'school', 'batch_created', 
        "Batch created: " . $data['batch_name']);
    
    return ['success' => true, 'batch_id' => $batch_id];
}

/**
 * Get school statistics
 */
function getSchoolStats($school_id) {
    // Total students
    $total = db_select_one(
        "SELECT COUNT(*) as count FROM school_students WHERE school_id = ?",
        [$school_id]
    );
    
    // Active students
    $active = db_select_one(
        "SELECT COUNT(*) as count FROM school_students 
        WHERE school_id = ? AND status = 'active'",
        [$school_id]
    );
    
    // Completed students
    $completed = db_select_one(
        "SELECT COUNT(*) as count FROM school_students 
        WHERE school_id = ? AND status = 'completed'",
        [$school_id]
    );
    
    // Total tests taken by school students
    $tests = db_select_one(
        "SELECT COUNT(*) as count FROM test_attempts ta
        JOIN school_students ss ON ta.user_id = ss.student_id
        WHERE ss.school_id = ?",
        [$school_id]
    );
    
    // Pass rate
    $pass_rate = db_select_one(
        "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed
        FROM test_attempts ta
        JOIN school_students ss ON ta.user_id = ss.student_id
        WHERE ss.school_id = ?",
        [$school_id]
    );
    
    $pass_percentage = 0;
    if ($pass_rate && $pass_rate['total'] > 0) {
        $pass_percentage = ($pass_rate['passed'] / $pass_rate['total']) * 100;
    }
    
    return [
        'total_students' => $total['count'],
        'active_students' => $active['count'],
        'completed_students' => $completed['count'],
        'tests_taken' => $tests['count'],
        'pass_rate' => round($pass_percentage, 2)
    ];
}

/**
 * ==========================================================================
 * 5. TEST & COURSE FUNCTIONS
 * ==========================================================================
 */

/**
 * Get available tests for user
 */
function getAvailableTests($user_id) {
    $has_subscription = hasActiveSubscription($user_id);
    
    if ($has_subscription) {
        // All tests available
        return db_select("SELECT * FROM tests WHERE is_active = 1 ORDER BY display_order");
    } else {
        // Only free tests
        return db_select("SELECT * FROM tests WHERE is_active = 1 AND is_free = 1 ORDER BY display_order");
    }
}

/**
 * Record test attempt
 */
function recordTestAttempt($user_id, $test_id, $answers, $time_taken) {
    // Get test questions
    $questions = db_select(
        "SELECT tq.question_id, q.correct_answer 
        FROM test_questions tq
        JOIN questions q ON tq.question_id = q.id
        WHERE tq.test_id = ?
        ORDER BY tq.question_order",
        [$test_id]
    );
    
    // Calculate score
    $total = count($questions);
    $correct = 0;
    
    foreach ($questions as $q) {
        if (isset($answers[$q['question_id']]) && 
            $answers[$q['question_id']] === $q['correct_answer']) {
            $correct++;
        }
    }
    
    $percentage = ($correct / $total) * 100;
    
    // Check if passed
    $test = db_select_one("SELECT passing_score FROM tests WHERE id = ?", [$test_id]);
    $passed = $correct >= $test['passing_score'];
    
    // Save attempt
    db_execute("INSERT INTO test_attempts (
        user_id, test_id, score, total_questions, percentage,
        passed, time_taken_seconds, answers_json, completed_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [$user_id, $test_id, $correct, $total, $percentage, $passed, $time_taken, json_encode($answers)]
    );
    
    $attempt_id = db_last_insert_id();
    
    return [
        'success' => true,
        'attempt_id' => $attempt_id,
        'score' => $correct,
        'total' => $total,
        'percentage' => round($percentage, 2),
        'passed' => $passed
    ];
}

/**
 * ==========================================================================
 * 6. NOTIFICATION FUNCTIONS
 * ==========================================================================
 */

/**
 * Create notification
 */
function createNotification($user_id, $user_type, $title, $message, $type = 'info', $action_url = null) {
    db_execute("INSERT INTO notifications (
        user_id, user_type, title, message, type, action_url
    ) VALUES (?, ?, ?, ?, ?, ?)",
        [$user_id, $user_type, $title, $message, $type, $action_url]
    );
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount($user_id) {
    $result = db_select_one(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
        [$user_id]
    );
    return $result['count'];
}

/**
 * ==========================================================================
 * 7. FILE UPLOAD FUNCTIONS
 * ==========================================================================
 */

/**
 * Upload file
 */
function uploadFile($file, $directory, $allowed_types = null) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($allowed_types && !in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

/**
 * ==========================================================================
 * 8. UTILITY FUNCTIONS
 * ==========================================================================
 */

/**
 * Log audit trail
 */
function logAudit($user_id, $user_type, $action, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    db_execute("INSERT INTO audit_log (
        user_id, user_type, action, description, ip_address, user_agent
    ) VALUES (?, ?, ?, ?, ?, ?)",
        [$user_id, $user_type, $action, $description, $ip, $user_agent]
    );
}

/**
 * Send email (placeholder - implement with actual email service)
 */
function sendEmail($to, $subject, $body) {
    // TODO: Implement with PHPMailer or similar
    return true;
}

/**
 * Send SMS (placeholder - implement with actual SMS service)
 */
function sendSMS($phone, $message) {
    // TODO: Implement with SMS gateway
    return true;
}

?>