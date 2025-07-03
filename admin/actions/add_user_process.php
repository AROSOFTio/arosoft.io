<?php
// admin/actions/add_user_process.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/hash.php';

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';

if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_role'] !== 'admin') { // Assuming only 'admin' role can add users
    $_SESSION['flash_message'] = "You do not have permission to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_user'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=add_user');
        exit;
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password']; // Not trimming password
    $password_confirm = $_POST['password_confirm'];
    $role = in_array($_POST['role'], ['admin', 'editor']) ? $_POST['role'] : 'editor';
    
    $errors = [];

    if (empty($username)) { $errors[] = "Username is required."; }
    elseif (strlen($username) < 3) { $errors[] = "Username must be at least 3 characters."; }
    if (empty($email)) { $errors[] = "Email is required."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email format."; }
    if (empty($password)) { $errors[] = "Password is required."; }
    elseif (strlen($password) < 6) { $errors[] = "Password must be at least 6 characters."; }
    if ($password !== $password_confirm) { $errors[] = "Passwords do not match."; }

    // Check username uniqueness
    $stmt_check_user = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt_check_user->bind_param("s", $username);
    $stmt_check_user->execute();
    $stmt_check_user->store_result();
    if ($stmt_check_user->num_rows > 0) { $errors[] = "Username already taken."; }
    $stmt_check_user->close();

    // Check email uniqueness
    $stmt_check_email = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();
    if ($stmt_check_email->num_rows > 0) { $errors[] = "Email address already in use."; }
    $stmt_check_email->close();

    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $admin_base_url . 'index.php?admin_page=add_user');
        exit;
    }

    $hashed_password = hash_password($password);
    if (!$hashed_password) {
        $_SESSION['flash_message'] = "Error hashing password.";
        $_SESSION['flash_message_type'] = "error";
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $admin_base_url . 'index.php?admin_page=add_user');
        exit;
    }

    $stmt_insert = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt_insert) {
        $stmt_insert->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);
        if ($stmt_insert->execute()) {
            $_SESSION['flash_message'] = "User created successfully!";
            $_SESSION['flash_message_type'] = "success";
        } else {
            error_log("DB Error adding user: " . $stmt_insert->error);
            $_SESSION['flash_message'] = "Error creating user: " . $stmt_insert->error;
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt_insert->close();
    } else {
        error_log("DB Prepare Error adding user: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for user.";
        $_SESSION['flash_message_type'] = "error";
    }
    header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
    exit;
} else {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
    exit;
}
?>
