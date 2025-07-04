<?php
// admin/actions/edit_post_process.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/hash.php';
$htmlPurifierPath = __DIR__ . '/../../libs/htmlpurifier/library/HTMLPurifier.auto.php';
$purifier = null;
if (file_exists($htmlPurifierPath)) {
    require_once $htmlPurifierPath;
    $purifier_config = HTMLPurifier_Config::createDefault();

    // Enable HTML5 features
    $purifier_config->set('HTML.DefinitionID', 'html5-definitions');
    $purifier_config->set('HTML.DefinitionRev', 1);
    // $purifier_config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // HTML5 definition handles this better

    if ($def = $purifier_config->maybeGetRawHTMLDefinition()) {
        // Add figure and figcaption
        $figure_def = $def->addElement('figure', 'Block', 'Flow', 'Common');
        $figure_def->excludes = array('figure' => true);

        $def->addElement('figcaption', 'Block', 'Flow', 'Common', 'figure');
    }

    $purifier_config->set('HTML.AllowedElements', [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'span',
        'ul', 'ol', 'li',
        'a[href|title|target]',
        'img[src|alt|title|width|height|style]',
        'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'pre', 'code',
        'figure', 'figcaption' // Added figure and figcaption
    ]);

    // Whitelist CSS properties if 'style' attribute is allowed
    $purifier_config->set('CSS.AllowedProperties', [
        'text-align', 'float', 'margin', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom',
        'padding', 'padding-left', 'padding-right', 'padding-top', 'padding-bottom',
        'width', 'height', 'border', 'border-collapse', 'border-spacing', 'list-style-type',
        'color', 'background-color', 'font-weight', 'font-style', 'text-decoration',
        'display'
    ]);

    $purifier_config->set('HTML.TargetBlank', true);
    $purifier_config->set('AutoFormat.AutoParagraph', true);
    $purifier_config->set('AutoFormat.RemoveEmpty', true);

    // Allow 'data' URI scheme for pasted images (TinyMCE's paste_data_images: true)
    $purifier_config->set('URI.AllowedSchemes', [
        'http' => true, 'https' => true, 'mailto' => true, 'ftp' => true,
        'nntp' => true, 'news' => true, 'data' => true
    ]);

    $purifier = new HTMLPurifier($purifier_config);
} else {
     error_log("CRITICAL: HTMLPurifier library not found at: " . $htmlPurifierPath . ". Content will not be purified.");
}

// Ensure BASE_URL is properly defined with domain
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . $domain . '/');
}
$admin_base_url = BASE_URL . 'admin/';

// Specific error logs for key validation points remain, but general request dump is removed.
// error_log("Processing post ID: " . $post_id); // Example of a useful specific log

$project_root = __DIR__ . '/../../'; 
$upload_path = $project_root . 'uploads/'; 

// Authentication check
if (!isset($_SESSION['admin_user_id'])) {
    $_SESSION['flash_message'] = "You must be logged in to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=login');
    exit;
}

// Request method validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    $_SESSION['flash_message'] = "Invalid request method. Expected POST, got " . $_SERVER['REQUEST_METHOD'];
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=posts');
    exit;
}

// Form submission validation
if (!isset($_POST['submit_post']) || ($_POST['submit_post'] !== 'update' && $_POST['submit_post'] !== 'create')) {
    error_log("Invalid submit_post value: " . ($_POST['submit_post'] ?? 'not set'));
    $_SESSION['flash_message'] = "Invalid form submission";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=posts');
    exit;
}

// Post ID validation
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
error_log("Processing post ID: " . $post_id);

if ($_POST['submit_post'] === 'update' && $post_id <= 0) {
    error_log("Invalid post ID for update: " . $post_id);
    $_SESSION['flash_message'] = "Invalid or missing post ID";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=posts');
    exit;
}

// CSRF validation
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    error_log("Invalid CSRF token");
    $_SESSION['flash_message'] = "Invalid security token. Please try again.";
    $_SESSION['flash_message_type'] = "error";
    $_SESSION['form_data'] = $_POST;
    $redirect_url = $post_id > 0 
        ? BASE_URL . 'admin/index.php?admin_page=edit_post&id=' . $post_id
        : BASE_URL . 'admin/index.php?admin_page=posts';
    header('Location: ' . $redirect_url);
    exit;
}

// Get existing featured image
$stmt_old = $conn->prepare("SELECT featured_image FROM posts WHERE id = ?");
$old_featured_image = null;
if ($stmt_old) {
    $stmt_old->bind_param("i", $post_id);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    if ($result_old->num_rows === 1) {
        $old_post_data = $result_old->fetch_assoc();
        $old_featured_image = $old_post_data['featured_image'];
    }
    $stmt_old->close();
}

// Process form data
$title = trim($_POST['post_title']);
$slug = trim($_POST['post_slug']);
$raw_content = $_POST['post_content'] ?? ''; 
$category_id = !empty($_POST['post_category_id']) ? (int)$_POST['post_category_id'] : null;

// Get and validate post status
$status_to_save = 'draft'; // Default to draft
if (isset($_POST['post_status']) && in_array($_POST['post_status'], ['published', 'draft'])) {
    $status_to_save = $_POST['post_status'];
}

$remove_featured_image = isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] == '1';

$meta_description = trim($_POST['post_meta_description'] ?? '');
$meta_keywords = trim($_POST['post_meta_keywords'] ?? '');
$meta_title = trim($_POST['post_meta_title'] ?? ''); // New field
$opengraph_image_url = trim($_POST['post_opengraph_image_url'] ?? ''); // New field
$excerpt = trim($_POST['post_excerpt'] ?? '');
$post_author_id_input = $_POST['post_author_id'] ?? null;
    
// Validate form data
$errors = [];

// Validate author ID
if (empty($post_author_id_input) || !filter_var($post_author_id_input, FILTER_VALIDATE_INT) || (int)$post_author_id_input <= 0) {
    $errors[] = "A valid author must be selected.";
} else {
    // Check if author exists
    $author_check_stmt = $conn->prepare("SELECT id FROM admin_users WHERE id = ?");
    if ($author_check_stmt) {
        $author_check_stmt->bind_param("i", $post_author_id_input);
        $author_check_stmt->execute();
        $author_check_stmt->store_result();
        if ($author_check_stmt->num_rows == 0) {
            $errors[] = "Selected author is invalid.";
        }
        $author_check_stmt->close();
    } else {
        $errors[] = "Database error validating author.";
    }
}

// Validate Open Graph Image URL
if (!empty($opengraph_image_url) && !filter_var($opengraph_image_url, FILTER_VALIDATE_URL)) {
    $errors[] = "Open Graph Image URL is not a valid URL.";
}

// Required fields validation
if (empty($title)) {
    $errors[] = "Post title is required.";
}
if (empty($raw_content) && strlen(strip_tags($raw_content)) < 2) {
    $errors[] = "Post content is required.";
}

// Meta fields length validation
if (strlen($meta_description) > 255) {
    $errors[] = "Meta Description should not exceed 255 characters.";
}
if (strlen($meta_keywords) > 255) {
    $errors[] = "Meta Keywords should not exceed 255 characters.";
}

// Slug processing and validation
if (empty($slug)) {
    $slug = slugify($title);
} else {
    $slug = slugify($slug);
}

if (!empty($slug)) {
    $stmt_slug_check = $conn->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
    if (!$stmt_slug_check) {
        $errors[] = "DB error preparing slug check: " . $conn->error;
    } else {
        $stmt_slug_check->bind_param("si", $slug, $post_id);
        $stmt_slug_check->execute();
        $stmt_slug_check->store_result();
        if ($stmt_slug_check->num_rows > 0) {
            $errors[] = "This slug ('" . esc_html($slug) . "') is already in use by another post.";
        }
        $stmt_slug_check->close();
    }
} elseif (!empty($title)) {
    $errors[] = "Slug could not be generated from the title.";
}

// Handle featured image
$featured_image_filename = $old_featured_image;

// Remove existing image if requested
if ($remove_featured_image && !empty($old_featured_image)) {
    if (file_exists($upload_path . $old_featured_image)) {
        @unlink($upload_path . $old_featured_image);
    }
    $featured_image_filename = null;
}

// Process new image upload
if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
    $upload_result = handle_file_upload($_FILES['featured_image'], $upload_path);
    if (is_array($upload_result)) {
        $errors = array_merge($errors, $upload_result);
    } else {
        // Remove old image if new one is uploaded
        if (!empty($old_featured_image) && $old_featured_image !== $upload_result && !$remove_featured_image) {
            if (file_exists($upload_path . $old_featured_image)) {
                @unlink($upload_path . $old_featured_image);
            }
        }
        $featured_image_filename = $upload_result;
    }
} elseif (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $errors[] = "Error uploading new featured image. Code: " . $_FILES['featured_image']['error'];
}

// Handle validation errors
if (!empty($errors)) {
    $_SESSION['form_error'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=edit_post&id=' . $post_id);
    exit;
}

// Clean and prepare content
$clean_content = $purifier ? $purifier->purify($raw_content) : esc_html($raw_content);

// Generate excerpt if not provided
if (empty($excerpt) && !empty($clean_content)) {
    $excerpt = generate_excerpt($clean_content, 155);
}

// Prepare values for database
$category_id_to_save = $category_id === 0 ? null : $category_id;
$featured_image_to_save = $featured_image_filename;
$meta_title_to_save = empty($meta_title) ? null : $meta_title; // New
$meta_description_to_save = empty($meta_description) ? null : $meta_description;
$meta_keywords_to_save = empty($meta_keywords) ? null : $meta_keywords;
$opengraph_image_url_to_save = empty($opengraph_image_url) ? null : $opengraph_image_url; // New
$excerpt_to_save = empty($excerpt) ? null : $excerpt;
$author_id_to_save = (int)$post_author_id_input; // Already validated

// Prepare update query
$sql = "UPDATE posts SET 
    author_id = ?,
    title = ?, 
    slug = ?, 
    content = ?, 
    category_id = ?, 
    status = ?, 
    featured_image = ?,
    meta_title = ?,
    meta_description = ?, 
    meta_keywords = ?,
    opengraph_image_url = ?,
    excerpt = ?, 
    updated_at = NOW() 
WHERE id = ?"; // Added author_id

// Execute update
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("DB Prepare Error: " . $conn->error . " SQL: " . $sql);
    $_SESSION['flash_message'] = "Database error: " . esc_html($conn->error);
    $_SESSION['flash_message_type'] = "error";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=edit_post&id=' . $post_id);
    exit;
}

// New bind_param string: isssisssssssi (i for author_id at the beginning)
$stmt->bind_param("isssisssssssi",
    $author_id_to_save,
    $title, 
    $slug, 
    $clean_content, 
    $category_id_to_save, 
    $status_to_save,
    $featured_image_to_save,
    $meta_title_to_save,
    $meta_description_to_save, 
    $meta_keywords_to_save,
    $opengraph_image_url_to_save,
    $excerpt_to_save,
    $post_id
);

if ($stmt->execute()) {
    $_SESSION['flash_message'] = "Post updated successfully with status: '" . esc_html($status_to_save) . "'";
    $_SESSION['flash_message_type'] = "success";
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=posts');
    exit;
} else {
    error_log("DB Error: (" . $stmt->errno . ") " . $stmt->error);
    $_SESSION['flash_message'] = "Error updating post: " . esc_html($stmt->error);
    $_SESSION['flash_message_type'] = "error";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=edit_post&id=' . $post_id);
    exit;
}

$stmt->close();

// Close database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// End of file
?>
