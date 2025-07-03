<?php
// admin/actions/delete_user.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';

if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_role'] !== 'admin') {
    $_SESSION['flash_message'] = "You do not have permission to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=dashboard');
    exit;
}

if (isset($_GET['id']) && isset($_GET['csrf_token'])) {
    $user_id_to_delete = (int)$_GET['id'];
    $token = $_GET['csrf_token'];

    if (!validate_csrf_token($token)) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
        exit;
    }

    // Prevent deleting oneself
    if (isset($_SESSION['admin_user_id']) && $_SESSION['admin_user_id'] == $user_id_to_delete) {
        $_SESSION['flash_message'] = "You cannot delete your own account.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
        exit;
    }

    // Optional: Prevent deleting the last admin user
    $stmt_count_admins = $conn->prepare("SELECT COUNT(*) as admin_count FROM admin_users WHERE role = 'admin'");
    $stmt_count_admins->execute();
    $admin_count_result = $stmt_count_admins->get_result()->fetch_assoc();
    $stmt_count_admins->close();

    $stmt_user_role = $conn->prepare("SELECT role FROM admin_users WHERE id = ?");
    $stmt_user_role->bind_param("i", $user_id_to_delete);
    $stmt_user_role->execute();
    $user_to_delete_role_result = $stmt_user_role->get_result()->fetch_assoc();
    $stmt_user_role->close();

    if ($admin_count_result['admin_count'] <= 1 && $user_to_delete_role_result && $user_to_delete_role_result['role'] === 'admin') {
        $_SESSION['flash_message'] = "Cannot delete the last administrator account.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
        exit;
    }


    $stmt_delete = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $user_id_to_delete);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $_SESSION['flash_message'] = "User deleted successfully.";
                $_SESSION['flash_message_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "User not found or already deleted.";
                $_SESSION['flash_message_type'] = "error";
            }
        } else {
            error_log("DB Error deleting user: " . $stmt_delete->error);
            $_SESSION['flash_message'] = "Error deleting user: " . $stmt_delete->error;
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt_delete->close();
    } else {
        error_log("DB Prepare Error deleting user: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for user deletion.";
        $_SESSION['flash_message_type'] = "error";
    }
} else {
    $_SESSION['flash_message'] = "Invalid request: Missing user ID or CSRF token.";
    $_SESSION['flash_message_type'] = "error";
}
header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
exit;
?>
