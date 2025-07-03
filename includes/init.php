<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/hash.php';

// Load site settings
$site_settings = load_site_settings();

// Define constants
defined('BASE_URL') or define('BASE_URL', '/');
defined('SITE_NAME') or define('SITE_NAME', $site_settings['site_name'] ?? 'dipug.com');
defined('SITE_TAGLINE') or define('SITE_TAGLINE', $site_settings['site_tagline'] ?? 'Digital Innovation and Programming');
defined('POSTS_PER_PAGE') or define('POSTS_PER_PAGE', (int)($site_settings['posts_per_page'] ?? 10));
defined('CONTACT_EMAIL') or define('CONTACT_EMAIL', $site_settings['contact_email'] ?? 'info@example.com');
defined('FOOTER_COPYRIGHT') or define('FOOTER_COPYRIGHT', $site_settings['footer_copyright'] ?? '&copy; {year} dipug.com. All Rights Reserved.');

// Set admin base URL
$admin_base_url = rtrim(BASE_URL, '/') . '/admin/';

// Set timezone
date_default_timezone_set('UTC');

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token = null) {
        if ($token === null) {
            $token = $_POST['token'] ?? $_GET['token'] ?? null;
        }
        return $token === $_SESSION['csrf_token'];
    }
}

// Flash Messages
if (!isset($_SESSION['flash_message'])) {
    $_SESSION['flash_message'] = '';
    $_SESSION['flash_message_type'] = '';
}

if (!function_exists('set_flash_message')) {
    function set_flash_message($message, $type = 'info') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_message_type'] = $type;
    }
}

if (!function_exists('get_flash_message')) {
    function get_flash_message() {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_message_type'];
        $_SESSION['flash_message'] = '';
        $_SESSION['flash_message_type'] = '';
        return ['message' => $message, 'type' => $type];
    }
}
