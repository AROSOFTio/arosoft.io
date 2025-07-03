<?php
// actions/contact_process.php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "index.php?page=contact");
    exit;
}

// CSRF validation
$token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($token)) {
    $_SESSION['contact_form_errors'] = ['Invalid form submission. Please try again.'];
    header("Location: " . BASE_URL . "index.php?page=contact");
    exit;
}

// Gather form data
$inquiry_type = $_POST['inquiry_type'] ?? '';
$name         = trim($_POST['contact_name'] ?? '');
$email        = trim($_POST['contact_email'] ?? '');
$subject      = trim($_POST['contact_subject'] ?? '');
$message      = trim($_POST['contact_message'] ?? '');
$service      = trim($_POST['service_select'] ?? '');
$prof_message = trim($_POST['professional_message'] ?? '');

// Validation
$errors = [];

// Inquiry type must be set
if (!in_array($inquiry_type, ['general', 'professional'], true)) {
    $errors[] = 'Please select an inquiry type.';
}

// General inquiry validation
if ($inquiry_type === 'general') {
    if ($name === '') {
        $errors[] = 'Full Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid Email Address is required.';
    }
    if ($message === '') {
        $errors[] = 'Message is required.';
    }
}

// Professional service validation
if ($inquiry_type === 'professional') {
    if ($service === '') {
        $errors[] = 'Please select a service.';
    }
    if ($prof_message === '') {
        $errors[] = 'Please describe your requirements.';
    }
    // File upload (if provided)
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error. Please try again.';
        } else {
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB
            if ($file['size'] > $max_size) {
                $errors[] = 'Attachment must be under 2MB.';
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);
            if (!in_array($mime_type, $allowed_types, true)) {
                $errors[] = 'Invalid file type. Allowed: PDF, DOC, DOCX, JPEG, PNG.';
            }
        }
    }
}

if (!empty($errors)) {
    // Preserve submitted data and errors
    $_SESSION['contact_form_data'] = $_POST;
    $_SESSION['contact_form_errors'] = $errors;
    header("Location: " . BASE_URL . "index.php?page=contact");
    exit;
}

// Compose email
$to      = CONTACT_EMAIL;
$subject_email = ($inquiry_type === 'professional')
    ? "Professional Service Request: {$service}"
    : ($subject !== '' ? $subject : "General Inquiry from {$name}");
$boundary = "----=_Part_" . md5(uniqid(time()));
$headers  = "From: \"{$name}\" <{$email}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

// Build message body
$body  = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
$body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

if ($inquiry_type === 'general') {
    $body .= "Inquiry Type: General Inquiry\r\n";
    $body .= "Name: {$name}\r\n";
    $body .= "Email: {$email}\r\n";
    if ($subject !== '') {
        $body .= "Subject: {$subject}\r\n";
    }
    $body .= "Message:\r\n{$message}\r\n";
} else {
    $body .= "Inquiry Type: Professional Service\r\n";
    $body .= "Service Requested: {$service}\r\n";
    $body .= "Name: {$name}\r\n";
    $body .= "Email: {$email}\r\n";
    $body .= "Requirements:\r\n{$prof_message}\r\n";
}

// Attachment (if professional and file uploaded)
if ($inquiry_type === 'professional' && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file_tmp_path = $_FILES['attachment']['tmp_name'];
    $file_name     = basename($_FILES['attachment']['name']);
    $file_size     = filesize($file_tmp_path);
    $file_type     = mime_content_type($file_tmp_path);
    $handle = fopen($file_tmp_path, "rb");
    $content = '';
    if ($handle) {
        $content = fread($handle, $file_size);
        fclose($handle);
        $encoded_content = chunk_split(base64_encode($content));
        $body .= "\r\n--{$boundary}\r\n";
        $body .= "Content-Type: {$file_type}; name=\"{$file_name}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$file_name}\"\r\n\r\n";
        $body .= $encoded_content . "\r\n";
    }
}

$body .= "--{$boundary}--";

// Send email
$mail_sent = mail($to, $subject_email, $body, $headers);

if ($mail_sent) {
    $_SESSION['contact_form_success'] = 'Your message has been sent successfully!';
} else {
    $_SESSION['contact_form_errors'] = ['Failed to send email. Please try again later.'];
    $_SESSION['contact_form_data'] = $_POST;
}

header("Location: " . BASE_URL . "index.php?page=contact");
exit;
