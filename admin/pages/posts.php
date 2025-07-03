<?php
// Pagination settings
$posts_per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $posts_per_page;

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['s']) ? trim($_GET['s']) : '';

// Build the SQL query
$where_clauses = array();
$params = array();
$param_types = '';

if ($status_filter && $status_filter !== 'all') {
    $where_clauses[] = "p.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}

if ($search_query) {
    $where_clauses[] = "(p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $param_types .= 'ss';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total posts for pagination
$count_sql = "SELECT COUNT(*) as total FROM posts p {$where_sql}";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get posts
$posts_sql = "
    SELECT p.*, c.name as category_name, 
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'approved') as comment_count
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    {$where_sql}
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($posts_sql);
if (!empty($params)) {
    $params[] = $posts_per_page;
    $params[] = $offset;
    $param_types .= 'ii';
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param('ii', $posts_per_page, $offset);
}
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$categories_sql = "SELECT id, name FROM categories ORDER BY name ASC";
$categories = $conn->query($categories_sql)->fetch_all(MYSQLI_ASSOC);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Posts</h1>
            <p class="mt-1 text-sm text-gray-600">
                Manage your blog posts, drafts, and categories
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_post" 
               class="btn-hover-effect inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-admin-primary hover:bg-admin-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary">
                <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                Add New Post
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
        <div class="p-6">
            <form action="" method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <input type="hidden" name="admin_page" value="posts">
                
                <!-- Search -->
                <div class="flex-1">
                    <label for="search" class="sr-only">Search posts</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input type="search" name="s" id="search" 
                               class="focus:ring-admin-primary focus:border-admin-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                               placeholder="Search posts..."
                               value="<?php echo esc_html($search_query); ?>">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="sm:w-40">
                    <label for="status" class="sr-only">Filter by status</label>
                    <select id="status" name="status" 
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm rounded-md">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="sm:w-48">
                    <label for="category" class="sr-only">Filter by category</label>
                    <select id="category" name="category" 
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm rounded-md">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter === $category['id'] ? 'selected' : ''; ?>>
                                <?php echo esc_html($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Posts Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <?php if (!empty($posts)): ?>
            <div class="min-w-full divide-y divide-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Title
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Author
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($posts as $post): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-md object-cover" 
                                                     src="<?php echo BASE_URL . 'uploads/' . esc_html($post['featured_image']); ?>" 
                                                     alt="">
                                            </div>
                                        <?php endif; ?>
                                        <div class="<?php echo !empty($post['featured_image']) ? 'ml-4' : ''; ?>">
                                            <div class="text-sm font-medium text-gray-900">
                                                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=edit_post&id=<?php echo $post['id']; ?>" 
                                                   class="hover:text-admin-primary">
                                                    <?php echo esc_html($post['title']); ?>
                                                </a>
                                            </div>
                                            <?php if ($post['comment_count'] > 0): ?>
                                                <div class="text-sm text-gray-500">
                                                    <i data-lucide="message-circle" class="w-4 h-4 inline"></i>
                                                    <?php echo $post['comment_count']; ?> comments
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Admin</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $post['category_name'] ? esc_html($post['category_name']) : 'Uncategorized'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $post['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($post['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-3">
                                        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=edit_post&id=<?php echo $post['id']; ?>" 
                                           class="text-admin-primary hover:text-admin-primary/80"
                                           title="Edit">
                                            <i data-lucide="edit-2" class="w-5 h-5"></i>
                                        </a>
                                        <a href="<?php echo rtrim(BASE_URL, '/') . '/' . urlencode($post['slug']); ?>" 
                                           target="_blank"
                                           class="text-gray-400 hover:text-gray-500"
                                           title="View">
                                            <i data-lucide="eye" class="w-5 h-5"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $post['id']; ?>)" 
                                                class="text-red-500 hover:text-red-600"
                                                title="Delete">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($current_page > 1): ?>
                            <a href="?admin_page=posts&paged=<?php echo $current_page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?admin_page=posts&paged=<?php echo $current_page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium"><?php echo $offset + 1; ?></span>
                                to
                                <span class="font-medium"><?php echo min($offset + $posts_per_page, $total_posts); ?></span>
                                of
                                <span class="font-medium"><?php echo $total_posts; ?></span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($current_page > 1): ?>
                                    <a href="?admin_page=posts&paged=<?php echo $current_page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i data-lucide="chevron-left" class="h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?admin_page=posts&paged=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i === $current_page ? 'text-admin-primary bg-admin-primary/10 border-admin-primary' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; 
                                
                                if ($end_page < $total_pages) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?admin_page=posts&paged=<?php echo $current_page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i data-lucide="chevron-right" class="h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-12">
                <i data-lucide="file-text" class="mx-auto h-12 w-12 text-gray-400"></i>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No posts found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    <?php echo $search_query ? 'Try adjusting your search or filter criteria.' : 'Get started by creating a new post.'; ?>
                </p>
                <div class="mt-6">
                    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_post" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-admin-primary hover:bg-admin-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                        Add New Post
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Delete Post
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to delete this post? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="deleteForm" method="POST" action="">
                    <?php echo generate_csrf_input(); ?>
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                </form>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(postId) {
    const modal = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    
    deleteForm.action = '<?php echo $admin_base_url; ?>actions/delete_post.php?id=' + postId;
    modal.classList.remove('hidden');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        closeDeleteModal();
    }
}

// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
