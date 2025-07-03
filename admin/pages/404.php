<?php
// admin/pages/404.php
defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
?>
<div class="container mx-auto px-4 py-16 text-center">
    <i data-lucide="search-x" class="w-24 h-24 text-red-400 mx-auto mb-6"></i>
    <h1 class="text-4xl font-bold text-gray-800 mb-3">Oops! Page Not Found</h1>
    <p class="text-lg text-gray-600 mb-8">The admin page you are looking for does not exist or has been moved.</p>
    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=dashboard" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-3 px-6 rounded-lg shadow-md transition-colors flex items-center justify-center max-w-xs mx-auto">
        <i data-lucide="home" class="w-5 h-5 mr-2"></i>
        Go to Dashboard
    </a>
</div>
