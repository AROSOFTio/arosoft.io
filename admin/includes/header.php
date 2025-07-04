<?php
// admin/includes/header.php
$current_admin_page_for_header = $admin_page ?? ($_GET['admin_page'] ?? 'dashboard');

defined('BASE_URL') or define('BASE_URL', '/'); 
defined('SITE_NAME') or define('SITE_NAME', 'AROSOFT');

$admin_base_url = BASE_URL . 'admin/';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($admin_page_title); ?> | <?php echo esc_html(SITE_NAME); ?> Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'admin-primary': '#2563eb',    // Blue-600
                        'admin-secondary': '#059669',   // Emerald-600
                        'admin-bg': '#f8fafc',         // Slate-50
                        'admin-text': '#0f172a',       // Slate-900
                        'admin-sidebar-bg': '#1e293b', // Slate-800
                        'admin-sidebar-text': '#e2e8f0', // Slate-200
                        'admin-sidebar-hover': '#334155', // Slate-700
                        'admin-sidebar-active': '#3b82f6', // Blue-500
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        display: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Modern Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide-static@latest/dist/lucide.min.js"></script>
    
    <?php if ($current_admin_page_for_header === 'add_post' || $current_admin_page_for_header === 'edit_post'): ?>
    <!-- TinyMCE with WordPress-like configuration -->
    <script src="https://cdn.tiny.cloud/1/5ppeckamfhsj57zifjdngxrca2zik0bwx6grbzf6whjn269e/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <?php endif; ?>

    <link rel="icon" href="<?php echo BASE_URL; ?>assets/favicon.ico" type="image/x-icon">

    <style>
        /* Essential Admin Styles */
        body { overflow-x: hidden; }
        .admin-content { transition: margin-left 0.3s ease-in-out; }
        
        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* TinyMCE Customization */
        .tox-tinymce {
            border-radius: 0.5rem !important;
            border-color: #e2e8f0 !important;
            overflow: hidden;
        }
        .tox .tox-toolbar__group {
            padding: 0 4px !important;
        }
        .tox .tox-tbtn {
            border-radius: 4px !important;
        }
        
        /* Modern Form Elements */
        input[type="text"], input[type="email"], input[type="password"], textarea, select {
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        /* Smooth Transitions */
        .transition-all { transition: all 0.3s ease-in-out; }
        
        /* Modern Button Hover Effects */
        .btn-hover-effect {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-hover-effect:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="h-full bg-admin-bg text-admin-text font-sans antialiased">
    <div class="min-h-screen flex">
        <?php include_once 'sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
            <header class="bg-white shadow-sm border-b border-gray-200 z-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center">
                            <button id="adminSidebarToggle" class="p-2 rounded-md text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-admin-primary md:hidden">
                                <i data-lucide="menu" class="w-6 h-6"></i>
                            </button>
                            <h1 class="ml-3 text-xl font-semibold text-gray-900"><?php echo esc_html($admin_page_title); ?></h1>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="hidden sm:flex items-center space-x-3">
                                <span class="text-sm text-gray-600">
                                    Welcome, <?php echo isset($_SESSION['admin_full_name']) && !empty($_SESSION['admin_full_name']) ? esc_html($_SESSION['admin_full_name']) : (isset($_SESSION['admin_username']) ? esc_html($_SESSION['admin_username']) : 'Admin'); ?>
                                </span>
                                <span class="text-gray-300">|</span>
                            </div>
                            
                            <a href="<?php echo BASE_URL; ?>index.php?page=home" target="_blank" class="btn-hover-effect text-sm text-admin-secondary hover:text-admin-secondary/80 flex items-center px-3 py-2 rounded-md">
                                <i data-lucide="external-link" class="w-4 h-4 mr-1.5"></i>
                                <span>View Site</span>
                            </a>
                            
                            <a href="<?php echo $admin_base_url; ?>index.php?admin_page=logout" 
                               class="btn-hover-effect text-sm text-red-600 hover:text-red-700 flex items-center bg-red-50 hover:bg-red-100 px-3 py-2 rounded-md">
                                <i data-lucide="log-out" class="w-4 h-4 mr-1.5"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-admin-bg">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
