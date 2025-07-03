<?php
// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    set_flash_message('Please log in to access this area.', 'error');
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}

// Check if user exists in database
$auth_check_stmt = $conn->prepare("SELECT id, status FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
$auth_check_stmt->bind_param('i', $_SESSION['admin_user_id']);
$auth_check_stmt->execute();
$auth_result = $auth_check_stmt->get_result();

if (!$auth_result || !$auth_result->fetch_assoc()) {
    // User not found or not admin, clear session and redirect
    session_destroy();
    session_start();
    set_flash_message('Your session has expired. Please log in again.', 'error');
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}

// Set last activity time
$_SESSION['last_activity'] = time();

// Optional: Check for session timeout (e.g., 2 hours)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_destroy();
    session_start();
    set_flash_message('Your session has timed out. Please log in again.', 'error');
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}
