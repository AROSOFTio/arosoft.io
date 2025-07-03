<?php
// admin/pages/settings.php
defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
global $conn; // Not used directly here, but good to have if settings were in DB

// Define the path to the settings file
$settings_file_path = __DIR__ . '/../../config/site_settings.json'; // Relative to this file's location

// Default settings
$default_settings = [
    'site_name' => defined('SITE_NAME') ? SITE_NAME : 'dipug.com',
    'site_tagline' => defined('SITE_TAGLINE') ? SITE_TAGLINE : 'Digital Innovation and Programing',
    'site_description' => '', // New default
    'posts_per_page' => 10,
    'contact_email' => 'info@dipug.com',
    'footer_copyright' => '&copy; ' . date("Y") . ' ' . (defined('SITE_NAME') ? SITE_NAME : 'dipug.com') . '. All Rights Reserved.',
    'site_logo' => null,
    'seo_site_title' => '', // New SEO default
    'seo_default_description' => '', // New SEO default
    'seo_og_image' => '', // New SEO default
];

// Load current settings
$current_settings = $default_settings;
if (file_exists($settings_file_path)) {
    $json_data = file_get_contents($settings_file_path);
    $loaded_settings = json_decode($json_data, true);
    if (is_array($loaded_settings)) {
        $current_settings = array_merge($default_settings, $loaded_settings);
    }
}
// Ensure all keys exist, defaulting if not present after merge (array_merge handles this for top-level keys)
// For robustness, explicitly ensure specific keys if defaults might not cover all cases or for clarity.
$current_settings['site_logo'] = $current_settings['site_logo'] ?? null;
$current_settings['site_description'] = $current_settings['site_description'] ?? '';
$current_settings['seo_site_title'] = $current_settings['seo_site_title'] ?? '';
$current_settings['seo_default_description'] = $current_settings['seo_default_description'] ?? '';
$current_settings['seo_og_image'] = $current_settings['seo_og_image'] ?? '';


// Retrieve form data from session if validation failed
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Site Settings</h2>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded-md <?php echo $_SESSION['flash_message_type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>" role="alert">
            <?php echo esc_html($_SESSION['flash_message']); ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['form_error'])): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-md" role="alert">
            <p class="font-bold">Please correct the following errors:</p>
            <ul class="list-disc list-inside">
            <?php 
                if(is_array($_SESSION['form_error'])){
                    foreach($_SESSION['form_error'] as $err) { echo "<li>" . esc_html($err) . "</li>"; }
                } else {
                    echo "<li>" . esc_html($_SESSION['form_error']) . "</li>";
                }
                unset($_SESSION['form_error']); 
            ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo $admin_base_url; ?>actions/save_settings_process.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-6">
        <?php echo generate_csrf_input(); ?>
        
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">General Settings</legend>
            <div class="space-y-4 mt-2">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name <span class="text-red-500">*</span></label>
                    <input type="text" name="site_name" id="site_name" value="<?php echo esc_html($form_data['site_name'] ?? $current_settings['site_name']); ?>" required 
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>

                <div>
                    <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-1">Site Tagline <span class="text-red-500">*</span></label>
                    <input type="text" name="site_tagline" id="site_tagline" value="<?php echo esc_html($form_data['site_tagline'] ?? $current_settings['site_tagline']); ?>" required
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>

                <div>
                    <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">Site Description</label>
                    <textarea name="site_description" id="site_description" rows="3"
                              class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"><?php echo esc_html($form_data['site_description'] ?? $current_settings['site_description']); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">A short description of your website for SEO and general display.</p>
                </div>

                 <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email <span class="text-red-500">*</span></label>
                    <input type="email" name="contact_email" id="contact_email" value="<?php echo esc_html($form_data['contact_email'] ?? $current_settings['contact_email']); ?>" required
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
            </div>
        </fieldset>

        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">Site Logo</legend>
            <div class="space-y-4 mt-2">
                <div>
                    <label for="site_logo_upload" class="block text-sm font-medium text-gray-700 mb-1">Upload New Logo</label>
                    <input type="file" name="site_logo" id="site_logo_upload" accept="image/png, image/jpeg, image/gif, image/svg+xml"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-admin-primary file:text-white hover:file:bg-opacity-90 file:cursor-pointer">
                    <p class="text-xs text-gray-500 mt-1">Recommended size: 300x100px. Max file size: 1MB. Supported formats: PNG, JPG, GIF, SVG.</p>
                </div>

                <?php if (!empty($current_settings['site_logo'])): ?>
                    <div id="currentLogoContainer">
                        <p class="block text-sm font-medium text-gray-700 mb-1">Current Logo:</p>
                        <?php
                            $logo_path = rtrim(BASE_URL, '/') . '/uploads/theme/' . esc_html($current_settings['site_logo']);
                            $local_logo_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/theme/' . $current_settings['site_logo'];
                            if (!file_exists($local_logo_path)) {
                                error_log("Current logo file not found at: " . $local_logo_path . " (referenced by " . $current_settings['site_logo'] .")");
                            }
                        ?>
                        <img src="<?php echo $logo_path; ?>" alt="Current Site Logo" class="max-h-24 border p-1 rounded-md shadow-sm"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <p class="text-xs text-red-500 hidden">Logo image not found. It might have been moved or deleted.</p>
                        <div class="mt-2">
                            <label class="inline-flex items-center text-sm text-gray-700">
                                <input type="checkbox" name="remove_site_logo" value="1" class="h-4 w-4 text-admin-primary border-gray-300 rounded focus:ring-admin-primary mr-1">
                                Remove current logo
                            </label>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No site logo currently uploaded.</p>
                <?php endif; ?>
            </div>
        </fieldset>

        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">SEO Settings</legend>
            <div class="space-y-4 mt-2">
                <div>
                    <label for="seo_site_title" class="block text-sm font-medium text-gray-700 mb-1">SEO Site Title</label>
                    <input type="text" name="seo_site_title" id="seo_site_title" value="<?php echo esc_html($form_data['seo_site_title'] ?? $current_settings['seo_site_title']); ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                    <p class="text-xs text-gray-500 mt-1">If empty, the main "Site Name" will be used.</p>
                </div>
                <div>
                    <label for="seo_default_description" class="block text-sm font-medium text-gray-700 mb-1">Default Meta Description</label>
                    <textarea name="seo_default_description" id="seo_default_description" rows="3"
                              class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"><?php echo esc_html($form_data['seo_default_description'] ?? $current_settings['seo_default_description']); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Default description for search engines if a page-specific one is not set (approx. 150-160 chars).</p>
                </div>
                <div>
                    <label for="seo_og_image" class="block text-sm font-medium text-gray-700 mb-1">Default Open Graph Image URL</label>
                    <input type="url" name="seo_og_image" id="seo_og_image" value="<?php echo esc_html($form_data['seo_og_image'] ?? $current_settings['seo_og_image']); ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"
                           placeholder="https://example.com/path/to/default-og-image.jpg">
                    <p class="text-xs text-gray-500 mt-1">Full URL to a default image for social media sharing (e.g., 1200x630px).</p>
                </div>
            </div>
        </fieldset>

        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">Blog Settings</legend>
             <div class="space-y-4 mt-2">
                <div>
                    <label for="posts_per_page" class="block text-sm font-medium text-gray-700 mb-1">Posts Per Page (Public Blog) <span class="text-red-500">*</span></label>
                    <input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo esc_html($form_data['posts_per_page'] ?? $current_settings['posts_per_page']); ?>" required min="1" max="50"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
            </div>
        </fieldset>
        
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">Footer Settings</legend>
             <div class="space-y-4 mt-2">
                <div>
                    <label for="footer_copyright" class="block text-sm font-medium text-gray-700 mb-1">Footer Copyright Text</label>
                    <input type="text" name="footer_copyright" id="footer_copyright" value="<?php echo esc_html($form_data['footer_copyright'] ?? $current_settings['footer_copyright']); ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                     <p class="text-xs text-gray-500 mt-1">Use <code>{year}</code> to automatically insert the current year. Use <code>{site_name}</code> for the current site name.</p>
                </div>
            </div>
        </fieldset>
        
        <div class="pt-5 border-t border-gray-200">
            <div class="flex justify-end">
                <button type="submit" name="save_settings" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2.5 px-6 rounded-lg shadow-md transition-colors flex items-center">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                    Save Settings
                </button>
            </div>
        </div>
    </form>
</div>
<script>
if (typeof lucide !== 'undefined') { lucide.createIcons(); }

// Script to handle image preview for new logo (optional, but good UX)
const siteLogoUpload = document.getElementById('site_logo_upload');
const currentLogoContainer = document.getElementById('currentLogoContainer');

if (siteLogoUpload) {
    siteLogoUpload.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let previewImg = document.getElementById('newLogoPreviewImg');
                if (!previewImg) {
                    previewImg = document.createElement('img');
                    previewImg.id = 'newLogoPreviewImg';
                    previewImg.alt = 'New logo preview';
                    previewImg.className = 'max-h-24 border p-1 rounded-md shadow-sm mt-2';
                    siteLogoUpload.insertAdjacentElement('afterend', previewImg);
                }
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            let previewImg = document.getElementById('newLogoPreviewImg');
            if (previewImg) {
                previewImg.style.display = 'none';
            }
        }
    });
}
</script>
