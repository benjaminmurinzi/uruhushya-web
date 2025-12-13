<?php
/**
 * =====================================================
 * DATABASE CONNECTION FILE
 * =====================================================
 * This file creates a connection to the MySQL database
 * 
 * HOW IT WORKS:
 * 1. Loads configuration from config.php
 * 2. Creates a PDO connection (modern PHP database method)
 * 3. Sets error handling
 * 4. Makes connection available globally as $conn
 * 
 * WHY PDO?
 * - More secure (prevents SQL injection)
 * - Works with multiple databases
 * - Better error handling
 * - Supports prepared statements
 * =====================================================
 */

// Include configuration file
require_once __DIR__ . '/../config.php';

try {
    /**
     * Create PDO connection
     * 
     * PDO = PHP Data Objects
     * It's a database access layer providing a uniform method
     * of access to multiple databases.
     * 
     * Format: mysql:host=HOST;dbname=DATABASE
     */
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            /**
             * PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
             * This makes PDO throw exceptions on errors
             * Instead of failing silently, we'll see what went wrong
             */
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            
            /**
             * PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
             * When we fetch data, return as associative arrays
             * Example: ['id' => 1, 'name' => 'John']
             * Instead of: [0 => 1, 1 => 'John']
             */
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            
            /**
             * PDO::ATTR_EMULATE_PREPARES => false
             * Use real prepared statements (more secure)
             */
            PDO::ATTR_EMULATE_PREPARES => false,
            
            /**
             * Set character set to utf8mb4
             * This supports all Unicode characters including emojis
             */
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    // If we reach here, connection was successful!
    // You can uncomment the line below for testing
    // echo "Database connected successfully!";
    
} catch(PDOException $e) {
    /**
     * If connection fails, catch the error
     * 
     * In development: Show detailed error
     * In production: Show generic message (more secure)
     */
    if (DEBUG_MODE) {
        // Development: Show detailed error
        die("
            <div style='
                background: #ff5252; 
                color: white; 
                padding: 20px; 
                border-radius: 8px;
                font-family: Arial;
                max-width: 600px;
                margin: 50px auto;
            '>
                <h2>❌ Database Connection Failed</h2>
                <p><strong>Error:</strong> " . $e->getMessage() . "</p>
                <h3>Quick Fixes:</h3>
                <ol>
                    <li>Check if XAMPP/WAMP is running</li>
                    <li>Verify database credentials in config.php</li>
                    <li>Make sure database 'db_uruhushya' exists</li>
                    <li>Check if MySQL service is running</li>
                </ol>
            </div>
        ");
    } else {
        // Production: Show generic message
        die("
            <div style='
                background: #ff5252; 
                color: white; 
                padding: 20px; 
                border-radius: 8px;
                font-family: Arial;
                max-width: 600px;
                margin: 50px auto;
                text-align: center;
            '>
                <h2>⚠️ Service Unavailable</h2>
                <p>We're experiencing technical difficulties. Please try again later.</p>
            </div>
        ");
    }
}

/**
 * =====================================================
 * HELPER FUNCTIONS FOR DATABASE OPERATIONS
 * =====================================================
 * These make it easier to work with the database
 */

/**
 * Execute a SELECT query and return all results
 * 
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array Results
 */
function db_select($query, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        if (DEBUG_MODE) {
            die("Query Error: " . $e->getMessage());
        }
        return [];
    }
}

/**
 * Execute a SELECT query and return single row
 * 
 * @param string $query SQL query
 * @param array $params Parameters
 * @return array|false Single row or false
 */
function db_select_one($query, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch(PDOException $e) {
        if (DEBUG_MODE) {
            die("Query Error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Execute INSERT, UPDATE, DELETE query
 * 
 * @param string $query SQL query
 * @param array $params Parameters
 * @return bool Success
 */
function db_execute($query, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($query);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        if (DEBUG_MODE) {
            die("Query Error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Get last inserted ID
 * 
 * @return string Last insert ID
 */
function db_last_insert_id() {
    global $conn;
    return $conn->lastInsertId();
}

/**
 * Begin transaction
 */
function db_begin_transaction() {
    global $conn;
    $conn->beginTransaction();
}

/**
 * Commit transaction
 */
function db_commit() {
    global $conn;
    $conn->commit();
}

/**
 * Rollback transaction
 */
function db_rollback() {
    global $conn;
    $conn->rollBack();
}

/**
 * =====================================================
 * EXAMPLE USAGE OF THESE FUNCTIONS
 * =====================================================
 * 
 * // SELECT multiple rows
 * $users = db_select("SELECT * FROM users WHERE user_type = ?", ['student']);
 * 
 * // SELECT single row
 * $user = db_select_one("SELECT * FROM users WHERE id = ?", [1]);
 * 
 * // INSERT
 * db_execute("INSERT INTO users (email, password) VALUES (?, ?)", ['test@email.com', 'hashed_password']);
 * $new_id = db_last_insert_id();
 * 
 * // UPDATE
 * db_execute("UPDATE users SET full_name = ? WHERE id = ?", ['John Doe', 1]);
 * 
 * // DELETE
 * db_execute("DELETE FROM users WHERE id = ?", [1]);
 * 
 * =====================================================
 */

?>