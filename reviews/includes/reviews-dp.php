// public_html/reviews/includes/reviews_db.php (EXAMPLE)
<?php
// Database connection constants for the NEW REVIEWS DATABASE
define('REVIEWS_DB_HOST', 'localhost');
define('REVIEWS_DB_USER', 'user_for_reviews_db'); // New DB User
define('REVIEWS_DB_PASS', 'password_for_reviews_db'); // New DB Password
define('REVIEWS_DB_NAME', 'name_of_your_new_reviews_db'); // New DB Name

// Create connection
$reviews_conn = new mysqli(REVIEWS_DB_HOST, REVIEWS_DB_USER, REVIEWS_DB_PASS, REVIEWS_DB_NAME);

// Check connection
if ($reviews_conn->connect_error) {
    die("Reviews DB Connection failed: " . $reviews_conn->connect_error);
}

$reviews_conn->set_charset("utf8mb4");
?>