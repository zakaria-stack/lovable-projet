<?php
// Database connection configuration with improved security and error handling
// XAMPP configuration for MySQL as required

// Constants for database connection - better practice than variables
define('DB_HOST', 'localhost');  // Use 'localhost' for XAMPP
define('DB_NAME', 'student_notes');
define('DB_USER', 'root');      // Default XAMPP username
define('DB_PASS', 'ZAk&#sql*#*192'); // Your password

// Global connection variable
$conn = null;

// Connect function with improved error handling
function connectDatabase() {
    global $conn;
    
    try {
        // Connect to MySQL using PDO
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
            PDO::ATTR_PERSISTENT => true // For better performance with connection pooling
        ];
        
        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        return true;
    } catch(PDOException $e) {
        // Log the error to a file instead of exposing details
        error_log("Database Connection Error: " . $e->getMessage());
        
        // Set a user-friendly error message
        $GLOBALS['db_connection_error'] = "Could not connect to the database";
        $GLOBALS['db_error_detail'] = "Please make sure XAMPP is running with MySQL service started.
            If you're setting up for the first time, import 'student_notes.sql' via phpMyAdmin.";
        return false;
    }
}

// Try to connect
connectDatabase();

// Enhanced security function to sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Added ENT_QUOTES for better security
    return $data;
}

// Session management functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Enhanced redirect function with security headers
function redirect($url, $message = '', $type = 'success') {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    
    // Add security headers before redirecting
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("Location: $url");
    exit();
}

// CSRF token management for form security
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,     // Prevent JavaScript access to session cookie
        'cookie_secure' => false,      // Set to true in production with HTTPS
        'cookie_samesite' => 'Lax',    // Controls how cookies are sent with cross-site requests
        'use_strict_mode' => true      // Rejects uninitialized session IDs
    ]);
}
?>