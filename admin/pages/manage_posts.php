<?php
// admin/pages/manage_posts.php
// Ensure BASE_URL is properly defined with domain
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . $domain . '/');
}
$admin_base_url = BASE_URL . 'admin/';

// Debug URL construction
error_log("BASE_URL in manage: " . BASE_URL);
error_log("Admin Base URL in manage: " . $admin_base_url);
error_log("Current URL: " . $_SERVER['REQUEST_URI']);
global $conn; 

// Temporarily enable full error reporting for this page
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$posts_per_page = defined('POSTS_PER_PAGE') ? POSTS_PER_PAGE : 10; 
$current_page_number = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
if ($current_page_number < 1) $current_page_number = 1;
$offset = ($current_page_number - 1) * $posts_per_page;

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$category_filter_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

$sql = "SELECT p.id, p.title, p.slug, p.status, p.created_at, p.updated_at, c.name as category_name 
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id";
$count_sql = "SELECT COUNT(p.id) as total 
              FROM posts p 
              LEFT JOIN categories c ON p.category_id = c.id";

$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_term)) {
    $where_clauses[] = "(p.title LIKE ? OR p.content LIKE ?)"; 
    $search_like = "%" . $search_term . "%";
    array_push($params, $search_like, $search_like);
    $types .= "ss";
}
if (!empty($status_filter)) {
    $where_clauses[] = "p.status = ?";
    array_push($params, $status_filter);
    $types .= "s";
}
if (!empty($category_filter_id)) {
    $where_clauses[] = "p.category_id = ?"; 
    array_push($params, $category_filter_id);
    $types .= "i";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
    $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
array_push($params, $posts_per_page, $offset);
$types .= "ii";
 
$stmt = $conn->prepare($sql); 
$posts = []; 
if ($stmt) {
    if (!empty($params)) { 
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log("Execute failed for manage_posts: (" . $stmt->errno . ") " . $stmt->error . " SQL: " . $sql);
    } else {
        $result = $stmt->get_result();
        $posts = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    $stmt->close();
} else {
    error_log("Prepare failed for manage_posts: (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql);
}

$stmt_count = $conn->prepare($count_sql);
$total_posts = 0;
$total_pages = 0;
if ($stmt_count) {
    $count_params_for_total = array_slice($params, 0, count($params) - 2); 
    $count_types_for_total = substr($types, 0, strlen($types) - 2); 
    if (!empty($count_params_for_total)) { 
        $stmt_count->bind_param($count_types_for_total, ...$count_params_for_total);
    }
    if (!$stmt_count->execute()) {
         error_log("Execute failed for count in manage_posts: (" . $stmt_count->errno . ") " . $stmt_count->error . " SQL: " . $count_sql);
    } else {
        $total_posts_result = $stmt_count->get_result();
        if ($total_posts_result) {
            $total_posts_row = $total_posts_result->fetch_assoc();
            $total_posts = $total_posts_row['total'] ?? 0; 
        } else {
            error_log("Get result failed for count in manage_posts: (" . $stmt_count->errno . ") " . $stmt_count->error);
        }
    }
    $total_pages = ($posts_per_page > 0 && $total_posts > 0) ? ceil($total_posts / $posts_per_page) : 0;
    $stmt_count->close();
} else {
    error_log("Prepare failed for count in manage_posts: (" . $conn->errno . ") " . $conn->error . " SQL: " . $count_sql);
}

$categories_sql_filter = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result_filter = $conn->query($categories_sql_filter);
$categories_for_filter = ($categories_result_filter && $categories_result_filter->num_rows > 0) ? $categories_result_filter->fetch_all(MYSQLI_ASSOC) : [];

?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Manage Posts</h2>
        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_post" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors flex items-center">
            <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i>Add New Post
        </a>
    </div>

    <form method="GET" action="<?php echo $admin_base_url; ?>index.php" class="mb-6 bg-white p-4 rounded-lg shadow">
        <input type="hidden" name="admin_page" value="posts">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Search Posts</label>
                <input type="text" name="search" id="search" value="<?php echo esc_html($search_term); ?>" placeholder="Title or content..." class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
            </div>
            <div>
                <label for="category_id_filter" class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category_id" id="category_id_filter" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories_for_filter as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter_id == $cat['id'] ? 'selected' : ''); ?>>
                            <?php echo esc_html($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                    <option value="">All Statuses</option>
                    <option value="published" <?php echo ($status_filter === 'published' ? 'selected' : ''); ?>>Published</option>
                    <option value="draft" <?php echo ($status_filter === 'draft' ? 'selected' : ''); ?>>Draft</option>
                </select>
            </div>
            <div class="md:pt-1">
                <button type="submit" class="w-full bg-admin-secondary hover:bg-opacity-90 text-white font-medium py-2.5 px-4 rounded-lg shadow-md transition-colors flex items-center justify-center">
                    <i data-lucide="search" class="w-5 h-5 mr-2"></i>Filter
                </button>
            </div>
        </div>
    </form>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded-md <?php echo $_SESSION['flash_message_type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>" role="alert">
            <?php echo esc_html($_SESSION['flash_message']); ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-lg rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Created</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 hover:text-admin-primary">
                                    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=edit_post&id=<?php echo (int)($post['id'] ?? 0); ?>">
                                        <?php echo esc_html($post['title'] ?? 'No Title'); ?>
                                    </a>
                                </div>
                                <div class="text-xs text-gray-500">Slug: <?php echo esc_html($post['slug'] ?? 'no-slug'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo esc_html($post['category_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($post['created_at'] ?? null, 'M j, Y H:i'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <?php
                                    $post_id_val = $post['id'] ?? null;
                                    $post_slug_val = $post['slug'] ?? null;
                                    $csrf_token_val = '';
                                    
                                    if (function_exists('generate_csrf_token')) {
                                        $csrf_token_val = generate_csrf_token();
                                    } else {
                                        // This should not happen if admin/index.php includes functions.php correctly
                                        echo "";
                                    }

                                    // View Link
                                    if (!empty($post_slug_val)) {
                                        $view_link = rtrim(BASE_URL, '/') . '/' . esc_html($post_slug_val);
                                        echo "<a href='" . esc_html($view_link) . "' target='_blank' class='text-blue-600 hover:text-blue-900 inline-block p-1' title='View Post'><i data-lucide='eye' class='w-4 h-4'></i></a>";
                                    } else {
                                        echo "<span class='text-gray-400 inline-block p-1' title='Post has no slug to view publicly (save post to generate slug if empty)'><i data-lucide='eye-off' class='w-4 h-4'></i></span>";
                                    }

                                    // Edit Link
                                    if (!empty($post_id_val)) {
                                        $edit_link = $admin_base_url . 'index.php?admin_page=edit_post&id=' . (int)$post_id_val;
                                        echo "<a href='" . esc_html($edit_link) . "' class='text-indigo-600 hover:text-indigo-900 inline-block p-1' title='Edit Post'><i data-lucide='edit-2' class='w-4 h-4'></i></a>";
                                        
                                        // Delete Link
                                        if (!empty($csrf_token_val) && $csrf_token_val !== 'CSRF_TOKEN_ERROR') { 
                                            $delete_link = $admin_base_url . 'actions/delete_post.php?id=' . (int)$post_id_val . '&csrf_token=' . $csrf_token_val;
                                            echo "<a href='" . esc_html($delete_link) . "' onclick=\"return confirm('Are you sure you want to delete this post? This action cannot be undone.');\" class='text-red-600 hover:text-red-900 inline-block p-1' title='Delete Post'><i data-lucide='trash-2' class='w-4 h-4'></i></a>";
                                        } else {
                                            echo "";
                                        }
                                    } else {
                                        echo "";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                            No posts found matching your criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): 
        $query_params = [];
        if (!empty($search_term)) $query_params['search'] = $search_term;
        if (!empty($status_filter)) $query_params['status'] = $status_filter;
        if (!empty($category_filter_id)) $query_params['category_id'] = $category_filter_id;
        $pagination_query_string = http_build_query($query_params);
    ?>
    <nav class="mt-6 py-3 flex items-center justify-between border-t border-gray-200" aria-label="Pagination">
        <div class="hidden sm:block">
            <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium"><?php echo ($total_posts > 0 ? $offset + 1 : 0); ?></span>
                to
                <span class="font-medium"><?php echo min($offset + $posts_per_page, $total_posts); ?></span>
                of
                <span class="font-medium"><?php echo $total_posts; ?></span>
                results
            </p>
        </div>
        <div class="flex-1 flex justify-between sm:justify-end space-x-1">
            <?php if ($current_page_number > 1): ?>
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=posts&paged=<?php echo $current_page_number - 1; ?>&<?php echo $pagination_query_string; ?>" class="relative inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    Previous
                </a>
            <?php endif; ?>
            <?php if ($current_page_number < $total_pages): ?>
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=posts&paged=<?php echo $current_page_number + 1; ?>&<?php echo $pagination_query_string; ?>" class="relative inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    Next
                </a>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>

</div>
