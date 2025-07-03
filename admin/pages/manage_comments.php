<?php
// Pagination settings
$comments_per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $comments_per_page;

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['s']) ? trim($_GET['s']) : '';

// Build the SQL query
$where_clauses = array();
$params = array();
$param_types = '';

if ($status_filter && $status_filter !== 'all') {
    $where_clauses[] = "c.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($search_query) {
    $where_clauses[] = "(c.content LIKE ? OR c.author_name LIKE ? OR c.author_email LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $param_types .= 'sss';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total comments
$count_sql = "SELECT COUNT(*) as total FROM comments c {$where_sql}";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_comments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_comments / $comments_per_page);

// Get comments with post information
$comments_sql = "
    SELECT c.*, p.title as post_title, p.slug as post_slug,
           COALESCE(u.name, c.author_name) as display_name
    FROM comments c
    LEFT JOIN posts p ON c.post_id = p.id
    LEFT JOIN users u ON c.user_id = u.id
    {$where_sql}
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($comments_sql);
if (!empty($params)) {
    $params[] = $comments_per_page;
    $params[] = $offset;
    $param_types .= 'ii';
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param('ii', $comments_per_page, $offset);
}
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get comment counts by status
$status_counts = array();
$status_sql = "SELECT status, COUNT(*) as count FROM comments GROUP BY status";
$status_result = $conn->query($status_sql);
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
$total_count = array_sum($status_counts);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Comments</h1>
            <p class="mt-1 text-sm text-gray-600">
                Manage and moderate comments on your posts
            </p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="mb-6">
        <nav class="flex space-x-4" aria-label="Status tabs">
            <a href="?admin_page=comments" 
               class="<?php echo $status_filter === 'all' ? 'bg-admin-primary text-white' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                All (<?php echo $total_count; ?>)
            </a>
            <a href="?admin_page=comments&status=pending" 
               class="<?php echo $status_filter === 'pending' ? 'bg-yellow-500 text-white' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                Pending (<?php echo $status_counts['pending'] ?? 0; ?>)
            </a>
            <a href="?admin_page=comments&status=approved" 
               class="<?php echo $status_filter === 'approved' ? 'bg-green-500 text-white' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                Approved (<?php echo $status_counts['approved'] ?? 0; ?>)
            </a>
            <a href="?admin_page=comments&status=spam" 
               class="<?php echo $status_filter === 'spam' ? 'bg-red-500 text-white' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                Spam (<?php echo $status_counts['spam'] ?? 0; ?>)
            </a>
            <a href="?admin_page=comments&status=trash" 
               class="<?php echo $status_filter === 'trash' ? 'bg-gray-500 text-white' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                Trash (<?php echo $status_counts['trash'] ?? 0; ?>)
            </a>
        </nav>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <form action="" method="GET" class="max-w-lg">
            <input type="hidden" name="admin_page" value="comments">
            <?php if ($status_filter !== 'all'): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
            <?php endif; ?>
            <div class="relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
                </div>
                <input type="search" name="s" id="search" 
                       class="focus:ring-admin-primary focus:border-admin-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                       placeholder="Search comments by content or author..."
                       value="<?php echo esc_html($search_query); ?>">
            </div>
        </form>
    </div>

    <!-- Comments Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <?php if (!empty($comments)): ?>
            <div class="min-w-full divide-y divide-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Author
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Comment
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                In Response To
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($comments as $comment): ?>
                            <tr id="comment-<?php echo $comment['id']; ?>" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full" 
                                                 src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($comment['author_email']))); ?>?s=40&d=mp" 
                                                 alt="<?php echo esc_attr($comment['display_name']); ?>">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo esc_html($comment['display_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo esc_html($comment['author_email']); ?>
                                            </div>
                                            <?php if ($comment['author_url']): ?>
                                                <a href="<?php echo esc_url($comment['author_url']); ?>" 
                                                   class="text-xs text-admin-primary hover:underline" 
                                                   target="_blank" rel="nofollow">
                                                    <?php echo esc_html(parse_url($comment['author_url'], PHP_URL_HOST)); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo nl2br(esc_html($comment['content'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Submitted on <?php echo date('M j, Y \a\t g:i a', strtotime($comment['created_at'])); ?>
                                    </div>
                                    <?php if ($comment['author_ip']): ?>
                                        <div class="text-xs text-gray-400">
                                            IP: <?php echo esc_html($comment['author_ip']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=edit_post&id=<?php echo $comment['post_id']; ?>" 
                                           class="text-admin-primary hover:underline">
                                            <?php echo esc_html($comment['post_title']); ?>
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <a href="<?php echo rtrim(BASE_URL, '/') . '/' . urlencode($comment['post_slug']); ?>#comment-<?php echo $comment['id']; ?>" 
                                           class="hover:underline" target="_blank">
                                            View Post
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php
                                        switch ($comment['status']) {
                                            case 'approved':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'spam':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'trash':
                                                echo 'bg-gray-100 text-gray-800';
                                                break;
                                        }
                                        ?>">
                                        <?php echo ucfirst($comment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-3">
                                        <?php if ($comment['status'] !== 'approved'): ?>
                                            <button onclick="approveComment(<?php echo $comment['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-700"
                                                    title="Approve">
                                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($comment['status'] !== 'spam'): ?>
                                            <button onclick="markAsSpam(<?php echo $comment['id']; ?>)" 
                                                    class="text-yellow-600 hover:text-yellow-700"
                                                    title="Mark as Spam">
                                                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($comment['status'] !== 'trash'): ?>
                                            <button onclick="moveToTrash(<?php echo $comment['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-700"
                                                    title="Move to Trash">
                                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="deleteComment(<?php echo $comment['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-700"
                                                    title="Delete Permanently">
                                                <i data-lucide="x-circle" class="w-5 h-5"></i>
                                            </button>
                                            <button onclick="restoreComment(<?php echo $comment['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-700"
                                                    title="Restore">
                                                <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                                            </button>
                                        <?php endif; ?>
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
                            <a href="?admin_page=comments&paged=<?php echo $current_page - 1; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?admin_page=comments&paged=<?php echo $current_page + 1; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
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
                                <span class="font-medium"><?php echo min($offset + $comments_per_page, $total_comments); ?></span>
                                of
                                <span class="font-medium"><?php echo $total_comments; ?></span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?admin_page=comments&paged=<?php echo $i; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?><?php echo $search_query ? '&s=' . urlencode($search_query) : ''; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i === $current_page ? 'text-admin-primary bg-admin-primary/10 border-admin-primary' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php 
                                endfor;
                                
                                if ($end_page < $total_pages) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-16 px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 max-w-lg mx-auto">
                    <div class="flex flex-col items-center">
                        <div class="rounded-full bg-gray-100 p-4 mb-4">
                            <i data-lucide="message-square" class="h-12 w-12 text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Comments Found</h3>
                        <p class="text-base text-gray-600 text-center max-w-md">
                            <?php
                            if ($search_query) {
                                echo 'We couldn\'t find any comments matching your search criteria. Try adjusting your search terms.';
                            } elseif ($status_filter !== 'all') {
                                echo 'There are no comments with status "' . ucfirst($status_filter) . '" at the moment.';
                            } else {
                                echo 'Comments will appear here once visitors start engaging with your posts. Enable comments on your posts to get started.';
                            }
                            ?>
                        </p>
                        <?php if ($status_filter !== 'all' || $search_query): ?>
                            <a href="?admin_page=comments" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-admin-primary hover:bg-admin-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary/60">
                                View All Comments
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title"></h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-message"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmButton"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Confirm
                </button>
                <button type="button" onclick="closeModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for comment management
const adminBaseUrl = '<?php echo $admin_base_url; ?>';
const csrfToken = '<?php echo generate_csrf_token(); ?>';
</script>
