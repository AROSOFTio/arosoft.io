<?php
// includes/header.php

$page_title       = $page_title ?? (defined('SITE_NAME') ? SITE_NAME : 'dipug.com');
$meta_description = $meta_description ?? 'Welcome to ' . (defined('SITE_NAME') ? SITE_NAME : 'dipug.com');
$meta_keywords    = $meta_keywords ?? '';

defined('BASE_URL') or define('BASE_URL', '/');
if (!defined('SITE_NAME')) {
    $site_settings_for_header_name = load_site_settings();
    define('SITE_NAME', $site_settings_for_header_name['site_name'] ?? 'dipug.com');
}

global $page;
$current_public_page = $page ?? ($_GET['page'] ?? 'home');

$services_menu_items_header = [
    ["name" => "Web Development",   "page" => "webDev",         "icon" => "code-xml",       "desc" => "Modern websites & applications."],
    ["name" => "Software Solutions","page" => "software",       "icon" => "app-window",    "desc" => "Custom software for your needs."],
    ["name" => "Online Courses",    "page" => "courses",        "icon" => "graduation-cap", "desc" => "Upskill with expert-led courses."],
    ["name" => "Tech Support",      "page" => "support",        "icon" => "life-buoy",     "desc" => "Reliable IT assistance & consulting."],
    ["name" => "Cloud Solutions",   "page" => "cloud",          "icon" => "cloud-cog",     "desc" => "Scalable cloud infrastructure."],
    ["name" => "Cybersecurity",     "page" => "cybersecurity",  "icon" => "shield-check",  "desc" => "Protect your valuable digital assets."]
];

$main_nav_links = [
    ["name" => "Home",      "page" => "home",              "icon" => "home"],
    ["name" => "Services",  "page" => "services_overview", "icon" => "layers", "is_mega_menu" => true],
    ["name" => "Portfolio", "page" => "portfolio",         "icon" => "briefcase"],
    ["name" => "Blog",      "page" => "blog",              "icon" => "book-open"],
    ["name" => "Contact",   "page" => "contact",           "icon" => "mail"]
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($page_title); ?></title>
    <meta name="description" content="<?= esc_html($meta_description); ?>">
    <?php if (!empty($meta_keywords)): ?>
      <meta name="keywords" content="<?= esc_html($meta_keywords); ?>">
    <?php endif; ?>

    <!-- Tailwind CSS + custom color configuration -->
    <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              primary:    { DEFAULT: '#0056B3', hover: '#004a99' },
              secondary:  { DEFAULT: '#4B5563', hover: '#374151' },
              accent:     { DEFAULT: '#F59E0B', hover: '#d97706' },
              background: '#F4F4F4',
              text:       '#333333',
              highlight:  '#F59E0B',

              neutral:          '#F4F4F4',
              'neutral-content': '#333333',
              'neutral-focus':   '#0056B3',
              'neutral-light':   '#E5E7EB',
              'neutral-lighter': '#F9FAFB',
              'base-100':        '#FFFFFF',
              'base-200':        '#F4F4F4',
              'base-300':        '#E5E7EB',
              info:             '#22d3ee',
              success:          '#34d399',
              warning:          '#F59E0B',
              error:            '#f87171',
            },
            fontFamily: {
              sans:   ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
              display: ['Poppins', 'sans-serif']
            },
            transitionProperty: {
              height:     'height',
              spacing:    'margin, padding',
              'max-height': 'max-height'
            },
            animation: {
              'fade-in-up':   'fadeInUp 0.5s ease-out forwards',
              'slide-in-left': 'slideInLeft 0.5s ease-out forwards',
              'slide-down':   'slideDown 0.3s ease-out forwards',
              'slide-up':     'slideUp 0.3s ease-out forwards',
            },
            keyframes: {
              fadeInUp: {
                '0%':   { opacity: '0', transform: 'translateY(20px)' },
                '100%': { opacity: '1', transform: 'translateY(0)' }
              },
              slideInLeft: {
                '0%':   { opacity: '0', transform: 'translateX(-20px)' },
                '100%': { opacity: '1', transform: 'translateX(0)' }
              },
              slideDown: {
                '0%':   { opacity: '0', transform: 'translateY(-100%)' },
                '100%': { opacity: '1', transform: 'translateY(0)' }
              },
              slideUp: {
                '0%':   { opacity: '1', transform: 'translateY(0)' },
                '100%': { opacity: '0', transform: 'translateY(-100%)' }
              }
            }
          }
        }
      };
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= BASE_URL; ?>public/css/theme.css">
    <link rel="icon" href="<?= BASE_URL; ?>assets/favicon.ico" type="image/x-icon">

    <style>
      body {
        font-family: 'Inter', sans-serif;
        background-color: #F4F4F4;
        color: #333333;
      }
      .font-display {
        font-family: 'Poppins', sans-serif;
      }
      .nav-link-active {
        color: #0056B3;
        font-weight: 600;
      }
      .mega-menu-container {
        display: none;
        opacity: 0;
        transform: translateY(5px);
        transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        pointer-events: none;
      }
      .group:hover .mega-menu-container,
      .mega-menu-trigger:focus + .mega-menu-container,
      .mega-menu-container:hover {
        display: block;
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
      }
      .mobile-menu {
        max-height: 0;
        overflow-y: auto;
        transition: max-height 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
      }
      .mobile-menu.open {
        max-height: calc(100vh - 4rem);
      }
    </style>
</head>
<body class="antialiased bg-background text-text flex flex-col min-h-screen">

  <!-- Top Bar -->
  <div class="bg-neutral text-neutral-content/70 py-2 px-4 sm:px-6 lg:px-8 border-b border-neutral-light text-xs print:hidden">
    <div class="container mx-auto flex justify-between items-center">
      <div class="flex space-x-4">
        <a href="<?= rtrim(BASE_URL, '/'); ?>/about"   class="hover:text-secondary transition-colors">About Us</a>
        <a href="<?= rtrim(BASE_URL, '/'); ?>/contact" class="hover:text-secondary transition-colors">Contact</a>
        <a href="<?= rtrim(BASE_URL, '/'); ?>/privacy" class="hover:text-secondary transition-colors">Privacy Policy</a>
      </div>
      <div class="flex space-x-3 items-center">
        <a href="#" aria-label="Facebook"  class="hover:text-secondary transition-colors"><i data-lucide="facebook"  class="w-4 h-4"></i></a>
        <a href="#" aria-label="Twitter"   class="hover:text-secondary transition-colors"><i data-lucide="twitter"   class="w-4 h-4"></i></a>
        <a href="#" aria-label="LinkedIn"  class="hover:text-secondary transition-colors"><i data-lucide="linkedin"  class="w-4 h-4"></i></a>
        <a href="#" aria-label="Instagram" class="hover:text-secondary transition-colors"><i data-lucide="instagram" class="w-4 h-4"></i></a>
        <?php if (!isset($_SESSION['admin_user_id'])): ?>
          <a href="<?= BASE_URL; ?>admin/" class="ml-3 px-2 py-0.5 text-xs font-medium bg-secondary hover:bg-secondary-hover text-white rounded transition-colors">Admin Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main Navigation -->
  <header id="mainNav" class="bg-base-100/90 backdrop-blur-lg shadow sticky top-0 z-50 border-b border-neutral-light/50 print:hidden">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo / Site Name -->
        <a href="<?= rtrim(BASE_URL, '/'); ?>/" class="flex items-center space-x-2 shrink-0">
          <i data-lucide="cpu" class="w-8 h-8 text-secondary"></i>
          <span class="font-display text-xl sm:text-2xl font-bold text-text hover:text-secondary transition-colors"><?= SITE_NAME; ?></span>
        </a>

        <!-- Desktop Menu -->
        <nav class="hidden md:flex items-center space-x-6">
          <?php foreach ($main_nav_links as $link_item): ?>
            <?php $link_url = rtrim(BASE_URL, '/') . '/' . $link_item['page']; ?>
            <?php $is_active = ($current_public_page === $link_item['page']); ?>
            <?php if (isset($link_item['is_mega_menu']) && $link_item['is_mega_menu']): ?>
              <div class="group relative">
                <a href="<?= $link_url; ?>"
                   aria-haspopup="true" aria-expanded="false"
                   class="px-3 py-2 rounded-md text-sm font-medium <?= $is_active ? 'nav-link-active' : 'text-text/80 hover:bg-neutral-focus hover:text-white'; ?> transition-colors flex items-center space-x-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-base-100 focus:ring-secondary">
                  <i data-lucide="<?= $link_item['icon']; ?>" class="w-4 h-4"></i>
                  <span><?= esc_html($link_item['name']); ?></span>
                  <i data-lucide="chevron-down" class="w-4 h-4 ml-1 group-hover:rotate-180 transition-transform"></i>
                </a>
                <div class="mega-menu-container absolute top-full left-1/2 transform -translate-x-1/2 mt-0 w-max min-w-[560px] pt-1">
                  <div class="bg-neutral shadow-2xl rounded-b-lg border-t-2 border-secondary p-6 grid grid-cols-2 gap-x-6 gap-y-4">
                    <?php foreach ($services_menu_items_header as $s_item): ?>
                      <a href="<?= rtrim(BASE_URL, '/') . '/' . $s_item['page']; ?>"
                         class="p-3 rounded-lg flex items-start space-x-3 transition-colors bg-neutral hover:bg-neutral-focus hover:text-white">
                        <i data-lucide="<?= $s_item['icon']; ?>" class="w-6 h-6 text-secondary mt-1"></i>
                        <div>
                          <span class="font-semibold text-text hover:text-secondary block text-base"><?= esc_html($s_item['name']); ?></span>
                          <span class="text-xs text-text/60 block"><?= esc_html($s_item['desc'] ?? ''); ?></span>
                        </div>
                      </a>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <a href="<?= $link_url; ?>"
                 class="px-3 py-2 rounded-md text-sm font-medium <?= $is_active ? 'nav-link-active' : 'text-text/80 hover:bg-neutral-focus hover:text-white'; ?> transition-colors flex items-center space-x-1">
                <i data-lucide="<?= $link_item['icon']; ?>" class="w-4 h-4"></i>
                <span><?= esc_html($link_item['name']); ?></span>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>

          <?php if (isset($_SESSION['admin_user_id'])): ?>
            <a href="<?= BASE_URL; ?>admin/" class="px-3 py-2 rounded-md text-sm font-medium text-text/80 hover:bg-neutral-focus hover:text-white transition-colors flex items-center space-x-1">
              <i data-lucide="settings-2" class="w-4 h-4"></i>Admin Panel
            </a>
            <a href="<?= BASE_URL; ?>admin/index.php?admin_page=logout" class="ml-2 px-3 py-1.5 rounded-md text-sm font-medium bg-red-600 hover:bg-red-700 text-white transition-colors flex items-center space-x-1">
              <i data-lucide="log-out" class="w-4 h-4"></i>Sign Out
            </a>
          <?php endif; ?>
        </nav>

        <!-- Mobile Menu Button -->
        <div class="md:hidden flex items-center">
          <button id="mobileMenuButton" aria-label="Open Menu" aria-expanded="false" aria-controls="mobileMenu"
                  class="text-text/80 hover:text-secondary focus:outline-none p-2 rounded-md hover:bg-neutral-focus">
            <i id="mobileMenuIconOpen" data-lucide="menu" class="w-7 h-7 block"></i>
            <i id="mobileMenuIconClose" data-lucide="x" class="w-7 h-7 hidden"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu Panel -->
    <div id="mobileMenu" class="mobile-menu hidden bg-neutral border-t border-neutral-lighter/50 absolute w-full shadow-xl left-0">
      <nav class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
        <?php foreach ($main_nav_links as $link_item): ?>
          <?php $link_url_mobile = rtrim(BASE_URL, '/') . '/' . $link_item['page']; ?>
          <?php $is_active_mobile = ($current_public_page === $link_item['page']); ?>
          <a href="<?= $link_url_mobile; ?>"
             class="block px-3 py-3 rounded-md text-base font-medium <?= $is_active_mobile ? 'text-secondary bg-neutral-focus' : 'text-text/80 hover:bg-neutral-focus hover:text-white'; ?> transition-colors flex items-center space-x-2">
            <i data-lucide="<?= $link_item['icon']; ?>" class="w-5 h-5"></i>
            <span><?= esc_html($link_item['name']); ?></span>
          </a>
          <?php if (isset($link_item['is_mega_menu']) && $link_item['is_mega_menu']): ?>
            <div class="pl-5 space-y-1 border-l-2 border-neutral-focus ml-2.5 mb-2">
              <?php foreach ($services_menu_items_header as $s_item): ?>
                <a href="<?= rtrim(BASE_URL, '/') . '/' . $s_item['page']; ?>"
                   class="block px-3 py-2 rounded-md text-sm font-medium text-text/80 hover:bg-neutral-focus hover:text-white transition-colors flex items-center space-x-2">
                  <i data-lucide="<?= $s_item['icon']; ?>" class="w-4 h-4"></i>
                  <span><?= esc_html($s_item['name']); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>

        <div class="border-t border-neutral-lighter pt-4 mt-4 space-y-2">
          <?php if (isset($_SESSION['admin_user_id'])): ?>
            <a href="<?= BASE_URL; ?>admin/"
               class="block px-3 py-3 rounded-md text-base font-medium text-text/80 hover:bg-neutral-focus hover:text-white transition-colors">Admin Dashboard</a>
            <a href="<?= BASE_URL; ?>admin/index.php?admin_page=logout"
               class="block w-full text-left px-3 py-3 rounded-md text-base font-medium bg-red-600 hover:bg-red-700 text-white transition-colors">
              Sign Out
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL; ?>admin/"
               class="block px-3 py-3 rounded-md text-base font-medium text-text/80 hover:bg-neutral-focus hover:text-white transition-colors">Admin Login</a>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </header>
