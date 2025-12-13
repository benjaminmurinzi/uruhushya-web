-- =====================================================
-- URUHUSHYA DATABASE SCHEMA
-- Multi-User Driving License Platform
-- =====================================================
-- This database supports 4 user types:
-- 1. Students (learning to drive)
-- 2. Schools (managing multiple students)
-- 3. Agents (referring students for commission)
-- 4. Admins (system administrators)
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS db_uruhushya;
USE db_uruhushya;

-- =====================================================
-- TABLE 1: users
-- This is the MAIN table that stores ALL users
-- (students, schools, and agents)
-- The 'user_type' field determines what type of user it is
-- =====================================================
CREATE TABLE users (
    -- Primary key - unique ID for each user
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- User type determines access and features
    user_type ENUM('student', 'school', 'agent') DEFAULT 'student',
    
    -- ===== COMMON FIELDS (All users have these) =====
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Will be hashed with password_hash()
    phone VARCHAR(20) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    
    -- ===== STUDENT-SPECIFIC FIELDS =====
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    id_number VARCHAR(50) NULL, -- National ID or passport
    
    -- Track WHO registered this student
    registered_by ENUM('self', 'school', 'agent') DEFAULT 'self',
    registered_by_id INT NULL, -- ID of the school or agent who registered them
    
    -- If student is enrolled in a school
    school_id INT NULL, -- References users table (where user_type='school')
    
    -- If student was registered by an agent
    agent_id INT NULL, -- References users table (where user_type='agent')
    
    -- If student is in a batch/class
    batch_id INT NULL,
    
    -- ===== SCHOOL-SPECIFIC FIELDS =====
    school_name VARCHAR(255) NULL,
    school_type VARCHAR(100) NULL, -- 'Driving School', 'Training Center', etc.
    tin_number VARCHAR(50) NULL, -- Tax Identification Number
    license_number VARCHAR(50) NULL, -- Official school license
    school_address TEXT NULL,
    district VARCHAR(100) NULL,
    sector VARCHAR(100) NULL,
    director_name VARCHAR(255) NULL,
    director_phone VARCHAR(20) NULL,
    school_logo VARCHAR(255) NULL, -- Path to uploaded logo
    
    -- School approval status (must be approved by admin)
    school_status ENUM('pending', 'approved', 'suspended', 'rejected') NULL,
    
    -- ===== AGENT-SPECIFIC FIELDS =====
    agent_code VARCHAR(50) NULL UNIQUE, -- Unique code like 'AGT-2024-001'
    
    -- Payment details for agent payouts
    bank_name VARCHAR(100) NULL,
    bank_account_number VARCHAR(50) NULL,
    bank_account_name VARCHAR(255) NULL,
    mobile_money_number VARCHAR(20) NULL,
    mobile_money_provider ENUM('mtn', 'airtel') NULL,
    
    -- Agent referral system (agents can refer other agents)
    referred_by_agent_id INT NULL,
    
    -- Agent earnings tracking
    total_earnings DECIMAL(10,2) DEFAULT 0.00, -- Total lifetime earnings
    pending_payout DECIMAL(10,2) DEFAULT 0.00, -- Available for withdrawal
    
    -- Agent approval status
    agent_status ENUM('pending', 'approved', 'suspended', 'rejected') NULL,
    
    -- ===== SUBSCRIPTION FIELDS =====
    subscription_type VARCHAR(50) DEFAULT 'free',
    subscription_start DATE NULL,
    subscription_end DATE NULL,
    subscription_auto_renew BOOLEAN DEFAULT FALSE,
    
    -- For schools: maximum students they can register
    max_students INT NULL,
    
    -- ===== STATUS & VERIFICATION =====
    email_verified BOOLEAN DEFAULT FALSE,
    account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    
    -- ===== OAUTH (for Google Sign-In) =====
    google_id VARCHAR(255) NULL,
    
    -- ===== TIMESTAMPS =====
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- ===== INDEXES for faster queries =====
    INDEX idx_user_type (user_type),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_school (school_id),
    INDEX idx_agent (agent_id),
    INDEX idx_agent_code (agent_code),
    INDEX idx_subscription_end (subscription_end)
);

-- =====================================================
-- TABLE 2: batches
-- Schools organize students into batches/classes
-- =====================================================
CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL, -- Which school owns this batch
    batch_name VARCHAR(100) NOT NULL, -- e.g., "B-01"
    batch_label VARCHAR(255), -- e.g., "Morning Class - December 2024"
    start_date DATE NOT NULL,
    max_students INT DEFAULT 30, -- Maximum capacity
    current_students INT DEFAULT 0, -- Current enrollment count
    instructor VARCHAR(255), -- Teacher/instructor name
    description TEXT,
    status ENUM('active', 'completed', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key: batch belongs to a school
    FOREIGN KEY (school_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_school_batch (school_id, status)
);

-- =====================================================
-- TABLE 3: agent_referrals
-- Tracks all referrals made by agents
-- (both student referrals and agent-to-agent referrals)
-- =====================================================
CREATE TABLE agent_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL, -- Agent who made the referral
    referred_user_id INT NOT NULL, -- The user who was referred
    referred_type ENUM('student', 'agent') NOT NULL, -- What type of user
    subscription_id INT NULL, -- If it's a student, which subscription
    
    -- Commission details
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    commission_percentage DECIMAL(5,2), -- e.g., 20.00 for 20%
    commission_status ENUM('pending', 'paid') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_agent_referrals (agent_id, commission_status)
);

-- =====================================================
-- TABLE 4: agent_payouts
-- Tracks payout requests and payments to agents
-- =====================================================
CREATE TABLE agent_payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    payout_id VARCHAR(50) NOT NULL UNIQUE, -- e.g., "PAY-2024-001"
    amount DECIMAL(10,2) NOT NULL,
    payout_method ENUM('bank', 'mtn_momo', 'airtel_money') NOT NULL,
    account_details VARCHAR(255), -- Account number or phone number
    
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL, -- Admin who processed it
    notes TEXT, -- Admin can add notes
    
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_agent_payouts (agent_id, status)
);

-- =====================================================
-- TABLE 5: subscriptions
-- Tracks all subscription purchases
-- (Both student and school subscriptions)
-- =====================================================
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Who subscribed
    user_type ENUM('student', 'school') NOT NULL,
    
    -- Plan types:
    -- Students: '1_day', '1_week', '1_month'
    -- Schools: 'monthly', '3_month', '6_month', 'annual'
    plan_type VARCHAR(50) NOT NULL,
    
    amount DECIMAL(10,2) NOT NULL, -- Price paid
    currency VARCHAR(3) DEFAULT 'RWF',
    
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    
    -- Payment details
    payment_method VARCHAR(50), -- 'mtn_momo', 'airtel_money', 'card'
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255), -- From payment gateway
    
    auto_renew BOOLEAN DEFAULT FALSE,
    
    -- For school subscriptions
    student_capacity INT NULL, -- How many students this plan allows
    
    -- Agent commission (if sold by agent)
    agent_id INT NULL,
    commission_amount DECIMAL(10,2) NULL,
    commission_paid BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_subscription (user_id, end_date DESC),
    INDEX idx_agent_sales (agent_id, created_at DESC)
);

-- =====================================================
-- TABLE 6: school_students
-- Links students to schools (many-to-many relationship)
-- A student can be enrolled in one school at a time
-- =====================================================
CREATE TABLE school_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    student_id INT NOT NULL,
    batch_id INT NULL, -- Which batch/class
    enrolled_date DATE NOT NULL,
    status ENUM('active', 'completed', 'withdrawn') DEFAULT 'active',
    notes TEXT,
    
    FOREIGN KEY (school_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    
    -- Ensure a student can only be in one school at a time
    UNIQUE KEY unique_school_student (school_id, student_id),
    INDEX idx_school_students (school_id, status)
);

-- =====================================================
-- TABLE 7: admin_users
-- Separate table for admin users (higher security)
-- =====================================================
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    
    -- Admin roles with different permissions
    role ENUM('super_admin', 'admin', 'moderator', 'support') DEFAULT 'admin',
    permissions JSON, -- Store specific permissions as JSON
    
    status ENUM('active', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL, -- Which admin created this account
    
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- =====================================================
-- TABLE 8: commission_rates
-- Stores commission percentages for different plans
-- Admin can update these rates
-- =====================================================
CREATE TABLE commission_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_type VARCHAR(50) NOT NULL, -- '1_day', '1_week', etc.
    user_type ENUM('student', 'school') NOT NULL,
    commission_percentage DECIMAL(5,2) NOT NULL, -- e.g., 20.00 for 20%
    
    -- Bonus system
    min_sales_for_bonus INT DEFAULT 0, -- e.g., 10 sales needed
    bonus_percentage DECIMAL(5,2) DEFAULT 0.00, -- Additional bonus percentage
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_plan (plan_type, user_type)
);

-- Insert default commission rates
INSERT INTO commission_rates (plan_type, user_type, commission_percentage, min_sales_for_bonus, bonus_percentage) VALUES
-- Student plans
('1_day', 'student', 10.00, 10, 5.00),
('1_week', 'student', 15.00, 10, 5.00),
('1_month', 'student', 20.00, 10, 5.00),
-- School plans
('monthly', 'school', 5.00, 5, 3.00),
('3_month', 'school', 7.00, 5, 3.00),
('6_month', 'school', 10.00, 5, 3.00),
('annual', 'school', 12.00, 5, 3.00);

-- =====================================================
-- TABLE 9: tests
-- Available driving tests (like K018, K019, K020, etc.)
-- =====================================================
CREATE TABLE tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_code VARCHAR(20) NOT NULL UNIQUE, -- e.g., "K018", "K019"
    test_name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Test settings
    total_questions INT DEFAULT 20,
    time_limit_minutes INT DEFAULT 30,
    passing_score INT DEFAULT 15, -- Need 15/20 to pass
    
    -- Access control
    is_free BOOLEAN DEFAULT FALSE, -- True = available to all, False = requires subscription
    
    -- Categorization
    category VARCHAR(100), -- 'General', 'Advanced', 'Motorcycle', etc.
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0, -- For sorting tests
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_test_code (test_code),
    INDEX idx_is_free (is_free),
    INDEX idx_is_active (is_active)
);

-- =====================================================
-- TABLE 10: questions
-- Question bank for all tests
-- =====================================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Question content
    question_text TEXT NOT NULL,
    question_text_rw TEXT, -- Kinyarwanda translation
    question_image VARCHAR(255), -- Optional image path
    
    -- Answer options (all stored in one table)
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT,
    
    -- Kinyarwanda options
    option_a_rw TEXT,
    option_b_rw TEXT,
    option_c_rw TEXT,
    option_d_rw TEXT,
    
    -- Correct answer
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    
    -- Explanation (why this answer is correct)
    explanation TEXT,
    explanation_rw TEXT,
    
    -- Categorization
    category VARCHAR(100), -- 'Traffic Signs', 'Road Rules', etc.
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    
    -- Usage tracking
    times_used INT DEFAULT 0,
    times_correct INT DEFAULT 0,
    times_wrong INT DEFAULT 0,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_difficulty (difficulty),
    INDEX idx_is_active (is_active)
);

-- =====================================================
-- TABLE 11: test_questions
-- Links questions to tests (many-to-many)
-- =====================================================
CREATE TABLE test_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    question_id INT NOT NULL,
    question_order INT DEFAULT 0, -- Order in test
    
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_test_question (test_id, question_id),
    INDEX idx_test_questions (test_id, question_order)
);

-- =====================================================
-- TABLE 12: test_attempts
-- Records every time a student takes a test
-- =====================================================
CREATE TABLE test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    
    -- Results
    score INT NOT NULL, -- Number of correct answers
    total_questions INT NOT NULL,
    percentage DECIMAL(5,2), -- Score percentage
    passed BOOLEAN, -- Did they pass?
    
    -- Timing
    time_taken_seconds INT, -- How long it took
    
    -- Test details (snapshot at time of attempt)
    answers_json JSON, -- Store all answers: {"1": "A", "2": "B", ...}
    
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    
    INDEX idx_user_attempts (user_id, completed_at DESC),
    INDEX idx_test_attempts (test_id)
);

-- =====================================================
-- TABLE 13: courses
-- Learning courses (theory, lessons)
-- =====================================================
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    title_rw VARCHAR(255),
    description TEXT,
    description_rw TEXT,
    
    -- Course image/thumbnail
    thumbnail VARCHAR(255),
    
    -- Access control
    is_free BOOLEAN DEFAULT FALSE,
    
    -- Organization
    category VARCHAR(100),
    display_order INT DEFAULT 0,
    
    -- Estimated duration
    duration_hours DECIMAL(4,2),
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course_code (course_code),
    INDEX idx_is_free (is_free)
);

-- =====================================================
-- TABLE 14: lessons
-- Individual lessons within courses
-- =====================================================
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    title_rw VARCHAR(255),
    
    -- Content can be text, video, or PDF
    content_type ENUM('text', 'video', 'pdf', 'mixed') DEFAULT 'text',
    content_text TEXT,
    content_text_rw TEXT,
    video_url VARCHAR(255), -- YouTube embed or video path
    pdf_path VARCHAR(255),
    
    -- Organization
    lesson_order INT DEFAULT 0,
    
    -- Estimated duration
    duration_minutes INT,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_lessons (course_id, lesson_order)
);

-- =====================================================
-- TABLE 15: user_course_progress
-- Tracks student progress in courses
-- =====================================================
CREATE TABLE user_course_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    lesson_id INT NULL, -- Current/last lesson
    
    -- Progress
    lessons_completed INT DEFAULT 0,
    total_lessons INT,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Status
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_course (user_id, course_id),
    INDEX idx_user_progress (user_id, status)
);

-- =====================================================
-- TABLE 16: certificates
-- Certificates earned by students
-- =====================================================
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(50) NOT NULL UNIQUE, -- e.g., "CERT-2024-0001"
    user_id INT NOT NULL,
    
    -- Certificate details
    issued_date DATE NOT NULL,
    valid_until DATE NULL, -- Some certificates expire
    
    -- Requirements met
    tests_passed INT,
    courses_completed INT,
    average_score DECIMAL(5,2),
    
    -- Certificate file
    certificate_path VARCHAR(255), -- PDF path
    
    status ENUM('valid', 'revoked', 'expired') DEFAULT 'valid',
    revoked_reason TEXT,
    revoked_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_certificates (user_id),
    INDEX idx_certificate_number (certificate_number)
);

-- =====================================================
-- TABLE 17: password_resets
-- Temporary tokens for password reset
-- =====================================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email_token (email, token),
    INDEX idx_expires (expires_at)
);

-- =====================================================
-- TABLE 18: email_verifications
-- Email verification tokens
-- =====================================================
CREATE TABLE email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
);

-- =====================================================
-- TABLE 19: notifications
-- System notifications for users
-- =====================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student', 'school', 'agent', 'admin') NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50), -- 'subscription', 'test_result', 'payment', etc.
    
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    
    -- Optional link/action
    action_url VARCHAR(255),
    action_text VARCHAR(100),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read, created_at DESC)
);

-- =====================================================
-- TABLE 20: audit_log
-- Track important system actions for security
-- =====================================================
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_type VARCHAR(20),
    action VARCHAR(100) NOT NULL, -- 'login', 'subscription_purchase', 'student_registered', etc.
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_audit (user_id, created_at DESC),
    INDEX idx_action (action, created_at DESC)
);

-- =====================================================
-- TABLE 21: site_settings
-- System-wide configuration
-- =====================================================
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50), -- 'text', 'number', 'boolean', 'json'
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL, -- Admin who updated it
    
    INDEX idx_setting_key (setting_key)
);

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'URUHUSHYA', 'text', 'Website name'),
('site_email', 'info@uruhushya.com', 'text', 'Contact email'),
('site_phone', '+250788000000', 'text', 'Contact phone'),
('allow_self_registration', '1', 'boolean', 'Allow students to self-register'),
('require_email_verification', '0', 'boolean', 'Require email verification'),
('mtn_api_key', '', 'text', 'MTN MoMo API Key'),
('airtel_api_key', '', 'text', 'Airtel Money API Key'),
('min_payout_amount', '20000', 'number', 'Minimum payout amount for agents (RWF)'),
('commission_payout_day', '1', 'number', 'Day of month for commission payouts'),
('default_language', 'rw', 'text', 'Default language (en/rw)');

-- =====================================================
-- Create a default admin user
-- Password: Admin@123 (hashed with password_hash())
-- =====================================================
INSERT INTO admin_users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@uruhushya.com', 'super_admin');

-- =====================================================
-- Sample data for testing (OPTIONAL - can be removed)
-- =====================================================

-- Sample free test
INSERT INTO tests (test_code, test_name, description, total_questions, time_limit_minutes, is_free, is_active) VALUES
('K001', 'Free Demo Test', 'Demo test available to all users', 10, 15, TRUE, TRUE);

-- Sample premium test
INSERT INTO tests (test_code, test_name, description, total_questions, time_limit_minutes, is_free, is_active) VALUES
('K018', 'Complete Driving Theory Test', 'Full driving theory examination', 20, 30, FALSE, TRUE);

-- Sample free course
INSERT INTO courses (course_code, title, title_rw, description, is_free, is_active) VALUES
('C001', 'Introduction to Road Safety', 'Intangiriro ku Mutekano mu Muhanda', 'Basic road safety principles', TRUE, TRUE);

-- Sample premium course
INSERT INTO courses (course_code, title, title_rw, description, is_free, is_active) VALUES
('C002', 'Traffic Rules and Regulations', 'Amategeko y\'Umuhanda', 'Complete traffic rules course', FALSE, TRUE);

-- =====================================================
-- END OF DATABASE SCHEMA
-- =====================================================