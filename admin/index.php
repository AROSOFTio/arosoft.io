<?php
// admin/index.php - Main router for the admin section

// Ensure errors are shown for debugging during development, comment out/set to 0 for production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- HELPERS & UTILITIES ---
// Use __DIR__ for reliable relative paths
$base_path_for_includes = __DIR__ . '/../includes/';
require_once $base_path_for_includes . 'functions.php'; 
require_once $base_path_for_includes . 'db.php'; 
require_once $base_path_for_includes . 'hash.php'; 

// --- LOAD SITE SETTINGS (Uses load_site_settings() from functions.php) ---
$site_settings = load_site_settings();


// --- CONFIGURATION (Use loaded settings, fallback to defaults if needed) ---
// Ensure BASE_URL is properly defined with domain
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    $base_path = '/';  // This should match your .htaccess RewriteBase
    define('BASE_URL', $protocol . $domain . $base_path);
}

defined('SITE_NAME') or define('SITE_NAME', $site_settings['site_name'] ?? 'dipug.com');

// Debug URL construction
error_log("BASE_URL in admin/index.php: " . BASE_URL);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP_HOST: " . $_SERVER['HTTP_HOST']);
defined('SITE_TAGLINE') or define('SITE_TAGLINE', $site_settings['site_tagline'] ?? 'Digital Innovation and Programing');
defined('POSTS_PER_PAGE') or define('POSTS_PER_PAGE', (int)($site_settings['posts_per_page'] ?? 10));
defined('CONTACT_EMAIL') or define('CONTACT_EMAIL', $site_settings['contact_email'] ?? 'info@example.com');
defined('FOOTER_COPYRIGHT') or define('FOOTER_COPYRIGHT', $site_settings['footer_copyright'] ?? '&copy; {year} dipug.com. All Rights Reserved.');


$admin_base_url = rtrim(BASE_URL, '/') . '/admin/'; // Ensure trailing slash for admin base

// --- Admin Page Routing ---
$admin_page = isset($_GET['admin_page']) ? $_GET['admin_page'] : 'dashboard'; // This variable is used by sidebar

// --- Authentication Check (Basic) ---
if ($admin_page !== 'login' && $admin_page !== 'login_process' && !isset($_SESSION['admin_user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}
if ($admin_page === 'login' && isset($_SESSION['admin_user_id'])) {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=dashboard');
    exit;
}


$admin_page_title = "Admin Dashboard"; 
$admin_include_file_path = __DIR__ . "/pages/dashboard.php"; // Default include full path

switch ($admin_page) {
    case 'dashboard':
        $admin_page_title = "Dashboard - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/dashboard.php';
        break;
    case 'login':
        $admin_page_title = "Admin Login - " . SITE_NAME;
        $admin_include_file_path = __DIR__ . '/pages/login.php'; 
        break;
    case 'login_process':
        include __DIR__ . '/auth/login_process.php'; 
        exit; 
        break;
    case 'logout':
        include __DIR__ . '/auth/logout.php'; 
        exit;
        break;
    // Posts Management
    case 'posts':
        $admin_page_title = "Manage Posts - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/manage_posts.php';
        break;
    case 'add_post':
        $admin_page_title = "Add New Post - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/add_edit_post.php'; 
        break;
    case 'edit_post':
        $admin_page_title = "Edit Post - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/add_edit_post.php'; 
        break;
    // Categories Management
    case 'categories':
        $admin_page_title = "Manage Categories - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/manage_categories.php';
        break;
    case 'add_category':
        $admin_page_title = "Add New Category - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/add_edit_category.php';
        break;
    case 'edit_category':
        $admin_page_title = "Edit Category - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/add_edit_category.php';
        break;
    // Users Management
    case 'users':
        $admin_page_title = "Manage Users - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/manage_users.php';
        break;
    case 'add_user':
        $admin_page_title = "Add New User - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/add_edit_user.php';
        break;
    case 'edit_user':
        $admin_page_title = "Edit User - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/add_edit_user.php';
        break;
    // Settings
    case 'settings':
        $admin_page_title = "Site Settings - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/settings.php';
        break;
    case 'comments':
        $admin_page_title = "Manage Comments - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/manage_comments.php';
        break;
    default:
        http_response_code(404);
        $admin_page_title = "Page Not Found - " . SITE_NAME . " Admin";
        $admin_include_file_path = __DIR__ . '/pages/404.php'; 
        break;
}

if ($admin_page !== 'login' && $admin_page !== 'login_process' && $admin_page !== 'logout') {
    include_once __DIR__ . '/includes/header.php';
}

if (file_exists($admin_include_file_path)) {
    include_once $admin_include_file_path;
} else {
    echo "<div class='p-8 bg-red-100 border border-red-400 text-red-700 rounded-lg text-center fixed inset-0 flex items-center justify-center z-[10000]'>";
    echo "<div class='bg-white p-10 rounded-lg shadow-xl'>";
    echo "<h1 class='text-xl font-bold mb-4'>Admin Content File Missing</h1>";
    echo "<p>The file '<strong>" . esc_html(basename($admin_include_file_path)) . "</strong>' (expected at " . esc_html($admin_include_file_path) . ") could not be found.</p>";
    echo "<p class='mt-4'>Current admin_page variable: '" . esc_html($admin_page) . "'</p>";
    echo "</div></div>";
}

if ($admin_page !== 'login' && $admin_page !== 'login_process' && $admin_page !== 'logout') {
    include_once __DIR__ . '/includes/footer.php';
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
