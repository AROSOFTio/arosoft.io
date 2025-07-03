<?php
// Set content type to XML
header('Content-Type: application/xml; charset=UTF-8');

// Include necessary files (adjust path if BASE_URL isn't set correctly in functions.php)
require_once 'includes/functions.php'; // For BASE_URL, esc_html, and format_date
require_once 'includes/db.php';     // For $conn database connection

// Ensure BASE_URL is defined if it's not already from included files
defined('BASE_URL') or define('BASE_URL', '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo rtrim(BASE_URL, '/'); ?>/</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo rtrim(BASE_URL, '/'); ?>/blog</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo rtrim(BASE_URL, '/'); ?>/about</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo rtrim(BASE_URL, '/'); ?>/services_overview</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo rtrim(BASE_URL, '/'); ?>/contact</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?php echo rtrim(BASE_URL, '/'); ?>/portfolio</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo rtrim(BASE_URL, '/'); ?>/privacy</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.5</priority>
    </url>

    <?php
    // Fetch all published posts from the database to include in the sitemap
    $posts_query = $conn->query("SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY updated_at DESC");

    if ($posts_query) {
        while ($post = $posts_query->fetch_assoc()):
            $post_url = rtrim(BASE_URL, '/') . '/' . esc_html($post['slug']);
            $last_modified = date('Y-m-d', strtotime($post['updated_at']));
            ?>
    <url>
        <loc><?php echo $post_url; ?></loc>
        <lastmod><?php echo $last_modified; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php
        endwhile;
        $posts_query->free_result(); // Free result set
    } else {
        error_log("Sitemap generation: Error fetching posts: " . $conn->error);
    }

    // Close database connection
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    ?>
</urlset>