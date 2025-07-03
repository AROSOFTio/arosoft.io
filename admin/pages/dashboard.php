<?php
// Get quick statistics
$stats = array();

// Posts count
$posts_query = "SELECT 
    COUNT(*) as total_posts,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_posts
FROM posts";
$posts_result = $conn->query($posts_query);
$posts_stats = $posts_result->fetch_assoc();

// Comments count (if implemented)
$comments_query = "SELECT COUNT(*) as total_comments FROM comments WHERE status = 'pending' LIMIT 1";
$comments_result = $conn->query($comments_query);
$pending_comments = ($comments_result && $comments_result->num_rows > 0) ? $comments_result->fetch_assoc()['total_comments'] : 0;

// Categories count
$categories_query = "SELECT COUNT(*) as total_categories FROM categories";
$categories_result = $conn->query($categories_query);
$categories_count = ($categories_result && $categories_result->num_rows > 0) ? $categories_result->fetch_assoc()['total_categories'] : 0;

// Recent posts
$recent_posts_query = "SELECT id, title, status, created_at FROM posts ORDER BY created_at DESC LIMIT 5";
$recent_posts_result = $conn->query($recent_posts_query);
$recent_posts = ($recent_posts_result && $recent_posts_result->num_rows > 0) ? $recent_posts_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-admin-primary to-blue-600 rounded-xl shadow-lg p-6 mb-8 text-white">
        <h1 class="text-2xl font-display font-bold mb-2">
            Welcome back, <?php echo isset($_SESSION['admin_full_name']) ? esc_html($_SESSION['admin_full_name']) : 'Admin'; ?>!
        </h1>
        <p class="text-blue-100">Here's what's happening with your site today.</p>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Posts -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Total Posts</h3>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $posts_stats['total_posts']; ?></p>
                </div>
            </div>
            <div class="mt-4 flex justify-between text-sm">
                <span class="text-green-600">
                    <i data-lucide="check-circle" class="w-4 h-4 inline"></i>
                    <?php echo $posts_stats['published_posts']; ?> Published
                </span>
                <span class="text-yellow-600">
                    <i data-lucide="edit-3" class="w-4 h-4 inline"></i>
                    <?php echo $posts_stats['draft_posts']; ?> Drafts
                </span>
            </div>
        </div>

        <!-- Categories -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                    <i data-lucide="folder" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Categories</h3>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $categories_count; ?></p>
                </div>
            </div>
            <div class="mt-4">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" 
                   class="text-purple-600 hover:text-purple-700 text-sm flex items-center">
                    <i data-lucide="plus" class="w-4 h-4 mr-1"></i>
                    Add New Category
                </a>
            </div>
        </div>

        <!-- Pending Comments -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <i data-lucide="message-circle" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Pending Comments</h3>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $pending_comments; ?></p>
                </div>
            </div>
            <div class="mt-4">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=comments" 
                   class="text-yellow-600 hover:text-yellow-700 text-sm flex items-center">
                    <i data-lucide="check" class="w-4 h-4 mr-1"></i>
                    Moderate Comments
                </a>
            </div>
        </div>

        <!-- Quick Draft -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <i data-lucide="edit" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500">Quick Draft</h3>
                    <p class="text-sm text-gray-600 mt-1">Start a new post</p>
                </div>
            </div>
            <div class="mt-4">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_post" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 w-full justify-center">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                    Create New Post
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Posts -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="border-b border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Recent Posts</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if (!empty($recent_posts)): ?>
                    <?php foreach ($recent_posts as $post): ?>
                        <div class="p-6 flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo esc_html($post['title']); ?>
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                </p>
                            </div>
                            <div class="ml-4 flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $post['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=edit_post&id=<?php echo $post['id']; ?>" 
                                   class="text-gray-400 hover:text-gray-500">
                                    <i data-lucide="edit-2" class="w-5 h-5"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        No posts found. Start by creating your first post!
                    </div>
                <?php endif; ?>
            </div>
            <div class="border-t border-gray-200 p-4">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=posts" 
                   class="text-admin-primary hover:text-admin-primary/80 text-sm font-medium flex items-center justify-center">
                    <i data-lucide="list" class="w-4 h-4 mr-2"></i>
                    View All Posts
                </a>
            </div>
        </div>

        <!-- Quick Links & Tips -->
        <div class="space-y-8">
            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="border-b border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Quick Links</h2>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_post" 
                       class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <i data-lucide="file-plus" class="w-5 h-5 text-blue-500 mr-3"></i>
                        <span class="text-sm font-medium text-gray-900">New Post</span>
                    </a>
                    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_user" 
                       class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <i data-lucide="user-plus" class="w-5 h-5 text-green-500 mr-3"></i>
                        <span class="text-sm font-medium text-gray-900">New User</span>
                    </a>
                    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" 
                       class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <i data-lucide="folder-plus" class="w-5 h-5 text-purple-500 mr-3"></i>
                        <span class="text-sm font-medium text-gray-900">Categories</span>
                    </a>
                    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=settings" 
                       class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <i data-lucide="settings" class="w-5 h-5 text-gray-500 mr-3"></i>
                        <span class="text-sm font-medium text-gray-900">Settings</span>
                    </a>
                </div>
            </div>

            <!-- Tips & Help -->
            <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Tips & Help</h2>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <i data-lucide="lightbulb" class="w-5 h-5 text-yellow-500 mr-3 flex-shrink-0 mt-0.5"></i>
                        <p class="text-sm text-gray-600">Use categories to organize your content and make it easier for visitors to find related posts.</p>
                    </li>
                    <li class="flex items-start">
                        <i data-lucide="image" class="w-5 h-5 text-blue-500 mr-3 flex-shrink-0 mt-0.5"></i>
                        <p class="text-sm text-gray-600">Add featured images to your posts to make them more engaging and shareable on social media.</p>
                    </li>
                    <li class="flex items-start">
                        <i data-lucide="search" class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5"></i>
                        <p class="text-sm text-gray-600">Don't forget to fill in SEO details for better search engine visibility.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
