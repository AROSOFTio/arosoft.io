<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- HELPERS & UTILITIES ---
require_once 'includes/functions.php'; // esc_html(), load_site_settings(), etc.

// --- LOAD SITE SETTINGS ---
$site_settings = load_site_settings();

// --- CONFIGURATION (Use loaded settings, fallback to defaults if needed) ---
define('BASE_URL', '/');
define('SITE_NAME', $site_settings['site_name'] ?? 'dipug.com');
define('SITE_TAGLINE', $site_settings['site_tagline'] ?? 'Digital Innovation and Programming');
define('POSTS_PER_PAGE', (int)($site_settings['posts_per_page'] ?? 10));
define('CONTACT_EMAIL', $site_settings['contact_email'] ?? 'info@example.com');
define('FOOTER_COPYRIGHT', $site_settings['footer_copyright'] ?? '&copy; {year} dipug.com. All Rights Reserved.');

define('DB_HOST', 'localhost');
define('DB_USER', 'u662439561_main5_');
define('DB_PASS', 'XpGmn&9a');
define('DB_NAME', 'u662439561_Main5_');

require_once 'includes/db.php';   // Must set up $conn = new mysqli(...)
require_once 'includes/hash.php';

// --- BASIC ROUTING ---
$page        = isset($_GET['page']) ? $_GET['page'] : 'home';
$admin_page  = isset($_GET['admin_page']) ? $_GET['admin_page'] : null;

// If this is an admin page request, include the admin index and exit
if ($admin_page) {
    if ($page === 'admin') {
        include_once 'admin/index.php';
        exit;
    }
}

// Prepare default meta
$page_title        = SITE_NAME; 
$meta_description  = "Welcome to " . SITE_NAME . ". Your trusted partner for digital innovation, programming, IT services, software, courses, and web development.";
$meta_keywords     = "";

// Build a suffix for titles
$page_title_suffix = SITE_NAME . " - " . SITE_TAGLINE;

// Determine which file to include based on $page
switch ($page) {
    case 'home':
        $page_title     = SITE_NAME . " - Innovate, Program, Succeed";
        $include_file   = 'pages/home.php';
        break;

    case 'about':
        $page_title     = "About Us - " . $page_title_suffix;
        $include_file   = 'pages/about.php';
        break;

    case 'contact':
        $page_title     = "Contact Us - " . $page_title_suffix;
        $include_file   = 'pages/contact.php';
        break;

    case 'privacy':
        $page_title     = "Privacy Policy - " . $page_title_suffix;
        $include_file   = 'pages/privacy.php';
        break;

    case 'services_overview':
        $page_title     = "Our Services - " . $page_title_suffix;
        $include_file   = 'pages/services_overview.php';
        break;

    case 'software':
        $page_title     = "Software Solutions - " . $page_title_suffix;
        $include_file   = 'pages/software.php';
        break;

    case 'courses':
        $page_title     = "Online Courses - " . $page_title_suffix;
        $include_file   = 'pages/courses.php';
        break;

    case 'support':
        $page_title     = "Tech Support - " . $page_title_suffix;
        $include_file   = 'pages/support.php';
        break;

    case 'webDev':
        $page_title     = "Web Development - " . $page_title_suffix;
        $include_file   = 'pages/web_development.php';
        break;

    case 'cloud':
        $page_title     = "Cloud Solutions - " . $page_title_suffix;
        $include_file   = 'pages/cloud.php';
        break;

    case 'cybersecurity':
        $page_title     = "Cybersecurity - " . $page_title_suffix;
        $include_file   = 'pages/cybersecurity.php';
        break;

    case 'portfolio':
        $page_title     = "Our Portfolio - " . $page_title_suffix;
        $include_file   = 'pages/portfolio.php';
        break;

    case 'blog':
        $page_title     = "Blog - " . $page_title_suffix;
        $include_file   = 'pages/blog.php';
        break;

    case 'post':
        // If someone explicitly calls `?page=post&slug=…`, handle that
        $page_title     = "Blog Post - " . $page_title_suffix;
        $include_file   = 'pages/post.php';
        break;

    default:
        // NO MATCH ON A “KNOWN PAGE” – ATTEMPT TO LOOK UP A BLOG POST WITH this SLUG
        // --------------------------------------------------------------------------------
        // In .htaccess we rewrote /some-post-slug to index.php?page=some-post-slug.
        // So “$page” might be the slug of a published post. Let’s try to fetch it:
        $stmt = $conn->prepare("
            SELECT id 
            FROM posts 
            WHERE slug = ? AND status = 'published' 
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("s", $page);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    // We found a matching post → show it via pages/post.php
                    $post_row           = $result->fetch_assoc();
                    $_GET['slug']       = $page;           // populate for post.php
                    $page_title         = "Blog Post - " . $page_title_suffix;
                    $include_file       = 'pages/post.php';
                    $stmt->close();
                    break;  // Exit switch – we know to load post.php
                }
            }
            $stmt->close();
        }

        // If we still didn’t set `$include_file`, this truly is a 404:
        http_response_code(404);
        $page_title   = "Page Not Found - " . $page_title_suffix;
        $include_file = 'pages/404.php';
        break;
}

include_once 'includes/header.php';

if (isset($include_file) && file_exists($include_file)) {
    include_once $include_file;
} else {
    // This should never happen if the above logic is correct—but just in case:
    echo "
      <div class='container mx-auto my-10 p-8 bg-red-100 border border-red-400 text-red-700 rounded-lg text-center'>
        <h1 class='text-2xl font-bold'>Error: Content File Missing</h1>
        <p>The file '" . esc_html($include_file) . "' could not be found.</p>
      </div>
    ";
}

include_once 'includes/footer.php';

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
