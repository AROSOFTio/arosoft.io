<?php
<?php
// admin/actions/bulk_post_actions.php
/**
 * Handles bulk actions (publish, draft, delete) for posts submitted from the manage_posts.php page.
 *
 * Expects POST request with:
 * - csrf_token: For security.
 * - bulk_action: The action to perform (e.g., 'publish', 'draft', 'delete').
 * - post_ids[]: An array of post IDs to perform the action on.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use __DIR__ for reliable relative paths
$base_path_for_includes = dirname(__DIR__, 2) . '/includes/'; // Go up two levels to project root, then to includes
require_once $base_path_for_includes . 'functions.php';
require_once $base_path_for_includes . 'db.php';     // For $conn
require_once dirname(__DIR__) . '/auth/check_auth.php'; // Ensures user is admin, handles session start and redirect if not authed.

// Ensure BASE_URL is defined for redirects (should ideally be in a central config/init)
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    $base_path = '/';
    define('BASE_URL', $protocol . $domain . $base_path);
}
$admin_base_url = rtrim(BASE_URL, '/') . '/admin/';
$redirect_url = $admin_base_url . 'index.php?admin_page=posts';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = "Invalid request method.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $redirect_url);
    exit;
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = "CSRF token validation failed. Action aborted.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $redirect_url);
    exit;
}

$bulk_action = $_POST['bulk_action'] ?? '';
$post_ids = $_POST['post_ids'] ?? [];

if (empty($bulk_action)) {
    $_SESSION['flash_message'] = "No bulk action selected.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $redirect_url);
    exit;
}

if (empty($post_ids) || !is_array($post_ids)) {
    $_SESSION['flash_message'] = "No posts selected for the bulk action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $redirect_url);
    exit;
}

// Sanitize post IDs to ensure they are integers
$sanitized_post_ids = array_map('intval', $post_ids);
$sanitized_post_ids = array_filter($sanitized_post_ids, function($id) { return $id > 0; });

if (empty($sanitized_post_ids)) {
    $_SESSION['flash_message'] = "No valid posts selected.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $redirect_url);
    exit;
}

$placeholders = implode(',', array_fill(0, count($sanitized_post_ids), '?'));
$types = str_repeat('i', count($sanitized_post_ids));
$success_count = 0;
$error_count = 0;

switch ($bulk_action) {
    case 'publish':
    case 'draft':
        $new_status = ($bulk_action === 'publish') ? 'published' : 'draft';
        $sql = "UPDATE posts SET status = ? WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s" . $types, $new_status, ...$sanitized_post_ids);
            if ($stmt->execute()) {
                $success_count = $stmt->affected_rows;
            } else {
                error_log("Bulk action ($bulk_action) DB error: " . $stmt->error);
                $error_count = count($sanitized_post_ids); // Assume all failed if execute fails
            }
            $stmt->close();
        } else {
            error_log("Bulk action ($bulk_action) prepare error: " . $conn->error);
            $error_count = count($sanitized_post_ids);
        }
        if ($success_count > 0) {
            $_SESSION['flash_message'] = "$success_count post(s) status updated to '$new_status'.";
            $_SESSION['flash_message_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Could not update status for selected posts. Check error logs.";
            $_SESSION['flash_message_type'] = "error";
        }
        break;

    case 'delete':
        $sql = "DELETE FROM posts WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$sanitized_post_ids);
            if ($stmt->execute()) {
                $success_count = $stmt->affected_rows;
            } else {
                error_log("Bulk delete DB error: " . $stmt->error);
                $error_count = count($sanitized_post_ids);
            }
            $stmt->close();
        } else {
            error_log("Bulk delete prepare error: " . $conn->error);
            $error_count = count($sanitized_post_ids);
        }

        if ($success_count > 0) {
            $_SESSION['flash_message'] = "$success_count post(s) successfully deleted.";
            $_SESSION['flash_message_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Could not delete selected posts. Check error logs.";
            $_SESSION['flash_message_type'] = "error";
        }
        break;

    default:
        $_SESSION['flash_message'] = "Invalid bulk action specified: " . esc_html($bulk_action);
        $_SESSION['flash_message_type'] = "error";
        break;
}

if ($error_count > 0 && $success_count > 0) {
    $_SESSION['flash_message'] = "$success_count post(s) processed. $error_count post(s) failed. Check error logs.";
    $_SESSION['flash_message_type'] = "warning"; // Partial success
} elseif ($error_count > 0 && $success_count == 0) {
     $_SESSION['flash_message'] = "All selected posts failed to process for action '$bulk_action'. Check error logs.";
    $_SESSION['flash_message_type'] = "error";
}


header('Location: ' . $redirect_url);
exit;

?>
