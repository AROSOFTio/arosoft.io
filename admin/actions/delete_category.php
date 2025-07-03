<?php
// admin/actions/delete_category.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';

if (!isset($_SESSION['admin_user_id'])) {
    $_SESSION['flash_message'] = "Authentication required.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}

if (isset($_GET['id']) && isset($_GET['csrf_token'])) {
    $category_id = (int)$_GET['id'];
    $token = $_GET['csrf_token'];

    if (!validate_csrf_token($token)) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
        exit;
    }

    // Check if category is associated with any posts
    $stmt_check_posts = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE category_id = ?");
    $stmt_check_posts->bind_param("i", $category_id);
    $stmt_check_posts->execute();
    $result_posts = $stmt_check_posts->get_result()->fetch_assoc();
    $stmt_check_posts->close();

    if ($result_posts['post_count'] > 0) {
        $_SESSION['flash_message'] = "Cannot delete category. It is associated with " . $result_posts['post_count'] . " post(s). Please reassign or delete those posts first.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
        exit;
    }

    // Proceed with deletion
    $stmt_delete = $conn->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $category_id);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $_SESSION['flash_message'] = "Category deleted successfully.";
                $_SESSION['flash_message_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Category not found or already deleted.";
                $_SESSION['flash_message_type'] = "error";
            }
        } else {
            error_log("DB Error deleting category: " . $stmt_delete->error);
            $_SESSION['flash_message'] = "Error deleting category: " . $stmt_delete->error;
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt_delete->close();
    } else {
        error_log("DB Prepare Error deleting category: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for category deletion.";
        $_SESSION['flash_message_type'] = "error";
    }
} else {
    $_SESSION['flash_message'] = "Invalid request: Missing category ID or CSRF token.";
    $_SESSION['flash_message_type'] = "error";
}
header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
exit;
?>
