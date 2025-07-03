<?php
// admin/actions/save_settings_process.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/functions.php'; // For esc_html, CSRF, slugify

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
$config_dir = __DIR__ . '/../../config/';
$settings_file_path = $config_dir . 'site_settings.json';
$upload_dir = __DIR__ . '/../../uploads/theme/';

if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_role'] !== 'admin') {
    $_SESSION['flash_message'] = "You do not have permission to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
        exit;
    }

    $errors = [];
    // Load existing settings to preserve them and to get current logo value
    $current_settings = [];
    $default_settings_keys = [ // Define keys that should always exist
        'site_name' => '', 'site_tagline' => '', 'site_description' => '',
        'posts_per_page' => 10, 'contact_email' => '', 'footer_copyright' => '',
        'site_logo' => null, 'seo_site_title' => '',
        'seo_default_description' => '', 'seo_og_image' => ''
    ];

    if (file_exists($settings_file_path)) {
        $json_data = file_get_contents($settings_file_path);
        $loaded_settings = json_decode($json_data, true);
        if (is_array($loaded_settings)) {
            $current_settings = array_merge($default_settings_keys, $loaded_settings);
        } else {
            $current_settings = $default_settings_keys; // Reset if JSON is invalid
        }
    } else {
        $current_settings = $default_settings_keys; // Use defaults if file doesn't exist
    }


    // --- Process Text-Based Settings ---
    $site_name = trim($_POST['site_name']);
    if (empty($site_name)) {
        $errors[] = "Site Name cannot be empty.";
    } else {
        $current_settings['site_name'] = $site_name;
    }

    $site_tagline = trim($_POST['site_tagline']);
    if (empty($site_tagline)) {
        $errors[] = "Site Tagline cannot be empty.";
    } else {
        $current_settings['site_tagline'] = $site_tagline;
    }
    
    $current_settings['site_description'] = trim($_POST['site_description'] ?? '');

    $contact_email = trim($_POST['contact_email']);
    if (empty($contact_email)) {
        $errors[] = "Contact Email cannot be empty.";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid Contact Email format.";
    } else {
        $current_settings['contact_email'] = $contact_email;
    }

    $posts_per_page = filter_var($_POST['posts_per_page'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 50]]);
    if ($posts_per_page === false) {
        $errors[] = "Posts Per Page must be a number between 1 and 50.";
    } else {
        $current_settings['posts_per_page'] = $posts_per_page;
    }
    
    $current_settings['footer_copyright'] = trim($_POST['footer_copyright'] ?? '');

    // SEO Settings
    $current_settings['seo_site_title'] = trim($_POST['seo_site_title'] ?? '');
    $current_settings['seo_default_description'] = trim($_POST['seo_default_description'] ?? '');

    $seo_og_image = trim($_POST['seo_og_image'] ?? '');
    if (!empty($seo_og_image) && !filter_var($seo_og_image, FILTER_VALIDATE_URL)) {
        $errors[] = "SEO Open Graph Image URL is not a valid URL.";
    } else {
        $current_settings['seo_og_image'] = $seo_og_image;
    }


    // --- Logo Handling ---
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
            $errors[] = "Failed to create logo upload directory at 'uploads/theme/'. Please check permissions.";
        }
    }

    if (isset($_POST['remove_site_logo']) && $_POST['remove_site_logo'] == '1') {
        if (!empty($current_settings['site_logo'])) {
            $old_logo_path = $upload_dir . $current_settings['site_logo'];
            if (file_exists($old_logo_path)) {
                if (!@unlink($old_logo_path)) {
                     $errors[] = "Could not delete the old logo file. Please check permissions for 'uploads/theme/'.";
                }
            }
            $current_settings['site_logo'] = null;
        }
    }

    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        // Proceed only if upload directory is writable, otherwise error was already added
        if (is_dir($upload_dir) && is_writable($upload_dir)) {
            $file = $_FILES['site_logo'];
            $allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
            $max_size = 1 * 1024 * 1024; // 1MB

            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Invalid logo file type. Allowed: PNG, JPG, GIF, SVG. Detected: " . esc_html($file['type']);
            } elseif ($file['size'] > $max_size) {
                $errors[] = "Logo file is too large. Maximum size is 1MB.";
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename_base = function_exists('slugify') ? slugify(pathinfo($file['name'], PATHINFO_FILENAME)) : preg_replace("/[^a-zA-Z0-9_-]/", "", pathinfo($file['name'], PATHINFO_FILENAME));
                if(empty($filename_base)) $filename_base = 'logo';
                $new_filename = time() . '_' . $filename_base . '.' . $extension;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    if (!empty($current_settings['site_logo']) && $current_settings['site_logo'] !== $new_filename) {
                        $old_logo_path_on_new_upload = $upload_dir . $current_settings['site_logo'];
                        if (file_exists($old_logo_path_on_new_upload)) {
                            if(!@unlink($old_logo_path_on_new_upload)){
                                error_log("Could not delete old logo file: " . $old_logo_path_on_new_upload);
                            }
                        }
                    }
                    $current_settings['site_logo'] = $new_filename;
                } else {
                    $errors[] = "Failed to move uploaded logo to 'uploads/theme/'. Check directory permissions.";
                }
            }
        } else if (!in_array("Failed to create logo upload directory at 'uploads/theme/'. Please check permissions.", $errors)) {
            // Add error only if it wasn't added before about directory creation
            $errors[] = "Cannot upload logo because the upload directory ('uploads/theme/') is not writable or does not exist.";
        }
    } elseif (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload.",
        ];
        $error_message = $upload_errors[$_FILES['site_logo']['error']] ?? "Unknown upload error (Code: ".$_FILES['site_logo']['error'].")";
        $errors[] = "Error during logo upload: " . $error_message;
    }

    // --- Finalize and Save ---
    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
        exit;
    }

    if (!is_dir($config_dir)) {
        if (!mkdir($config_dir, 0755, true) && !is_dir($config_dir)) {
            $_SESSION['flash_message'] = "Configuration directory ('config/') could not be created. Please check permissions.";
            $_SESSION['flash_message_type'] = "error";
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
            exit;
        }
    }
    
    if (file_put_contents($settings_file_path, json_encode($current_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        $_SESSION['flash_message'] = "Settings saved successfully!";
        $_SESSION['flash_message_type'] = "success";
    } else {
        error_log("Failed to write settings to: " . $settings_file_path);
        $_SESSION['flash_message'] = "Error saving settings. Could not write to 'config/site_settings.json'. Please check file/directory permissions.";
        $_SESSION['flash_message_type'] = "error";
    }

    header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
    exit;

} else {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
    exit;
}
?>
