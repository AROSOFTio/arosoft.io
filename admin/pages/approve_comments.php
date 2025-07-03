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

$stmt = $conn->prepare("UPDATE comments SET status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    $_SESSION['flash_message'] = "Comment approved successfully.";
    $_SESSION['flash_message_type'] = "success";
} else {
    $_SESSION['flash_message'] = "Error approving comment: " . $conn->error;
    $_SESSION['flash_message_type'] = "error";
}

header('Location: ' . $admin_base_url . 'index.php?admin_page=comments');
exit;
