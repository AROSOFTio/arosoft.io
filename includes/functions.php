<?php
// includes/functions.php

/**
 * Loads site settings from a JSON file or returns defaults.
 *
 * @return array Associative array of site settings.
 */
function load_site_settings(): array {
    $settings_file_path = __DIR__ . '/../config/site_settings.json';

    $default_settings = [
        'site_name'          => 'DIPUG',
        'site_tagline'       => 'Digital Innovation and Programming',
        'posts_per_page'     => 10,
        'contact_email'      => 'info@dipug.com',
        'footer_copyright'   => '&copy; {year} dipug.com. All Rights Reserved.',
    ];

    if (!file_exists($settings_file_path)) {
        return $default_settings;
    }

    $json_data = file_get_contents($settings_file_path);
    if ($json_data === false) {
        return $default_settings;
    }

    $loaded_settings = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($loaded_settings)) {
        return $default_settings;
    }

    return array_merge($default_settings, $loaded_settings);
}

/**
 * Safely escape a string for HTML output.
 *
 * @param string|null $string
 * @return string
 */
function esc_html(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Safely escape a URL component.
 *
 * @param string $string
 * @return string
 */
function esc_url(string $string): string {
    return filter_var($string, FILTER_SANITIZE_URL);
}

/**
 * Convert text to a URL-friendly slug.
 *
 * @param string $text
 * @param string $divider
 * @return string
 */
function slugify(string $text, string $divider = '-'): string {
    // Replace non-letter/digit by divider
    $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

    // Transliterate to ASCII if possible
    if (function_exists('iconv')) {
        $text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }

    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // Trim dividers from ends
    $text = trim($text, $divider);

    // Remove duplicate dividers
    $text = preg_replace('~-+~', $divider, $text);

    $text = strtolower($text);

    if (empty($text)) {
        // Fallback to random string
        return 'n-a-' . substr(md5(time() . random_bytes(4)), 0, 6);
    }

    return $text;
}

/**
 * Generate a short excerpt from content, stripping HTML tags.
 *
 * @param string $content
 * @param int    $length
 * @param string $suffix
 * @return string
 */
function generate_excerpt(string $content, int $length = 200, string $suffix = '...'): string {
    $content_no_tags = strip_tags($content);

    if (mb_strlen($content_no_tags) <= $length) {
        return $content_no_tags;
    }

    $excerpt = mb_substr($content_no_tags, 0, $length);
    $last_space = mb_strrpos($excerpt, ' ');
    if ($last_space !== false) {
        $excerpt = mb_substr($excerpt, 0, $last_space);
    }

    return $excerpt . $suffix;
}

/**
 * Format a date string into a human-readable format.
 *
 * @param string|null $date_string
 * @param string      $format
 * @return string
 */
function format_date(?string $date_string, string $format = 'F j, Y'): string {
    if (empty($date_string) || $date_string === '0000-00-00 00:00:00') {
        return 'N/A';
    }

    try {
        $date = new DateTime($date_string);
        return $date->format($format);
    } catch (Exception $e) {
        return 'N/A';
    }
}

/**
 * Generate or retrieve a CSRF token stored in session.
 *
 * @return string
 */
function generate_csrf_token(): string {
    if (
        empty($_SESSION['csrf_token'])
        || !isset($_SESSION['csrf_token_time'])
        || (time() - $_SESSION['csrf_token_time'] > 1800)
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a given CSRF token against the one stored in session.
 *
 * @param string $token
 * @return bool
 */
function validate_csrf_token(string $token): bool {
    if (
        isset($_SESSION['csrf_token'])
        && isset($_SESSION['csrf_token_time'])
        && (time() - $_SESSION['csrf_token_time'] <= 1800)
        && hash_equals($_SESSION['csrf_token'], $token)
    ) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return true;
    }

    return false;
}

/**
 * Return a hidden HTML input field containing the CSRF token.
 *
 * @return string
 */
function generate_csrf_input(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . esc_html($token) . '">';
}

/**
 * Handle file uploads securely.
 *
 * @param array  $file_input    The $_FILES['input_name'] array.
 * @param string $upload_dir    Directory to save uploaded file.
 * @param array  $allowed_types MIME types allowed.
 * @param int    $max_size      Maximum allowed size in bytes.
 * @return string|array         New filename on success, or array of errors.
 */
function handle_file_upload(
    array $file_input,
    string $upload_dir,
    array $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    int $max_size = 2097152 // 2 MB
) {
    $errors = [];

    if (!isset($file_input['error']) || is_array($file_input['error'])) {
        $errors[] = 'Invalid file upload parameters.';
        return $errors;
    }

    switch ($file_input['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ''; // No file uploaded
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'Exceeded filesize limit.';
            return $errors;
        default:
            $errors[] = 'Unknown file upload error. Code: ' . $file_input['error'];
            return $errors;
    }

    if ($file_input['size'] > $max_size) {
        $errors[] = 'Exceeded filesize limit (Max ' . ($max_size / 1048576) . ' MB).';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_input['tmp_name']);
    if (!in_array($mime_type, $allowed_types, true)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
    }

    if (!empty($errors)) {
        return $errors;
    }

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $errors[] = 'Upload directory does not exist and could not be created.';
            return $errors;
        }
    }

    if (!is_writable($upload_dir)) {
        $errors[] = 'Upload directory is not writable.';
        return $errors;
    }

    $file_extension = strtolower(pathinfo($file_input['name'], PATHINFO_EXTENSION));
    $safe_filename_base = slugify(pathinfo($file_input['name'], PATHINFO_FILENAME));
    $new_filename = $safe_filename_base . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $destination = rtrim($upload_dir, '/') . '/' . $new_filename;

    if (!move_uploaded_file($file_input['tmp_name'], $destination)) {
        $errors[] = 'Failed to move uploaded file.';
        return $errors;
    }

    return $new_filename;
}

/**
 * Generate a secure preview token for a post and store it in session.
 *
 * @param int $post_id The ID of the post.
 * @return string      Generated token.
 */
function generate_preview_token(int $post_id): string {
    $token = bin2hex(random_bytes(16));
    $_SESSION['preview_token_' . $post_id] = $token;
    $_SESSION['preview_token_time_' . $post_id] = time();
    return $token;
}

/**
 * Validate a preview token for a post.
 *
 * @param int    $post_id The ID of the post.
 * @param string $token   The token to validate.
 * @return bool           True if valid, false otherwise.
 */
function validate_preview_token(int $post_id, string $token): bool {
    $token_key = 'preview_token_' . $post_id;
    $time_key = 'preview_token_time_' . $post_id;

    if (
        isset($_SESSION[$token_key], $_SESSION[$time_key])
        && (time() - $_SESSION[$time_key] < 3600) // valid for 1 hour
        && hash_equals($_SESSION[$token_key], $token)
    ) {
        // Optionally consume the token after one use:
        // unset($_SESSION[$token_key], $_SESSION[$time_key]);
        return true;
    }

    return false;
}
