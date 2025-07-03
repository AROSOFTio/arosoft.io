<?php
// migrations/001_add_parent_id_to_categories.php
// This script is intended to be run from the command line or a migration tool.
// It adds a parent_id column to the categories table.

// Attempt to establish database connection
// This assumes db.php is correctly configured and $conn is established.
$db_file_path = __DIR__ . '/../includes/db.php';

if (!file_exists($db_file_path)) {
    die("Error: Database configuration file not found at " . $db_file_path . "\n");
}

require_once $db_file_path;

global $conn;

if (!isset($conn) || $conn->connect_error) {
    $error_message = isset($conn) ? $conn->connect_error : "Unknown error";
    die("Error: Failed to connect to the database. " . $error_message . "\n");
}

echo "Successfully connected to the database.\n";

// SQL to add the parent_id column and the foreign key constraint.
// Note: Running ALTER TABLE with ADD COLUMN and ADD CONSTRAINT in a single statement
// is generally fine, but some very old MySQL versions might have issues.
// Splitting them would be a more conservative approach if compatibility is a concern.
$sql = "ALTER TABLE `categories`
        ADD COLUMN `parent_id` INT NULL DEFAULT NULL AFTER `description`;";

echo "Executing SQL to add column: \n$sql\n";

if ($conn->query($sql) === TRUE) {
    echo "Column `parent_id` added successfully to `categories` table.\n";

    // Now, add the foreign key constraint.
    // This is separated to ensure the column exists first and for clarity.
    $sql_fk = "ALTER TABLE `categories`
               ADD CONSTRAINT `fk_category_parent`
                   FOREIGN KEY (`parent_id`)
                   REFERENCES `categories`(`id`)
                   ON DELETE SET NULL
                   ON UPDATE CASCADE;";

    echo "Executing SQL to add foreign key constraint: \n$sql_fk\n";

    if ($conn->query($sql_fk) === TRUE) {
        echo "Migration successful: Foreign key `fk_category_parent` created successfully on `parent_id`.\n";
    } else {
        echo "Error adding foreign key constraint `fk_category_parent`: " . $conn->error . "\n";
        echo "Please ensure the `categories` table exists and has an `id` column as primary key.\n";
    }

} else {
    echo "Error adding column `parent_id` to `categories` table: " . $conn->error . "\n";
    echo "SQL attempted: \n$sql\n";
}

// Close the connection
if (isset($conn)) {
    $conn->close();
    echo "Database connection closed.\n";
}
?>
