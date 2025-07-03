<?php
require_once 'includes/header.php';

// Get post ID from URL
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// Fetch post details
$post_sql = "SELECT title, slug FROM posts WHERE id = ?";
$post_stmt = $conn->prepare($post_sql);
$post_stmt->bind_param('i', $post_id);
$post_stmt->execute();
$post = $post_stmt->get_result()->fetch_assoc();

// Fetch approved comments for this post
$comments_sql = "SELECT c.*, COALESCE(u.full_name, c.author_name) as display_name 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? AND c.status = 'approved' 
                ORDER BY c.created_at DESC";
$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param('i', $post_id);
$comments_stmt->execute();
$comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <?php if ($post): ?>
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Comments on "<?php echo esc_html($post['title']); ?>"</h1>
            <a href="/<?php echo esc_attr($post['slug']); ?>" class="text-admin-primary hover:underline">← Back to Post</a>
        </div>

        <?php if (!empty($comments)): ?>
            <div class="space-y-8">
                <?php foreach ($comments as $comment): ?>
                    <div id="comment-<?php echo $comment['id']; ?>" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <img class="h-10 w-10 rounded-full" 
                                     src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($comment['author_email']))); ?>?s=40&d=mp" 
                                     alt="<?php echo esc_attr($comment['display_name']); ?>">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900"><?php echo esc_html($comment['display_name']); ?></h3>
                                        <?php if ($comment['author_url']): ?>
                                            <a href="<?php echo esc_url($comment['author_url']); ?>" 
                                               class="text-xs text-admin-primary hover:underline" 
                                               target="_blank" rel="nofollow">
                                                <?php echo esc_html(parse_url($comment['author_url'], PHP_URL_HOST)); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        <?php echo date('M j, Y \a\t g:i a', strtotime($comment['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="mt-2 text-sm text-gray-700 space-y-4">
                                    <?php echo nl2br(esc_html($comment['content'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <div class="flex flex-col items-center">
                    <div class="rounded-full bg-gray-100 p-4 mb-4">
                        <i data-lucide="message-square" class="h-12 w-12 text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Comments Yet</h3>
                    <p class="text-base text-gray-600 max-w-md">
                        Be the first to share your thoughts on this post.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Comment Form -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Leave a Comment</h2>
            <form action="/actions/add_comment.php" method="POST" class="space-y-4">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <input type="hidden" name="token" value="<?php echo generate_csrf_token(); ?>">
                
                <?php if (!is_logged_in()): ?>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="author_name" class="block text-sm font-medium text-gray-700">Name *</label>
                            <input type="text" name="author_name" id="author_name" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-admin-primary focus:ring-admin-primary sm:text-sm">
                        </div>
                        <div>
                            <label for="author_email" class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="author_email" id="author_email" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-admin-primary focus:ring-admin-primary sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="author_url" class="block text-sm font-medium text-gray-700">Website</label>
                        <input type="url" name="author_url" id="author_url"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-admin-primary focus:ring-admin-primary sm:text-sm">
                    </div>
                <?php endif; ?>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">Comment *</label>
                    <textarea name="content" id="content" rows="4" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-admin-primary focus:ring-admin-primary sm:text-sm"></textarea>
                </div>

                <div>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-admin-primary hover:bg-admin-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary/60">
                        Post Comment
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="flex flex-col items-center">
                <div class="rounded-full bg-gray-100 p-4 mb-4">
                    <i data-lucide="alert-triangle" class="h-12 w-12 text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Post Not Found</h3>
                <p class="text-base text-gray-600 max-w-md">
                    The post you're looking for doesn't exist or has been removed.
                </p>
                <a href="/blog" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-admin-primary hover:bg-admin-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary/60">
                    Back to Blog
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>

<?php require_once 'includes/footer.php'; ?>
