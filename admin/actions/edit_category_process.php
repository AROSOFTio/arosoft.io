<?php
// admin/actions/edit_category_process.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_category']) && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_category&id=' . $category_id);
        exit;
    }

    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    // Handle parent_id: if empty or '0', treat as NULL
    $parent_id_input = $_POST['parent_id'] ?? null;
    $parent_id = (!empty($parent_id_input) && $parent_id_input !== '0') ? (int)$parent_id_input : null;
    $errors = [];

    if (empty($name)) { $errors[] = "Category name is required."; }
    if (empty($slug)) { $slug = slugify($name); } else { $slug = slugify($slug); }

    // Check slug uniqueness (excluding current category)
    $stmt_check_slug = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    if ($stmt_check_slug) {
        $stmt_check_slug->bind_param("si", $slug, $category_id);
        $stmt_check_slug->execute();
        $stmt_check_slug->store_result();
        if ($stmt_check_slug->num_rows > 0) { $errors[] = "Category slug already exists ('" . esc_html($slug) . "'). Choose a different name or slug."; }
        $stmt_check_slug->close();
    } else {
        $errors[] = "Database error preparing slug uniqueness check.";
        error_log("DB Prepare Error for slug check: " . $conn->error);
    }


    // Self-referencing check: A category cannot be its own parent.
    if ($parent_id === $category_id) {
        $errors[] = "A category cannot be set as its own parent. Please choose a different parent or select 'No Parent'.";
        // If deciding to silently set parent_id to null instead of erroring, uncomment next line and remove error.
        // $parent_id = null;
    }

    // Check if parent_id exists if provided, is valid, and is not the category itself
    if ($parent_id !== null && $parent_id !== $category_id) {
        $stmt_check_parent = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        if ($stmt_check_parent) {
            $stmt_check_parent->bind_param("i", $parent_id);
            $stmt_check_parent->execute();
            $stmt_check_parent->store_result();
            if ($stmt_check_parent->num_rows == 0) {
                $errors[] = "Selected parent category (ID: " . esc_html($parent_id) . ") does not exist.";
            }
            $stmt_check_parent->close();
        } else {
            $errors[] = "Database error preparing parent category existence check.";
            error_log("DB Prepare Error for parent existence check: " . $conn->error);
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve form data
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_category&id=' . $category_id);
        exit;
    }

    $stmt_update = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ? WHERE id = ?");
    if ($stmt_update) {
        // Bind parameters: s (name), s (slug), s (description), i (parent_id), i (category_id)
        // For parent_id, if it's null, it will be bound as NULL.
        $stmt_update->bind_param("sssii", $name, $slug, $description, $parent_id, $category_id);
        if ($stmt_update->execute()) {
            $_SESSION['flash_message'] = "Category updated successfully!";
            $_SESSION['flash_message_type'] = "success";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
            exit;
        } else {
            error_log("DB Error updating category: (" . $stmt_update->errno . ") " . $stmt_update->error);
            $_SESSION['flash_message'] = "Error updating category. DB Code: " . $stmt_update->errno;
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt_update->close();
    } else {
        error_log("DB Prepare Error updating category: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for category update: " . esc_html($conn->error);
        $_SESSION['flash_message_type'] = "error";
    }

    // If execution reaches here, it's likely due to a failed execute() or prepare()
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_category&id=' . $category_id);
    exit;

} else {
    // Redirect if not POST, or if submit_category or id is not set.
    $_SESSION['flash_message'] = "Invalid request.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
    exit;
}
?>
