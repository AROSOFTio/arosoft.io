<?php
$active_admin_page = $admin_page ?? ($_GET['admin_page'] ?? 'dashboard');

defined('BASE_URL') or define('BASE_URL', '/');
defined('SITE_NAME') or define('SITE_NAME', 'dipug.com');

$admin_base_url = BASE_URL . 'admin/';

$nav_items = [
    'dashboard' => [
        'icon' => 'layout-dashboard',
        'text' => 'Dashboard',
        'description' => 'Overview & Statistics'
    ],
    'posts' => [
        'icon' => 'file-text',
        'text' => 'Posts',
        'description' => 'Manage Blog Posts',
        'sub_pages' => ['add_post', 'edit_post'],
        'actions' => [
            ['text' => 'All Posts', 'url' => '?admin_page=posts'],
            ['text' => 'Add New', 'url' => '?admin_page=add_post'],
            ['text' => 'Categories', 'url' => '?admin_page=categories']
        ]
    ],
    'media' => [
        'icon' => 'image',
        'text' => 'Media',
        'description' => 'Media Library'
    ],
    'comments' => [
        'icon' => 'messages-square',
        'text' => 'Comments',
        'description' => 'Manage Comments'
    ],
    'users' => [
        'icon' => 'users',
        'text' => 'Users',
        'description' => 'Manage Users',
        'sub_pages' => ['add_user', 'edit_user']
    ],
    'settings' => [
        'icon' => 'settings',
        'text' => 'Settings',
        'description' => 'Site Configuration'
    ],
];
?>

<div id="adminSidebar" class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64">
        <div class="flex flex-col h-0 flex-1">
            <!-- Sidebar Header -->
            <div class="flex items-center h-16 flex-shrink-0 px-4 bg-admin-sidebar-bg border-b border-gray-700">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=dashboard" class="flex items-center space-x-3 w-full">
                    <i data-lucide="boxes" class="w-8 h-8 text-admin-primary"></i>
                    <span class="font-display text-lg font-bold text-white truncate">
                        <?php echo esc_html(SITE_NAME); ?> Admin
                    </span>
                </a>
            </div>
            
            <!-- Navigation -->
            <div class="flex-1 flex flex-col overflow-y-auto bg-admin-sidebar-bg">
                <nav class="flex-1 px-2 py-4 space-y-1">
                    <?php foreach ($nav_items as $page_slug => $item): ?>
                        <?php
                            $is_active = ($active_admin_page === $page_slug);
                            if (isset($item['sub_pages']) && in_array($active_admin_page, $item['sub_pages'])) {
                                $is_active = true;
                            }
                        ?>
                        <div class="space-y-1">
                            <a href="<?php echo $admin_base_url; ?>index.php?admin_page=<?php echo $page_slug; ?>" 
                               class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-md transition-all duration-200
                                      <?php echo $is_active 
                                          ? 'bg-admin-sidebar-active text-white' 
                                          : 'text-admin-sidebar-text hover:bg-admin-sidebar-hover hover:text-white'; ?>">
                                <i data-lucide="<?php echo $item['icon']; ?>" 
                                   class="mr-3 flex-shrink-0 h-5 w-5 transition-transform duration-200 group-hover:scale-110"></i>
                                <span class="flex-1"><?php echo esc_html($item['text']); ?></span>
                                <?php if (isset($item['actions'])): ?>
                                    <i data-lucide="chevron-down" class="ml-2 h-4 w-4 transition-transform duration-200
                                        <?php echo $is_active ? 'transform rotate-180' : ''; ?>"></i>
                                <?php endif; ?>
                            </a>
                            
                            <?php if (isset($item['actions']) && $is_active): ?>
                                <div class="mt-1 space-y-1">
                                    <?php foreach ($item['actions'] as $action): ?>
                                        <a href="<?php echo $admin_base_url . 'index.php' . $action['url']; ?>" 
                                           class="group flex items-center pl-10 pr-3 py-2 text-sm font-medium text-admin-sidebar-text 
                                                  rounded-md hover:text-white hover:bg-admin-sidebar-hover transition-colors duration-200">
                                            <?php echo esc_html($action['text']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
            </div>
            
            <!-- Sidebar Footer -->
            <div class="flex-shrink-0 flex border-t border-gray-700 p-4">
                <div class="flex-shrink-0 w-full group block">
                    <div class="flex items-center">
                        <div>
                            <i data-lucide="user" class="inline-block h-9 w-9 rounded-full text-admin-primary"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">
                                <?php echo isset($_SESSION['admin_full_name']) ? esc_html($_SESSION['admin_full_name']) : 'Admin User'; ?>
                            </p>
                            <p class="text-xs font-medium text-gray-400 group-hover:text-gray-300">
                                Administrator
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-gray-600 bg-opacity-75 transition-opacity md:hidden"></div>
