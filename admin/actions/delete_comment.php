<?php
require_once '../../includes/init.php';
require_once '../auth/check_auth.php';

// Verify CSRF token
if (!verify_csrf_token($_GET['token'])) {
    $_SESSION['flash_message'] = "Invalid security token.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=comments');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "Invalid comment ID.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=comments');
    exit;
}

$comment_id = (int)$_GET['id'];

// Start transaction
$conn->begin_transaction();

try {
    // First, delete any child comments (replies)
    $stmt = $conn->prepare("DELETE FROM comments WHERE parent_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();

    // Then delete the comment itself
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['flash_message'] = "Comment deleted permanently.";
    $_SESSION['flash_message_type'] = "success";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $_SESSION['flash_message'] = "Error deleting comment: " . $e->getMessage();
    $_SESSION['flash_message_type'] = "error";
}

header('Location: ' . $admin_base_url . 'index.php?admin_page=comments');
exit;
