<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_library_site');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("❌ اتصال به دیتابیس شکست خورد: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8mb4")) {
    die("❌ خطا در تنظیم کاراکتر: " . $conn->error);
}

date_default_timezone_set('Asia/Tehran');


function hasPermission($page_name) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner') {
        return true;
    }
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        return false;
    }
    
    global $conn;
    $admin_id = $_SESSION['user_id'] ?? 0;
    
    $stmt = $conn->prepare("SELECT can_access FROM admin_permissions WHERE admin_id = ? AND page_name = ?");
    $stmt->bind_param('is', $admin_id, $page_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row && $row['can_access'] == 1;
}

function requirePermission($page_name) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner') {
        return;
    }
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header("Location: /new-web/authentication/login.php");
        exit();
    }
    
    if (!hasPermission($page_name)) {
        $_SESSION['permission_error'] = '⛔ شما دسترسی به این صفحه را ندارید.';
        header("Location: /new-web/admin/admin_panel.php");
        exit();
    }
}

function getAllDefinedPages() {
    return [
        'admin_panel'      => 'داشبورد',
        'list_users'       => 'مدیریت کاربران',
        'list_comments'    => 'مدیریت نظرات',
        'list_book'        => 'مدیریت کتاب‌ها',
        'list_categories'  => 'مدیریت دسته‌بندی‌ها',
        'list_orders'      => 'مدیریت سفارشات',
        'list_messages'    => 'مدیریت پیام‌ها',
    ];
}
?>