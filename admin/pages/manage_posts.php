<?php
<?php
// admin/pages/manage_posts.php - Rebuilt Post Management System
global $conn, $admin_base_url;

// --- Configuration & Initializations ---
// Ensure BASE_URL is properly defined (should be set in admin/index.php or an init file)
if (!defined('BASE_URL')) {
    // Fallback if not defined, though it should be
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    // Adjust $base_path if your site is not in the web root
    $base_path = '/'; // Example: '/mysite/' if URL is example.com/mysite/
    define('BASE_URL', $protocol . $domain . $base_path);
}
// $admin_base_url is expected to be set by admin/index.php
$admin_base_url = $admin_base_url ?? (rtrim(BASE_URL, '/') . '/admin/');

/**
 * Fetches posts from the database with filtering and pagination.
 *
 * @param mysqli $db_conn The database connection object.
 * @param array  $filters An associative array of filters. Expected keys:
 *                        'search_term' (string) - For title or content.
 *                        'status' (string) - e.g., 'published', 'draft'.
 *                        'category_id' (int) - ID of the category.
 *                        'author_id' (int) - ID of the author (admin_users.id).
 *                        'date_from' (string) - YYYY-MM-DD format.
 *                        'date_to' (string) - YYYY-MM-DD format.
 * @param int    $page The current page number for pagination.
 * @param int    $per_page The number of posts to display per page.
 * @return array An associative array containing:
 *               'posts' (array) - The list of post data.
 *               'total_posts' (int) - Total number of posts matching filters.
 *               'total_pages' (int) - Total number of pages.
 *               'current_page' (int) - The current page number.
 *               'per_page' (int) - Posts per page.
 */
function get_filtered_posts(mysqli $db_conn, array $filters = [], int $page = 1, int $per_page = 10): array {
    $select_columns = "SELECT p.id, p.title, p.slug, p.status, p.created_at, p.updated_at, p.view_count,
                              p.author_id, u.username AS author_name, u.full_name AS author_full_name,
                              c.id AS category_id, c.name AS category_name";
    $base_sql = " FROM posts p
                  LEFT JOIN admin_users u ON p.author_id = u.id
                  LEFT JOIN categories c ON p.category_id = c.id";

    $where_clauses = [];
    $params = [];
    $types = "";

    // Search Term (Title or Content)
    if (!empty($filters['search_term'])) {
        $where_clauses[] = "(p.title LIKE ? OR p.content LIKE ?)";
        $search_like = "%" . $filters['search_term'] . "%";
        array_push($params, $search_like, $search_like);
        $types .= "ss";
    }

    // Status Filter
    if (!empty($filters['status'])) {
        $where_clauses[] = "p.status = ?";
        array_push($params, $filters['status']);
        $types .= "s";
    }

    // Category Filter
    if (!empty($filters['category_id'])) {
        $where_clauses[] = "p.category_id = ?";
        array_push($params, $filters['category_id']);
        $types .= "i";
    }

    // Author Filter
    if (!empty($filters['author_id'])) {
        $where_clauses[] = "p.author_id = ?";
        array_push($params, $filters['author_id']);
        $types .= "i";
    }

    // Date Range Filter
    if (!empty($filters['date_from'])) {
        $where_clauses[] = "p.created_at >= ?";
        array_push($params, $filters['date_from'] . " 00:00:00"); // Start of day
        $types .= "s";
    }
    if (!empty($filters['date_to'])) {
        $where_clauses[] = "p.created_at <= ?";
        array_push($params, $filters['date_to'] . " 23:59:59"); // End of day
        $types .= "s";
    }

    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    }

    // Get total count for pagination
    $count_sql = "SELECT COUNT(p.id) as total " . $base_sql . $where_sql;
    $stmt_count = $db_conn->prepare($count_sql);
    $total_posts = 0;
    if ($stmt_count) {
        if (!empty($params)) {
            $stmt_count->bind_param($types, ...$params);
        }
        if (!$stmt_count->execute()) {
            error_log("Execute failed for post count: (" . $stmt_count->errno . ") " . $stmt_count->error);
        } else {
            $result_count = $stmt_count->get_result();
            $total_posts = $result_count ? ($result_count->fetch_assoc()['total'] ?? 0) : 0;
        }
        $stmt_count->close();
    } else {
        error_log("Prepare failed for post count: (" . $db_conn->errno . ") " . $db_conn->error);
    }

    // Get posts for the current page
    $offset = ($page - 1) * $per_page;
    $posts_sql = $select_columns . $base_sql . $where_sql . " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";

    $page_params = $params; // Copy params for main query
    $page_types = $types;   // Copy types for main query
    array_push($page_params, $per_page, $offset);
    $page_types .= "ii";

    $stmt_posts = $db_conn->prepare($posts_sql);
    $posts_data = [];
    if ($stmt_posts) {
        if (!empty($page_params)) {
            $stmt_posts->bind_param($page_types, ...$page_params);
        }
        if (!$stmt_posts->execute()) {
            error_log("Execute failed for fetching posts: (" . $stmt_posts->errno . ") " . $stmt_posts->error);
        } else {
            $result_posts = $stmt_posts->get_result();
            $posts_data = $result_posts ? $result_posts->fetch_all(MYSQLI_ASSOC) : [];
        }
        $stmt_posts->close();
    } else {
        error_log("Prepare failed for fetching posts: (" . $db_conn->errno . ") " . $db_conn->error);
    }

    $total_pages = ($per_page > 0 && $total_posts > 0) ? ceil($total_posts / $per_page) : 0;

    return [
        'posts' => $posts_data,
        'total_posts' => (int)$total_posts,
        'total_pages' => (int)$total_pages,
        'current_page' => (int)$page,
        'per_page' => (int)$per_page
    ];
}

// --- Process GET parameters for filtering ---
$posts_per_page_default = defined('POSTS_PER_PAGE') ? POSTS_PER_PAGE : 10;
$current_page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
$posts_per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : $posts_per_page_default;


$filter_values = [
    'search_term' => trim($_GET['search'] ?? ''),
    'status'      => trim($_GET['status'] ?? ''),
    'category_id' => isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null,
    'author_id'   => isset($_GET['author_id']) && $_GET['author_id'] !== '' ? (int)$_GET['author_id'] : null,
    'date_from'   => trim($_GET['date_from'] ?? ''), // Expected YYYY-MM-DD
    'date_to'     => trim($_GET['date_to'] ?? '')    // Expected YYYY-MM-DD
];

// --- Fetch data for filters ---
// Categories for dropdown
$categories_for_filter = [];
$cat_sql = "SELECT id, name FROM categories ORDER BY name ASC";
$cat_result = $conn->query($cat_sql);
if ($cat_result) {
    $categories_for_filter = $cat_result->fetch_all(MYSQLI_ASSOC);
}

// Authors for dropdown
$authors_for_filter = [];
$author_sql = "SELECT id, username, full_name FROM admin_users ORDER BY username ASC";
$author_result = $conn->query($author_sql);
if ($author_result) {
    $authors_for_filter = $author_result->fetch_all(MYSQLI_ASSOC);
}

// --- Fetch posts using the new function ---
$posts_result = get_filtered_posts($conn, $filter_values, $current_page, $posts_per_page);
$posts = $posts_result['posts'];
$total_posts = $posts_result['total_posts'];
$total_pages = $posts_result['total_pages'];

// --- Start HTML Output ---
?>
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Posts</h1>
            <p class="mt-1 text-sm text-gray-600">A list of all posts in your site including their details.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
             <a href="<?php echo esc_html($admin_base_url . 'index.php?admin_page=add_post'); ?>"
               class="inline-flex items-center justify-center rounded-md border border-transparent bg-admin-primary px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-admin-primary/90 focus:outline-none focus:ring-2 focus:ring-admin-primary focus:ring-offset-2 sm:w-auto">
                <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i> Add New Post
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="<?php echo esc_html($admin_base_url . 'index.php'); ?>" id="filter-posts-form">
            <input type="hidden" name="admin_page" value="posts">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 items-end">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" id="search" value="<?php echo esc_html($filter_values['search_term']); ?>" placeholder="Title or content..."
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm rounded-md">
                        <option value="">All Categories</option>
                        <?php foreach ($categories_for_filter as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php selected($filter_values['category_id'], (int)$cat['id']); ?>>
                                <?php echo esc_html($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="author_id" class="block text-sm font-medium text-gray-700">Author</label>
                    <select name="author_id" id="author_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm rounded-md">
                        <option value="">All Authors</option>
                        <?php foreach ($authors_for_filter as $author): ?>
                            <option value="<?php echo (int)$author['id']; ?>" <?php selected($filter_values['author_id'], (int)$author['id']); ?>>
                                <?php echo esc_html($author['full_name'] ?: $author['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm rounded-md">
                        <option value="">All Statuses</option>
                        <option value="published" <?php selected($filter_values['status'], 'published'); ?>>Published</option>
                        <option value="draft" <?php selected($filter_values['status'], 'draft'); ?>>Draft</option>
                        <?php // Add other statuses if they exist, e.g., 'pending', 'private' ?>
                    </select>
                </div>
                 <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                    <button type="submit" class="w-full bg-admin-secondary hover:bg-opacity-90 text-white font-medium py-2.5 px-4 rounded-lg shadow-md transition-colors flex items-center justify-center">
                        <i data-lucide="filter" class="w-5 h-5 mr-2"></i>Filter Posts
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 items-end">
                 <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_html($filter_values['date_from']); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_html($filter_values['date_to']); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-3 rounded-md <?php echo $_SESSION['flash_message_type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>" role="alert">
            <?php echo esc_html($_SESSION['flash_message']); ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
        </div>
    <?php endif; ?>

    <!-- Posts Table -->
    <form id="bulk-action-form" method="POST" action="<?php echo esc_html($admin_base_url . 'actions/bulk_post_actions.php'); ?>">
        <?php echo generate_csrf_input(); ?>
        <div class="mb-4 flex items-center space-x-3">
            <label for="bulk_action" class="sr-only">Bulk Action</label>
            <select name="bulk_action" id="bulk_action" class="py-2 pl-3 pr-10 border-gray-300 focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm rounded-md">
                <option value="">Bulk Actions</option>
                <option value="publish">Publish</option>
                <option value="draft">Move to Draft</option>
                <option value="delete">Delete</option>
            </select>
            <button type="submit" name="apply_bulk_action" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md text-sm shadow-sm">Apply</button>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-x-auto border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 w-12 text-center">
                            <input type="checkbox" id="select-all-posts" class="h-4 w-4 text-admin-primary border-gray-300 rounded focus:ring-admin-primary">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" name="post_ids[]" value="<?php echo (int)$post['id']; ?>" class="h-4 w-4 text-admin-primary border-gray-300 rounded focus:ring-admin-primary post-checkbox">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 hover:text-admin-primary">
                                        <a href="<?php echo esc_html($admin_base_url . 'index.php?admin_page=edit_post&id=' . (int)$post['id']); ?>">
                                            <?php echo esc_html($post['title'] ?: 'No Title'); ?>
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500">Slug: <?php echo esc_html($post['slug'] ?: 'no-slug'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo esc_html($post['author_full_name'] ?: ($post['author_name'] ?: 'N/A')); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo esc_html($post['category_name'] ?: 'Uncategorized'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php
                                        $status_val = $post['status'] ?? 'unknown';
                                        $status_class = 'bg-gray-100 text-gray-800';
                                        if ($status_val === 'published') {
                                            $status_class = 'bg-green-100 text-green-800';
                                        } elseif ($status_val === 'draft') {
                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                        }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo esc_html(ucfirst($status_val)); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo esc_html($post['view_count'] ?? 0); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo format_date($post['created_at'] ?? null, 'M j, Y'); ?><br>
                                    <span class="text-xs text-gray-400"><?php echo esc_html(ucfirst($post['status'])); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <?php
                                            $view_link = rtrim(BASE_URL, '/') . '/' . esc_html($post['slug'] ?? '');
                                            $edit_link = $admin_base_url . 'index.php?admin_page=edit_post&id=' . (int)$post['id'];
                                            $delete_action_url = $admin_base_url . 'actions/delete_post.php'; // For single delete via JS form
                                        ?>
                                        <a href="<?php echo esc_html($view_link); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 p-1" title="View Post">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="<?php echo esc_html($edit_link); ?>" class="text-indigo-600 hover:text-indigo-800 p-1" title="Edit Post">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </a>
                                        <button type="button" onclick="confirmSingleDelete(<?php echo (int)$post['id']; ?>, '<?php echo esc_js($post['title']); ?>', '<?php echo esc_js($delete_action_url); ?>')" class="text-red-600 hover:text-red-800 p-1" title="Delete Post">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i data-lucide="folder-search" class="w-12 h-12 text-gray-400 mb-3"></i>
                                    <h3 class="text-lg font-medium text-gray-700">No posts found.</h3>
                                    <p class="text-gray-500">Try adjusting your filters or <a href="<?php echo esc_html($admin_base_url . 'index.php?admin_page=add_post'); ?>" class="text-admin-primary hover:underline">add a new post</a>.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form> <!-- End bulk action form -->

    <!-- Pagination -->
    <?php if ($total_pages > 1):
        // Build query params for pagination, excluding 'paged'
        $query_params = $filter_values; // Already contains all filter values
        unset($query_params['paged']); // Remove paged if it was somehow set
        $pagination_query_string = http_build_query(array_filter($query_params)); // array_filter to remove empty params
    ?>
    <nav class="mt-6 py-3 flex items-center justify-between border-t border-gray-200" aria-label="Pagination">
        <div class="hidden sm:block">
            <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium"><?php echo ($total_posts > 0 ? (($current_page - 1) * $posts_per_page) + 1 : 0); ?></span>
                to
                <span class="font-medium"><?php echo min($current_page * $posts_per_page, $total_posts); ?></span>
                of
                <span class="font-medium"><?php echo $total_posts; ?></span>
                results
            </p>
        </div>
        <div class="flex-1 flex justify-between sm:justify-end space-x-1">
            <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_html($admin_base_url . 'index.php?admin_page=posts&paged=' . ($current_page - 1) . ($pagination_query_string ? '&' . $pagination_query_string : '')); ?>"
                   class="relative inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    Previous
                </a>
            <?php endif; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_html($admin_base_url . 'index.php?admin_page=posts&paged=' . ($current_page + 1) . ($pagination_query_string ? '&' . $pagination_query_string : '')); ?>"
                   class="ml-1 relative inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    Next
                </a>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>

</div> <!-- End container -->

<!-- Single Delete Confirmation Modal (Example) -->
<div id="singleDeleteModal" class="hidden fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="singleDeleteForm" method="POST" action="">
                <?php echo generate_csrf_input(); // Make sure this function is available ?>
                <input type="hidden" name="post_id" id="singleDeletePostId" value="">
                 <input type="hidden" name="action" value="delete_single_post"> <!-- Or similar depending on your action file -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-delete">Delete Post</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="singleDeleteModalMessage">Are you sure you want to delete this post? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeSingleDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Select/Deselect all checkboxes
    const selectAllCheckbox = document.getElementById('select-all-posts');
    const postCheckboxes = document.querySelectorAll('.post-checkbox');

    if(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function (e) {
            postCheckboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
    }

    // Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Bulk action confirmation
    const bulkActionForm = document.getElementById('bulk-action-form');
    if(bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            const bulkAction = document.getElementById('bulk_action').value;
            const selectedPosts = document.querySelectorAll('.post-checkbox:checked').length;

            if (!bulkAction) {
                e.preventDefault();
                alert('Please select a bulk action.');
                return;
            }
            if (selectedPosts === 0) {
                e.preventDefault();
                alert('Please select at least one post to apply the bulk action.');
                return;
            }
            if (bulkAction === 'delete') {
                if (!confirm('Are you sure you want to delete the selected posts? This action cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });
    }
});

// Single Delete Modal
function confirmSingleDelete(postId, postTitle, actionUrl) {
    const modal = document.getElementById('singleDeleteModal');
    const form = document.getElementById('singleDeleteForm');
    const message = document.getElementById('singleDeleteModalMessage');
    const postIdInput = document.getElementById('singleDeletePostId');

    if (modal && form && message && postIdInput) {
        form.action = actionUrl; // Set the form's action URL
        postIdInput.value = postId;
        message.innerHTML = `Are you sure you want to delete the post "<strong>${escapeHTML(postTitle)}</strong>"?<br>This action cannot be undone.`;
        modal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') { lucide.createIcons({ nodes: [modal.querySelector('[data-lucide="alert-triangle"]')] }); }
    } else {
        console.error("Single delete modal elements not found.");
        // Fallback to simple confirm if modal elements are missing
        if(confirm(`Are you sure you want to delete the post "${postTitle}"? This action cannot be undone.`)) {
            // Create a temporary form and submit if modal is broken
            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = actionUrl;

            const csrfInput = document.querySelector('input[name="csrf_token"]'); // Try to get CSRF
            if(csrfInput) {
                const csrfHiddenInput = document.createElement('input');
                csrfHiddenInput.type = 'hidden';
                csrfHiddenInput.name = 'csrf_token';
                csrfHiddenInput.value = csrfInput.value;
                tempForm.appendChild(csrfHiddenInput);
            }

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'post_id'; // Ensure this matches your backend expected param for single delete
            idInput.value = postId;
            tempForm.appendChild(idInput);

            document.body.appendChild(tempForm);
            tempForm.submit();
        }
    }
}

function closeSingleDeleteModal() {
    const modal = document.getElementById('singleDeleteModal');
    if(modal) modal.classList.add('hidden');
}

function escapeHTML(str) {
    var p = document.createElement("p");
    p.appendChild(document.createTextNode(str));
    return p.innerHTML;
}

// The 'selected' function is now expected to be globally available from includes/functions.php
</script>
