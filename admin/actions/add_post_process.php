<?php
// admin/actions/add_post_process.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php'; // For slugify, generate_excerpt, handle_file_upload, generate_csrf_input, validate_csrf_token
// require_once __DIR__ . '/../../includes/hash.php'; // Not strictly needed for add, but good for consistency if other includes use it

// HTMLPurifier setup
$htmlPurifierPath = __DIR__ . '/../../libs/htmlpurifier/library/HTMLPurifier.auto.php';
$purifier = null;
if (file_exists($htmlPurifierPath)) {
    require_once $htmlPurifierPath;
    $purifier_config = HTMLPurifier_Config::createDefault();
    $purifier_config->set('HTML.Doctype', 'HTML 4.01 Transitional');
    $purifier_config->set('HTML.AllowedElements', [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'span',
        'ul', 'ol', 'li',
        'a[href|title|target]',
        'img[src|alt|title|width|height|style]', // Consider if img should be allowed from editor
        'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'pre', 'code', 'figure', 'figcaption' // Added figure/figcaption
    ]);
    $purifier_config->set('HTML.TargetBlank', true); // Open external links in new tab
    $purifier_config->set('AutoFormat.AutoParagraph', true);
    $purifier_config->set('AutoFormat.RemoveEmpty', true); // Removes empty paragraphs
    $purifier = new HTMLPurifier($purifier_config);
} else {
     error_log("CRITICAL: HTMLPurifier library not found at: " . $htmlPurifierPath . ". Content will not be purified.");
     // Depending on policy, you might want to halt or continue with unpurified content
}


defined('BASE_URL') or define('BASE_URL', '/'); // Adjust if your BASE_URL is different
$admin_base_url = BASE_URL . 'admin/';
$project_root = __DIR__ . '/../../';
$upload_path = $project_root . 'uploads/'; // Define upload path for featured images


if (!isset($_SESSION['admin_user_id'])) {
    $_SESSION['flash_message'] = "You must be logged in to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}

// Check if the form was submitted for creating a post
// The form in add_edit_post.php when adding sets submit_post to "create" or "1"
// Let's ensure the check is robust for either value if 'submit_post' is the main indicator
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_post'])) {
    $_SESSION['flash_message'] = "Invalid request method or missing submission data.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=add_post');
    exit;
}
// Check if 'id' is set in GET, which it shouldn't be for 'add'
if (isset($_GET['id'])) {
    $_SESSION['flash_message'] = "Invalid action for adding a post.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=add_post');
    exit;
}


if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = "Invalid or missing CSRF token. Please try again.";
    $_SESSION['flash_message_type'] = "error";
    $_SESSION['form_data'] = $_POST; // Preserve form data
    header('Location: ' . $admin_base_url . 'index.php?admin_page=add_post');
    exit;
}

// Process form data
$title = trim($_POST['post_title']);
$slug_input = trim($_POST['post_slug']); // User-provided slug
$raw_content = $_POST['post_content'] ?? '';
$category_id = !empty($_POST['post_category_id']) ? (int)$_POST['post_category_id'] : null;
$status_to_save = (isset($_POST['post_status']) && in_array($_POST['post_status'], ['published', 'draft'])) ? $_POST['post_status'] : 'draft';
$meta_description = trim($_POST['post_meta_description'] ?? '');
$meta_keywords = trim($_POST['post_meta_keywords'] ?? '');
$excerpt = trim($_POST['post_excerpt'] ?? '');
$user_id = $_SESSION['admin_user_id']; // Author ID

$errors = [];

// Validate title
if (empty($title)) {
    $errors[] = "Post title is required.";
}

// Validate content
if (empty(strip_tags($raw_content)) && empty($_FILES['featured_image']['name'])) { // Allow empty content if image is present
    if (empty($raw_content) && strlen(strip_tags($raw_content)) < 2) { // Basic check if purifier isn't run yet
         $errors[] = "Post content is required if no featured image is provided.";
    }
}


// Generate or use provided slug, then validate uniqueness
if (empty($slug_input)) {
    $slug = slugify($title);
} else {
    $slug = slugify($slug_input); // Ensure user-provided slug is clean
}

if (empty($slug) && !empty($title)) {
     $errors[] = "Slug could not be generated. Ensure the title is not made of only special characters.";
} elseif (!empty($slug)) {
    $stmt_slug_check = $conn->prepare("SELECT id FROM posts WHERE slug = ?");
    if ($stmt_slug_check) {
        $stmt_slug_check->bind_param("s", $slug);
        $stmt_slug_check->execute();
        $stmt_slug_check->store_result();
        if ($stmt_slug_check->num_rows > 0) {
            $errors[] = "This slug ('" . esc_html($slug) . "') is already in use. Please provide a unique slug or modify the title.";
        }
        $stmt_slug_check->close();
    } else {
        $errors[] = "Database error preparing slug check: " . $conn->error;
        error_log("DB Prepare Error (Slug Check Add Post): " . $conn->error);
    }
}


// Handle featured image upload
$featured_image_filename = null;
if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
    if (!is_dir($upload_path)) { // Check if upload path exists
        if (!mkdir($upload_path, 0755, true) && !is_dir($upload_path)) {
             $errors[] = "Failed to create image upload directory.";
        }
    }
    if (is_dir($upload_path) && is_writable($upload_path)) { // Check if writable
        $upload_result = handle_file_upload($_FILES['featured_image'], $upload_path, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
        if (is_array($upload_result)) { // Errors from handle_file_upload
            $errors = array_merge($errors, $upload_result);
        } else {
            $featured_image_filename = $upload_result; // Success
        }
    } else if (!in_array("Failed to create image upload directory.", $errors)){
         $errors[] = "Image upload directory is not writable or does not exist.";
    }
} elseif (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload_errors_map = [
        UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
        UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
        UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload.",
    ];
    $error_code = $_FILES['featured_image']['error'];
    $errors[] = "Error uploading featured image: " . ($upload_errors_map[$error_code] ?? "Unknown error code {$error_code}");
}


if (!empty($errors)) {
    $_SESSION['form_error'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . $admin_base_url . 'index.php?admin_page=add_post');
    exit;
}

// Purify content and generate excerpt if needed
$clean_content = $purifier ? $purifier->purify($raw_content) : esc_html($raw_content); // Fallback to esc_html if no purifier
if (empty($excerpt) && !empty($clean_content)) {
    $excerpt = generate_excerpt(strip_tags($clean_content), 155); // Excerpt from stripped tags for better plain text
}

$category_id_to_save = ($category_id === 0 || $category_id === '') ? null : $category_id;
$meta_description_to_save = empty($meta_description) ? null : $meta_description;
$meta_keywords_to_save = empty($meta_keywords) ? null : $meta_keywords;
$excerpt_to_save = empty($excerpt) ? null : $excerpt;


$sql = "INSERT INTO posts (user_id, title, slug, content, category_id, status, featured_image, meta_description, meta_keywords, excerpt, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("DB Prepare Error (Add Post): " . $conn->error . " SQL: " . $sql);
    $_SESSION['flash_message'] = "Database error preparing statement for adding post. Check error logs.";
    $_SESSION['flash_message_type'] = "error";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . $admin_base_url . 'index.php?admin_page=add_post');
    exit;
}

// Order: user_id (i), title (s), slug (s), content (s), category_id (i), status (s),
// featured_image (s), meta_description (s), meta_keywords (s), excerpt (s)
$stmt->bind_param("isssisssss",
    $user_id, $title, $slug, $clean_content, $category_id_to_save, $status_to_save,
    $featured_image_filename, $meta_description_to_save, $meta_keywords_to_save, $excerpt_to_save
);

if ($stmt->execute()) {
    $new_post_id = $conn->insert_id;
    $_SESSION['flash_message'] = "Post created successfully! (ID: {$new_post_id})";
    $_SESSION['flash_message_type'] = "success";
    // Redirect to edit page of the new post or to posts list
    header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_post&id=' . $new_post_id);
    exit;
} else {
    error_log("DB Execute Error (Add Post): (" . $stmt->errno . ") " . $stmt->error);
    $_SESSION['flash_message'] = "Error creating post. Database code: " . $stmt->errno;
    $_SESSION['flash_message_type'] = "error";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . $admin_base_url . 'index.php?admin_page=add_post');
    exit;
}

$stmt->close();
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
