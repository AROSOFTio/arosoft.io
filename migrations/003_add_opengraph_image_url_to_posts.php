<?php

// Migration: Add opengraph_image_url to posts table.
// This migration is created to specifically address the fatal error:
// "Unknown column 'opengraph_image_url' in 'INSERT INTO'"
// which indicates that migration 002 (which should have added this column)
// was likely not run or failed to add this specific column.

// This script provides the SQL to add the column with a recommended length.
// The user is expected to run this SQL against their database.

$sql_command = "ALTER TABLE `posts`
ADD COLUMN `opengraph_image_url` VARCHAR(2048) NULL DEFAULT NULL COMMENT 'Open Graph image URL for social sharing' AFTER `meta_keywords`;";

// Note: The `AFTER meta_keywords` clause assumes that the `meta_keywords` column (also part of migration 002) exists.
// If migration 002 was not run at all, `meta_keywords` would also be missing, and this `AFTER` clause would cause an error.
// A safer version if the state of `meta_keywords` is unknown would be to omit `AFTER meta_keywords`,
// which would add the column to the end of the table definition:
// $sql_command_safer = "ALTER TABLE `posts` ADD COLUMN `opengraph_image_url` VARCHAR(2048) NULL DEFAULT NULL COMMENT 'Open Graph image URL for social sharing';";
// Choose the command appropriate for your situation. If `meta_keywords` exists, the first one is fine.
// If unsure, use the second one, or ensure migration 002 is fully run first.

echo "Migration 003: SQL to add 'opengraph_image_url' column to 'posts' table.\n\n";
echo "Please execute the following SQL command against your database:\n";
echo "-----------------------------------------------------------------\n";
echo $sql_command . "\n";
echo "-----------------------------------------------------------------\n\n";
echo "If the 'meta_keywords' column does not exist (i.e., migration 002 was not run or was incomplete), \n";
echo "you might need to run migration 002 first, or use this alternative command that adds the column to the end of the table:\n";
echo "ALTER TABLE `posts` ADD COLUMN `opengraph_image_url` VARCHAR(2048) NULL DEFAULT NULL COMMENT 'Open Graph image URL for social sharing';\n";

// This PHP file itself does not execute the SQL. It provides the command for manual execution
// or for a separate migration runner script.

?>
