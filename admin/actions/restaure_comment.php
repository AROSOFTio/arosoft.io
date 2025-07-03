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

// Get the comment's parent status (if it has a parent)
$parent_status = null;
$stmt = $conn->prepare("
    SELECT c2.status 
    FROM comments c1 
    LEFT JOIN comments c2 ON c1.parent_id = c2.id 
    WHERE c1.id = ?
");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $parent_status = $row['status'];
}

// If parent is in trash, we can't restore the child comment
if ($parent_status === 'trash') {
    $_SESSION['flash_message'] = "Cannot restore comment: parent comment is in trash.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=comments');
    exit;
}

// Restore the comment to pending status for re-moderation
$stmt = $conn->prepare("UPDATE comments SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    $_SESSION['flash_message'] = "Comment restored successfully.";
    $_SESSION['flash_message_type'] = "success";
} else {
    $_SESSION['flash_message'] = "Error restoring comment: " . $conn->error;
    $_SESSION['flash_message_type'] = "error";
}

header('Location: ' . $admin_base_url . 'index.php?admin_page=comments');
exit;
