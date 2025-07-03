<?php

// Migration: Add SEO fields (meta_title, opengraph_image_url) and view_count to posts table

global $conn; // Assuming $conn is available globally from your migration runner context

$sql = "ALTER TABLE `posts`
ADD COLUMN `meta_title` VARCHAR(255) NULL DEFAULT NULL AFTER `featured_image`,
ADD COLUMN `opengraph_image_url` VARCHAR(255) NULL DEFAULT NULL AFTER `meta_keywords`,
ADD COLUMN `view_count` INT NOT NULL DEFAULT 0 AFTER `user_id`;";

// Note on placement:
// - meta_title is often good near other title/meta fields.
// - opengraph_image_url near other meta fields.
// - view_count near user_id or other tracking fields.
// The exact `AFTER` placement can be adjusted based on preference.

/*
// --- How to run this migration (example, adapt to your migration runner) ---

// 1. Ensure your database connection ($conn) is established.
//    require_once __DIR__ . '/../includes/db.php'; // Or your connection script

// 2. Execute the SQL:
// if ($conn->multi_query($sql)) {
//     do {
//         // consume all results from multi_query
//         if ($result = $conn->store_result()) {
//             $result->free();
//         }
//     } while ($conn->more_results() && $conn->next_result());
//     echo "Migration 002 applied successfully.\n";
// } else {
//     echo "Error applying migration 002: " . $conn->error . "\n";
//     error_log("Migration 002 Error: " . $conn->error . " SQL: " . $sql);
// }

// 3. Optionally, record this migration as run in a 'migrations' table or similar tracking mechanism.
//    Example:
//    $migration_name = basename(__FILE__);
//    $record_sql = "INSERT INTO migrations_log (migration_name, applied_at) VALUES ('" . $conn->real_escape_string($migration_name) . "', NOW())";
//    if (!$conn->query($record_sql)) {
//        echo "Error logging migration 002: " . $conn->error . "\n";
//    }

*/

// For direct execution if this file is run standalone (not recommended for production):
/*
if (php_sapi_name() == 'cli') {
    echo "Attempting to apply migration: 002_add_seo_and_viewcount_to_posts.php\n";

    // Minimal DB connection for direct run - replace with your actual connection logic
    $db_host = 'localhost';
    $db_user = 'your_db_user'; // Replace
    $db_pass = 'your_db_password'; // Replace
    $db_name = 'your_db_name'; // Replace

    $conn_cli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn_cli->connect_error) {
        die("Connection failed for CLI migration: " . $conn_cli->connect_error . "\n");
    }

    if ($conn_cli->multi_query($sql)) {
        do {
            if ($result = $conn_cli->store_result()) {
                $result->free();
            }
        } while ($conn_cli->more_results() && $conn_cli->next_result());
        echo "Migration 002 applied successfully via CLI.\n";
    } else {
        echo "Error applying migration 002 via CLI: " . $conn_cli->error . "\n";
    }
    $conn_cli->close();
}
*/

// If you have a migration runner, it would typically include this file and execute the $sql.
// For now, this file just defines the $sql.
// echo "Migration 002 defined. SQL: \n" . $sql . "\n"; // For debugging if needed

?>
