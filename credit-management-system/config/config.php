<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'credit_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Digital Credit Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/credit-management-system');

// Security Configuration
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 3600); // 1 hour

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
session_start();

// Database Connection
function getDBConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Helper Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function formatCurrency($amount) {
    return '₱' . number_format($amount ?? 0, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Log Audit Trail
function logAudit($action, $description) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO audit_logs (action, description, timestamp) VALUES (?, ?, NOW())");
    $stmt->execute([$action, $description]);
}
?>
