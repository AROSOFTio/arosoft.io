<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

<?php
// admin/actions/delete_post.php
/**
 * Handles single post deletion.
 *
 * Expects POST request with:
 * - csrf_token: For security.
 * - post_id: The ID of the post to delete.
 *
 * Also handles AJAX requests by returning JSON, but primarily designed for form submission
 * from the manage_posts.php page's single delete modal.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Robust include paths
$base_project_path = dirname(__DIR__, 2); // Goes up two levels to the project root
require_once $base_project_path . '/includes/db.php';
require_once $base_project_path . '/includes/functions.php';
// require_once $base_project_path . '/includes/hash.php'; // hash.php might not be needed if not used directly
require_once dirname(__DIR__) . '/auth/check_auth.php'; // Ensures user is admin and handles redirect

// Ensure BASE_URL is defined for redirects (should be defined in a central init file)
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    $base_path_config = '/'; // Adjust if site is in a subfolder
    define('BASE_URL', $protocol . $domain . $base_path_config);
}
$admin_base_url = rtrim(BASE_URL, '/') . '/admin/';
$upload_path = $base_project_path . '/uploads/'; // Use absolute path for uploads
$redirect_url = $admin_base_url . 'index.php?admin_page=posts';


// Check for AJAX request for potential future use, but primarily handle POST
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function send_json_response($success, $message, $data = null) {
    // Ensure no output before this
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Expect POST request for delete operations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = "Invalid request method for delete operation.";
    $_SESSION['flash_message_type'] = "error";
    if ($is_ajax) send_json_response(false, $_SESSION['flash_message']);
    header('Location: ' . $redirect_url);
    exit;
}

// CSRF Token Validation from POST
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = "Invalid security token. Please try again.";
    $_SESSION['flash_message_type'] = "error";
    if ($is_ajax) send_json_response(false, $_SESSION['flash_message']);
    header('Location: ' . $redirect_url);
    exit;
}

if (!isset($_POST['post_id']) || empty((int)$_POST['post_id'])) {
    $_SESSION['flash_message'] = "Invalid request: Missing post ID.";
    $_SESSION['flash_message_type'] = "error";
    if ($is_ajax) send_json_response(false, $_SESSION['flash_message']);
    header('Location: ' . $redirect_url);
    exit;
}

$post_id = (int)$_POST['post_id'];

// Fetch post details before deletion (for image and title for message)
$stmt_img = $conn->prepare("SELECT featured_image, title FROM posts WHERE id = ?");
$featured_image_to_delete = null;
$post_title = 'Post #' . $post_id; // Default title for message

if ($stmt_img) {
    $stmt_img->bind_param("i", $post_id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    if ($result_img->num_rows === 1) {
        $post_data = $result_img->fetch_assoc();
        $featured_image_to_delete = $post_data['featured_image'];
        $post_title = $post_data['title'];
    }
    $stmt_img->close();
}

// Delete the post
$stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $post_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Delete associated featured image if exists
            if (!empty($featured_image_to_delete)) {
                $file_path = rtrim($upload_path, '/') . '/' . $featured_image_to_delete;
                if (file_exists($file_path)) {
                    @unlink($file_path); // Use @ to suppress errors if file not found or unlink fails
                }
            }
            
            $success_message = sprintf('Post "%s" (ID: %d) deleted successfully.', esc_html($post_title), $post_id);
            $_SESSION['flash_message'] = $success_message;
            $_SESSION['flash_message_type'] = "success";
            if ($is_ajax) send_json_response(true, $success_message, ['deleted_id' => $post_id]);

        } else {
            $_SESSION['flash_message'] = "Post not found or already deleted (ID: " . $post_id . ").";
            $_SESSION['flash_message_type'] = "error";
            if ($is_ajax) send_json_response(false, $_SESSION['flash_message']);
        }
    } else {
        $db_error = $stmt->error;
        $_SESSION['flash_message'] = "Error deleting post (ID: " . $post_id . "). Database error.";
        $_SESSION['flash_message_type'] = "error";
        error_log("DB Error deleting post ID $post_id: " . $db_error);
        if ($is_ajax) send_json_response(false, $_SESSION['flash_message'] . " " . $db_error);
    }
    $stmt->close();
} else {
    $conn_error = $conn->error;
    $_SESSION['flash_message'] = "Database error. Could not prepare statement for deletion.";
    $_SESSION['flash_message_type'] = "error";
    error_log("DB Prepare Error deleting post: " . $conn_error);
    if ($is_ajax) send_json_response(false, $_SESSION['flash_message'] . " " . $conn_error);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Redirect for non-AJAX requests or if AJAX response wasn't sent
if (!$is_ajax) {
    header('Location: ' . $redirect_url);
    exit;
}
// If it was AJAX and we reached here, it means send_json_response was called.
}
?>
