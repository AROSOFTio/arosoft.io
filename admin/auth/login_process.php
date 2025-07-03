<?php
// admin/auth/login_process.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Using __DIR__ for more reliable path resolution
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/hash.php'; 
require_once __DIR__ . '/../../includes/functions.php'; 

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username']);
    $password = trim($_POST['password']);
    // Use a default redirect if not provided or invalid
    $redirect_url_from_form = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';
    $default_redirect = $admin_base_url . 'index.php?admin_page=dashboard';
    
    // Basic validation for the redirect URL to prevent open redirect vulnerabilities
    if (!empty($redirect_url_from_form) && (strpos($redirect_url_from_form, BASE_URL) === 0 || substr($redirect_url_from_form, 0, 1) === '/')) {
        $redirect_url = $redirect_url_from_form;
    } else {
        $redirect_url = $default_redirect;
    }


    if (empty($username_or_email) || empty($password)) {
        $_SESSION['login_error'] = "Username/Email and password are required.";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
        exit;
    }

    $login_field = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    $sql = "SELECT id, username, password_hash, full_name, role FROM admin_users WHERE $login_field = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Login process DB prepare error: " . $conn->error);
        $_SESSION['login_error'] = "Database error. Please try again later.";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
        exit;
    }

    $stmt->bind_param("s", $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (verify_password($password, $user['password_hash'])) {
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_full_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            
            session_regenerate_id(true);

            // Check if there was a redirect URL stored in session from before login
            $final_redirect = $_SESSION['redirect_after_login'] ?? $redirect_url;
            unset($_SESSION['redirect_after_login']); // Clear it after use

            // Final validation of the redirect URL
            if (!empty($final_redirect) && (strpos($final_redirect, BASE_URL) === 0 || substr($final_redirect, 0, 1) === '/')) {
                 header('Location: ' . $final_redirect);
            } else {
                 header('Location: ' . $default_redirect);
            }
            exit;
        } else {
            $_SESSION['login_error'] = "Invalid username/email or password.";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
            exit;
        }
    } else {
        $_SESSION['login_error'] = "Invalid username/email or password.";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
        exit;
    }
    $stmt->close();
} else {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
