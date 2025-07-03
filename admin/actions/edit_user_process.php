<?php
// admin/actions/edit_user_process.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/hash.php';

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';

if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_role'] !== 'admin') {
    $_SESSION['flash_message'] = "You do not have permission to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_user']) && isset($_GET['id'])) {
    $user_id_to_edit = (int)$_GET['id'];

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_user&id=' . $user_id_to_edit);
        exit;
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password']; // Not trimming
    $password_confirm = $_POST['password_confirm'];
    $role = in_array($_POST['role'], ['admin', 'editor']) ? $_POST['role'] : 'editor';
    
    $errors = [];

    if (empty($username)) { $errors[] = "Username is required."; }
    elseif (strlen($username) < 3) { $errors[] = "Username must be at least 3 characters."; }
    if (empty($email)) { $errors[] = "Email is required."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email format."; }
    
    if (!empty($password)) { // Only validate password if it's being changed
        if (strlen($password) < 6) { $errors[] = "New password must be at least 6 characters."; }
        if ($password !== $password_confirm) { $errors[] = "New passwords do not match."; }
    }

    // Check username uniqueness (excluding current user)
    $stmt_check_user = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
    $stmt_check_user->bind_param("si", $username, $user_id_to_edit);
    $stmt_check_user->execute();
    $stmt_check_user->store_result();
    if ($stmt_check_user->num_rows > 0) { $errors[] = "Username already taken by another user."; }
    $stmt_check_user->close();

    // Check email uniqueness (excluding current user)
    $stmt_check_email = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $stmt_check_email->bind_param("si", $email, $user_id_to_edit);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();
    if ($stmt_check_email->num_rows > 0) { $errors[] = "Email address already in use by another user."; }
    $stmt_check_email->close();

    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve form data
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_user&id=' . $user_id_to_edit);
        exit;
    }

    $sql_parts = ["username = ?", "email = ?", "full_name = ?", "role = ?"];
    $params = [$username, $email, $full_name, $role];
    $types = "ssss";

    if (!empty($password)) {
        $hashed_password = hash_password($password);
        if (!$hashed_password) {
            $_SESSION['flash_message'] = "Error hashing new password.";
            $_SESSION['flash_message_type'] = "error";
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_user&id=' . $user_id_to_edit);
            exit;
        }
        $sql_parts[] = "password_hash = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }
    
    $params[] = $user_id_to_edit; // For the WHERE clause
    $types .= "i";

    $sql = "UPDATE admin_users SET " . implode(", ", $sql_parts) . " WHERE id = ?";
    
    $stmt_update = $conn->prepare($sql);
    if ($stmt_update) {
        $stmt_update->bind_param($types, ...$params);
        if ($stmt_update->execute()) {
            $_SESSION['flash_message'] = "User updated successfully!";
            $_SESSION['flash_message_type'] = "success";
        } else {
            error_log("DB Error updating user: " . $stmt_update->error);
            $_SESSION['flash_message'] = "Error updating user: " . $stmt_update->error;
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt_update->close();
    } else {
        error_log("DB Prepare Error updating user: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for user update.";
        $_SESSION['flash_message_type'] = "error";
    }
    header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
    exit;
} else {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
    exit;
}
?>
