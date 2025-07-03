<?php
// db.php - Database Connection Script for DIPUG

// Database connection constants
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_USER') or define('DB_USER', 'u662439561_main5_');
defined('DB_PASS') or define('DB_PASS', 'XpGmn&9a');
defined('DB_NAME') or define('DB_NAME', 'u662439561_Main5_');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . 
        "<br><br><strong>Troubleshooting Tips:</strong>" .
        "<ul>" .
        "<li>Ensure MySQL server is running.</li>" .
        "<li>Check database credentials in db.php.</li>" .
        "<li>Ensure database '" . DB_NAME . "' exists.</li>" .
        "<li>Verify user permissions.</li>" .
        "</ul>");
}

// Set charset for full Unicode support
$conn->set_charset("utf8mb4");

// Optional: You can log or echo connection success during development
// echo "Database connected successfully.";
?>
