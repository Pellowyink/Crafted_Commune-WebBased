<?php
/**
 * Crafted Commune Café - Database Configuration
 * Easy to edit database connection settings
 */

// ========================================
// DATABASE SETTINGS - EDIT THESE
// ========================================
define('DB_HOST', 'localhost');        // ← Don't change
define('DB_NAME', 'crafted_commune');  // ← Your database name
define('DB_USER', 'root');             // ← XAMPP default
define('DB_PASS', '');                 // ← Empty for XAMPP

// ========================================
// SITE SETTINGS - EDIT THESE
// ========================================
define('SITE_NAME', 'Crafted Commune');
define('SITE_URL', 'http://localhost/crafted-commune'); // Change to your URL
define('ADMIN_EMAIL', 'admin@craftedcommune.com');

// ========================================
// SECURITY SETTINGS
// ========================================
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('PASSWORD_SALT', 'your_random_salt_here_change_this'); // Change this!

// ========================================
// ADMIN PANEL SETTINGS
// ========================================
define('ADMIN_URL', 'admin'); // URL to access admin panel (e.g., yoursite.com/admin)
define('ITEMS_PER_PAGE', 20); // Pagination

// ========================================
// DATABASE CONNECTION
// ========================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ========================================
// SESSION MANAGEMENT
// ========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Check if user is logged in as admin
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Require admin login
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Sanitize output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

/**
 * Log admin activity
 */
function logActivity($adminId, $action, $description = '') {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (admin_id, action, description, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$adminId, $action, $description, $ip]);
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Calculate points from price
 */
function calculatePoints($price) {
    $ratio = 10; // ₱10 = 1 point
    return floor($price / $ratio);
}

/**
 * Get setting value
 */
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update setting value
 */
function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$key, $value, $value]);
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Flash message helper
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type; // success, error, warning, info
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
?>