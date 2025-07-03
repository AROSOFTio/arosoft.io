<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/hash.php';

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
$upload_path = '../../uploads/';

// Check for AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function send_json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Authentication Check
if (!isset($_SESSION['admin_user_id'])) {
    if ($is_ajax) {
        send_json_response(false, "Authentication required");
    } else {
        $_SESSION['flash_message'] = "You must be logged in to perform this action.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
        exit;
    }
}

if (isset($_GET['id']) && isset($_GET['csrf_token'])) {
    $post_id = (int)$_GET['id'];
    $token = $_GET['csrf_token'];

    // CSRF Token Validation
    if (!validate_csrf_token($token)) {
        if ($is_ajax) {
            send_json_response(false, "Invalid security token");
        } else {
            $_SESSION['flash_message'] = "Invalid security token. Please try again.";
            $_SESSION['flash_message_type'] = "error";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=posts');
            exit;
        }
    }

    // Fetch post details before deletion
    $stmt_img = $conn->prepare("SELECT featured_image, title FROM posts WHERE id = ?");
    $featured_image_to_delete = null;
    $post_title = '';
    
    if ($stmt_img) {
        $stmt_img->bind_param("i", $post_id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();
        if ($result_img->num_rows === 1) {
            $post_data = $result_img->fetch_assoc();
            $featured_image_to_delete = $post_data['featured_image'];
            $post_title = $post_data['title'];
        }
        $stmt_img->close();
    }

    // Delete the post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $post_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Delete associated featured image if exists
                if (!empty($featured_image_to_delete)) {
                    $file_path = $upload_path . $featured_image_to_delete;
                    if (file_exists($file_path)) {
                        @unlink($file_path);
                    }
                }
                
                $success_message = sprintf('Post "%s" deleted successfully.', 
                                         htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'));
                
                if ($is_ajax) {
                    send_json_response(true, $success_message);
                } else {
                    $_SESSION['flash_message'] = $success_message;
                    $_SESSION['flash_message_type'] = "success";
                }
            } else {
                $error_message = "Post not found or already deleted.";
                if ($is_ajax) {
                    send_json_response(false, $error_message);
                } else {
                    $_SESSION['flash_message'] = $error_message;
                    $_SESSION['flash_message_type'] = "error";
                }
            }
        } else {
            $error_message = "Error deleting post: " . $stmt->error;
            error_log("DB Error deleting post: " . $stmt->error);
            
            if ($is_ajax) {
                send_json_response(false, $error_message);
            } else {
                $_SESSION['flash_message'] = $error_message;
                $_SESSION['flash_message_type'] = "error";
            }
        }
        $stmt->close();
    } else {
        $error_message = "Database error. Could not prepare statement for deletion.";
        error_log("DB Prepare Error deleting post: " . $conn->error);
        
        if ($is_ajax) {
            send_json_response(false, $error_message);
        } else {
            $_SESSION['flash_message'] = $error_message;
            $_SESSION['flash_message_type'] = "error";
        }
    }
} else {
    $error_message = "Invalid request: Missing post ID or security token.";
    if ($is_ajax) {
        send_json_response(false, $error_message);
    } else {
        $_SESSION['flash_message'] = $error_message;
        $_SESSION['flash_message_type'] = "error";
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

if (!$is_ajax) {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=posts');
    exit;
}
?>
