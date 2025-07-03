<?php
// admin/actions/add_category_process.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_category'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=add_category');
        exit;
    }

    $name = trim($_POST['category_name']);
    $slug = trim($_POST['category_slug']);
    $description = trim($_POST['category_description']);
    // Handle parent_id: if empty or '0', treat as NULL
    $parent_id_input = $_POST['parent_id'] ?? null;
    $parent_id = (!empty($parent_id_input) && $parent_id_input !== '0') ? (int)$parent_id_input : null;
    $errors = [];

    if (empty($name)) { $errors[] = "Category name is required."; }
    if (empty($slug)) { $slug = slugify($name); } else { $slug = slugify($slug); }

    // Check slug uniqueness
    $stmt_check = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt_check->bind_param("s", $slug);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) { $errors[] = "Category slug already exists. Choose a different name or slug."; }
    $stmt_check->close();

    // Check if parent_id exists if provided
    if ($parent_id !== null) {
        $stmt_check_parent = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        if ($stmt_check_parent) {
            $stmt_check_parent->bind_param("i", $parent_id);
            $stmt_check_parent->execute();
            $stmt_check_parent->store_result();
            if ($stmt_check_parent->num_rows == 0) {
                $errors[] = "Selected parent category does not exist.";
            }
            $stmt_check_parent->close();
        } else {
            $errors[] = "Database error preparing parent category check.";
            error_log("DB Prepare Error for parent check: " . $conn->error);
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $admin_base_url . 'index.php?admin_page=add_category');
        exit;
    }

    $stmt_insert = $conn->prepare("INSERT INTO categories (name, slug, description, parent_id) VALUES (?, ?, ?, ?)");
    if ($stmt_insert) {
        $stmt_insert->bind_param("sssi", $name, $slug, $description, $parent_id);
        if ($stmt_insert->execute()) {
            $_SESSION['flash_message'] = "Category created successfully!";
            $_SESSION['flash_message_type'] = "success";
        } else {
            error_log("DB Error adding category: " . $stmt_insert->error);
            $_SESSION['flash_message'] = "Error creating category: " . $stmt_insert->error;
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt_insert->close();
    } else {
        error_log("DB Prepare Error adding category: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for category.";
        $_SESSION['flash_message_type'] = "error";
    }
    header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
    exit;
} else {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
    exit;
}
?>
