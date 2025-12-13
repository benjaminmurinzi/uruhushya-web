# 🚗 URUHUSHYA - Multi-User Driving License Platform

## 📋 PROJECT OVERVIEW

URUHUSHYA is a comprehensive driving license preparation platform built for Rwanda. It supports **4 different user types**:

1. **Students** - Learn and practice for driving tests
2. **Driving Schools** - Manage multiple students and track progress
3. **Agents** - Register students and earn commissions
4. **Administrators** - Full system control

---

## 🎯 WHAT WE'VE BUILT SO FAR

### ✅ Phase 1: Foundation (COMPLETED)

We've successfully created the following foundational files:

#### 1. **Database Schema** (`database/db_uruhushya.sql`)
- 21 tables for complete multi-user system
- Support for students, schools, agents, and admins
- Subscription management system
- Agent commission tracking
- Test and course management
- Audit logging and notifications

#### 2. **Configuration** (`config.php`)
- Database connection settings
- Site-wide constants (prices, limits, etc.)
- File upload configuration
- Security settings
- Email/SMS configuration
- Payment gateway settings
- Helper functions for common tasks

#### 3. **Database Connection** (`includes/db-connect.php`)
- PDO connection (secure, modern PHP database method)
- Helper functions for queries (select, insert, update, delete)
- Error handling
- Transaction support

#### 4. **Core Functions** (`includes/functions.php`)
- User registration (students, schools, agents)
- Login/logout system
- Subscription management
- Agent commission calculations
- School management functions
- Test recording
- Notification system
- File upload handlers

#### 5. **Landing Page** (`index.php`)
- Beautiful, modern design
- Responsive layout
- Multi-user login options
- Features showcase
- Pricing section
- Call-to-action sections

#### 6. **Landing Page Styles** (`assets/css/landing.css`)
- Modern gradient design
- Smooth animations
- Mobile-responsive
- Professional look

---

## 🚀 HOW TO SET UP THE PLATFORM

### Step 1: Install Required Software

You need these programs installed on your computer:

1. **XAMPP** (for Windows/Mac) or **WAMP** (for Windows)
   - Download from: https://www.apachefriends.org/
   - This includes:
     - Apache (web server)
     - MySQL (database)
     - PHP (programming language)

2. **A Code Editor** (choose one):
   - VS Code (recommended): https://code.visualstudio.com/
   - Sublime Text: https://www.sublimetext.com/
   - Notepad++: https://notepad-plus-plus.org/

### Step 2: Set Up the Database

1. **Start XAMPP**:
   - Open XAMPP Control Panel
   - Click "Start" for Apache
   - Click "Start" for MySQL

2. **Create the Database**:
   - Open your browser
   - Go to: `http://localhost/phpmyadmin`
   - Click "New" in the left sidebar
   - Database name: `db_uruhushya`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

3. **Import the Database Schema**:
   - Select your newly created `db_uruhushya` database
   - Click "Import" tab at the top
   - Click "Choose File"
   - Select the file: `database/db_uruhushya.sql`
   - Click "Go" at the bottom
   - ✅ You should see "Import has been successfully finished"

### Step 3: Place Files in the Right Location

1. **Find your htdocs folder**:
   - For XAMPP: `C:\xampp\htdocs\`
   - For WAMP: `C:\wamp\www\`

2. **Copy the project**:
   - Copy the entire `uruhushya` folder
   - Paste it into `htdocs` (or `www`)
   - Your path should look like: `C:\xampp\htdocs\uruhushya\`

### Step 4: Configure the Database Connection

1. **Open `config.php`** in your code editor

2. **Check these settings** (lines 18-21):
   ```php
   define('DB_HOST', 'localhost');     // Usually 'localhost'
   define('DB_USER', 'root');          // Default XAMPP username
   define('DB_PASS', '');              // Default XAMPP password (empty)
   define('DB_NAME', 'db_uruhushya'); // Your database name
   ```

3. **If using WAMP or different setup**, you might need to change:
   - `DB_USER` to your MySQL username
   - `DB_PASS` to your MySQL password

### Step 5: Access the Platform

1. **Open your browser**

2. **Go to**: `http://localhost/uruhushya/`

3. **You should see the beautiful landing page!** 🎉

---

## 📁 FILE STRUCTURE EXPLAINED

Let me explain what each file/folder does:

```
uruhushya/
│
├── index.php                    # Landing page (what visitors see first)
├── config.php                   # Main configuration (database, settings)
│
├── database/
│   └── db_uruhushya.sql        # Database schema (tables structure)
│
├── includes/
│   ├── db-connect.php          # Database connection
│   └── functions.php           # Reusable PHP functions
│
├── assets/
│   ├── css/
│   │   └── landing.css         # Landing page styles
│   ├── js/
│   │   └── main.js             # JavaScript (to be created)
│   └── images/                 # Images folder
│
├── auth/                        # Authentication (login/register)
│   ├── student/                # Student login/register
│   ├── school/                 # School login/register
│   └── agent/                  # Agent login/register
│
├── student/                     # Student portal (dashboard, tests, courses)
├── school/                      # School portal (manage students)
├── agent/                       # Agent portal (referrals, earnings)
└── admin/                       # Admin panel (control everything)
```

---

## 🔐 HOW THE MULTI-USER SYSTEM WORKS

### Understanding User Types

This platform has **4 completely separate user areas**:

#### 1. **STUDENT**
- **Who**: Individual learners
- **Can**:
  - Register themselves
  - Subscribe to plans (1-day, 1-week, 1-month)
  - Take tests and courses
  - Track their progress
  - Get certificates
- **Dashboard**: `/student/dashboard.php`
- **Database**: Stored in `users` table with `user_type = 'student'`

#### 2. **DRIVING SCHOOL**
- **Who**: Driving schools/institutions
- **Can**:
  - Register multiple students
  - Organize students into batches/classes
  - Track all students' performance
  - View reports and analytics
  - Manage subscriptions
- **Dashboard**: `/school/dashboard.php`
- **Database**: Stored in `users` table with `user_type = 'school'`

#### 3. **AGENT**
- **Who**: Independent referral partners
- **Can**:
  - Register new students
  - Earn commission on each subscription
  - Track earnings
  - Request payouts
  - Refer other agents
- **Dashboard**: `/agent/dashboard.php`
- **Database**: Stored in `users` table with `user_type = 'agent'`

#### 4. **ADMINISTRATOR**
- **Who**: System managers
- **Can**:
  - Approve/reject schools and agents
  - Manage all content (tests, questions, courses)
  - View all analytics
  - Process payouts
  - Configure system settings
- **Dashboard**: `/admin/index.php`
- **Database**: Separate table `admin_users`

### How They Connect

```
ADMIN
  │
  ├─── Approves ──→ SCHOOLS
  │                    │
  │                    └─── Registers ──→ STUDENTS
  │
  ├─── Approves ──→ AGENTS
  │                    │
  │                    └─── Registers ──→ STUDENTS
  │
  └─── Manages ────→ CONTENT (Tests, Courses)
                        │
                        └─── Used by ──→ STUDENTS
```

---

## 💾 HOW THE DATABASE WORKS

### Main Tables Explained

#### 1. **users** (Most Important Table)
Stores ALL users (students, schools, agents) in ONE table.

**How it works**:
- `user_type` field determines what type of user:
  - `'student'` = Student
  - `'school'` = Driving School
  - `'agent'` = Agent

- Each user type has specific fields:
  ```sql
  Student fields: date_of_birth, gender, school_id, agent_id
  School fields: school_name, tin_number, director_name, etc.
  Agent fields: agent_code, bank_account, commission_rate, etc.
  ```

#### 2. **subscriptions**
Tracks who paid for what:
```sql
user_id        → Who subscribed
plan_type      → '1_day', '1_week', '1_month', etc.
start_date     → When it starts
end_date       → When it expires
agent_id       → If sold by agent (for commission)
```

#### 3. **agent_referrals**
Tracks agent commissions:
```sql
agent_id              → Which agent
referred_user_id      → Student they registered
commission_amount     → Money they earned
commission_status     → 'pending' or 'paid'
```

#### 4. **school_students**
Links students to schools:
```sql
school_id    → Which school
student_id   → Which student
batch_id     → Which class/batch
```

#### 5. **tests** & **questions**
Stores all driving tests and questions:
```sql
tests:
  - test_code: 'K018', 'K019', etc.
  - is_free: TRUE/FALSE (free or premium)
  
questions:
  - question_text: The question
  - correct_answer: A, B, C, or D
  - explanation: Why answer is correct
```

#### 6. **test_attempts**
Records every test taken:
```sql
user_id           → Who took it
test_id           → Which test
score             → How many correct
percentage        → Score percentage
passed            → TRUE/FALSE
answers_json      → All their answers
```

---

## 🎨 DESIGN PHILOSOPHY

### Why This Design?

1. **Modern & Professional**
   - Gradient colors (blue to purple)
   - Clean typography (Plus Jakarta Sans font)
   - Smooth animations
   - Card-based layouts

2. **Mobile-First**
   - Works perfectly on phones
   - Responsive design
   - Touch-friendly buttons

3. **User-Friendly**
   - Clear navigation
   - Obvious call-to-actions
   - Easy-to-understand layouts

### Color System

```css
Primary:    #4f46e5 (Indigo)
Secondary:  #7c3aed (Purple)
Accent:     #f59e0b (Amber)
Success:    #10b981 (Green)
Error:      #ef4444 (Red)
```

---

## 🔐 SECURITY FEATURES

1. **Password Hashing**
   ```php
   password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
   ```
   - Passwords are never stored in plain text
   - Uses bcrypt algorithm

2. **SQL Injection Prevention**
   ```php
   // PDO prepared statements
   db_execute("SELECT * FROM users WHERE email = ?", [$email]);
   ```
   - All queries use prepared statements
   - User input is escaped

3. **Session Security**
   - Session timeout (2 hours)
   - User type verification
   - Login attempt limiting

4. **Input Sanitization**
   ```php
   sanitize($user_input); // Removes malicious code
   ```

---

## 💰 SUBSCRIPTION SYSTEM

### Student Plans

| Plan      | Price      | Duration | Access         |
|-----------|-----------|----------|----------------|
| 1-Day     | 1,000 RWF | 24 hours | All content    |
| 1-Week    | 4,900 RWF | 7 days   | All + support  |
| 1-Month   | 9,900 RWF | 30 days  | All + priority |

### School Plans

| Plan      | Price        | Students | Duration |
|-----------|-------------|----------|----------|
| Monthly   | 50,000 RWF  | 50       | 30 days  |
| 3-Month   | 135,000 RWF | 100      | 90 days  |
| 6-Month   | 240,000 RWF | 150      | 180 days |
| Annual    | 420,000 RWF | Unlimited| 365 days |

### How It Works

1. **User Subscribes**:
   ```php
   createSubscription($user_id, '1_month', 9900, $agent_id);
   ```

2. **System Updates**:
   - Adds record to `subscriptions` table
   - Updates `users` table with subscription dates
   - Calculates agent commission (if applicable)
   - Sends confirmation notification

3. **Access Control**:
   ```php
   if (hasActiveSubscription($user_id)) {
       // Allow access to premium content
   } else {
       // Show only free content
   }
   ```

---

## 📊 AGENT COMMISSION SYSTEM

### How Agents Earn Money

1. **Register a Student**:
   - Agent registers student with subscription
   - Commission calculated automatically

2. **Commission Rates**:
   ```php
   Student Plans:
   - 1-Day:   10% = 100 RWF
   - 1-Week:  15% = 735 RWF
   - 1-Month: 20% = 1,980 RWF
   
   School Plans:
   - Monthly:  5% = 2,500 RWF
   - 3-Month:  7% = 9,450 RWF
   - 6-Month: 10% = 24,000 RWF
   - Annual:  12% = 50,400 RWF
   ```

3. **Bonus System**:
   - 10+ sales per month = +5% bonus
   - Example: Normal 20% + 5% bonus = 25% total

4. **Payouts**:
   - Agent requests payout (minimum 20,000 RWF)
   - Admin approves
   - Money sent to agent's bank/mobile money

---

## 🔄 WHAT HAPPENS NEXT?

### Phase 2: Student Portal (Next Steps)

We need to build:

1. **Student Registration** (`auth/student/register.php`)
   - Registration form
   - Email verification
   - Password strength validation

2. **Student Login** (`auth/student/login.php`)
   - Login form
   - Remember me option
   - Forgot password link

3. **Student Dashboard** (`student/dashboard.php`)
   - Welcome message
   - Subscription status
   - Statistics (tests taken, scores, etc.)
   - Quick actions
   - Recent activity

4. **Tests Page** (`student/tests.php`)
   - List all available tests
   - Show FREE vs PREMIUM
   - Filter/search tests

5. **Take Test** (`student/take-test.php`)
   - Display questions
   - Timer countdown
   - Submit answers
   - Show results

6. **Courses** (`student/courses.php`)
   - List all courses
   - Track progress
   - Video lessons
   - PDF materials

7. **Subscription Page** (`student/subscription.php`)
   - Show plans
   - Payment integration
   - Upgrade/renew options

---

## 🛠️ TROUBLESHOOTING

### Common Issues

#### 1. "Database connection failed"
**Solution**:
- Check if MySQL is running in XAMPP
- Verify database name in `config.php`
- Make sure database `db_uruhushya` exists

#### 2. "404 Not Found"
**Solution**:
- Check if files are in `htdocs/uruhushya/`
- Verify URL: `http://localhost/uruhushya/`
- Check Apache is running in XAMPP

#### 3. "Blank white page"
**Solution**:
- Enable error reporting in `config.php`:
  ```php
  define('DEBUG_MODE', true);
  ```
- Check browser console for errors
- Check Apache error logs

#### 4. "CSS not loading"
**Solution**:
- Verify `SITE_URL` in `config.php`:
  ```php
  define('SITE_URL', 'http://localhost/uruhushya');
  ```
- Clear browser cache (Ctrl + F5)

---

## 📚 LEARNING RESOURCES

### Understanding PHP & MySQL

1. **PHP Tutorial**: https://www.w3schools.com/php/
2. **MySQL Tutorial**: https://www.w3schools.com/mysql/
3. **PDO Tutorial**: https://phpdelusions.net/pdo

### Understanding Web Development

1. **HTML**: https://www.w3schools.com/html/
2. **CSS**: https://www.w3schools.com/css/
3. **JavaScript**: https://www.w3schools.com/js/

---

## 🎓 KEY CONCEPTS EXPLAINED

### 1. **Session Management**

```php
// Starting a session
session_start();

// Saving data in session
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'student';

// Reading from session
$user_id = $_SESSION['user_id'];

// Checking if logged in
if (isset($_SESSION['user_id'])) {
    // User is logged in
}

// Destroying session (logout)
session_destroy();
```

### 2. **PDO Prepared Statements**

```php
// BAD (vulnerable to SQL injection):
$query = "SELECT * FROM users WHERE email = '$email'";

// GOOD (safe):
$query = "SELECT * FROM users WHERE email = ?";
$result = db_select_one($query, [$email]);
```

### 3. **Include Files**

```php
// Include a file
require_once 'config.php';

// Why use require_once?
// - 'require' = file is required (error if missing)
// - 'once' = include only once (prevent duplicates)
```

### 4. **Constants vs Variables**

```php
// Constant (never changes)
define('SITE_NAME', 'URUHUSHYA');
echo SITE_NAME; // No $ sign

// Variable (can change)
$user_name = 'John';
$user_name = 'Jane'; // Changed!
```

---

## 📞 SUPPORT & HELP

If you need help:

1. **Check this README** - Most answers are here
2. **Review the code comments** - Every file has explanations
3. **Google the error** - Most PHP errors have solutions online
4. **Check XAMPP logs** - Look for error messages

---

## ✅ SYSTEM REQUIREMENTS

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher
- **Browser**: Chrome, Firefox, Safari, or Edge (latest versions)

---

## 🎯 DEFAULT CREDENTIALS

### Admin Login
- **URL**: `http://localhost/uruhushya/admin/login.php`
- **Username**: `admin`
- **Password**: `Admin@123`

⚠️ **IMPORTANT**: Change this password in production!

---

## 🚀 DEPLOYMENT CHECKLIST (For Production)

When ready to deploy to a live server:

- [ ] Change `DEBUG_MODE` to `false` in `config.php`
- [ ] Update `SITE_URL` to your domain
- [ ] Change default admin password
- [ ] Configure email/SMS settings
- [ ] Set up payment gateways
- [ ] Enable SSL (HTTPS)
- [ ] Set proper file permissions
- [ ] Configure backup system

---

## 📄 LICENSE

This project is for educational purposes. Customize as needed for your requirements.

---

## 🎉 CONGRATULATIONS!

You've successfully set up the foundation of URUHUSHYA! 

**What we have**:
✅ Complete database structure
✅ Configuration system
✅ Beautiful landing page
✅ User management functions
✅ Subscription system
✅ Commission tracking

**Next phase**: Build the student portal with registration, login, and dashboard!

---

**Built with ❤️ in Rwanda 🇷🇼**