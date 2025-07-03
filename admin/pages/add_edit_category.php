<?php
// admin/pages/add_edit_category.php
defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
global $conn;

// --- Helper functions (copied from manage_categories.php for now) ---
function build_category_tree(array &$elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = build_category_tree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }
    return $branch;
}

function generate_category_options(array $categories, $selectedParentId = null, $excludeCategoryId = null, $level = 0) {
    $options = '';
    foreach ($categories as $category) {
        if ($excludeCategoryId !== null && $category['id'] == $excludeCategoryId) {
            continue;
        }
        $value = $category['id'];
        // Use loose comparison for selectedParentId as it might come from POST data as string '0'
        $selected = ($selectedParentId !== null && $selectedParentId == $value) ? 'selected' : '';
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $options .= "<option value=\"{$value}\" {$selected}>" . $indent . esc_html($category['name']) . "</option>";
        if (!empty($category['children'])) {
            $options .= generate_category_options($category['children'], $selectedParentId, $excludeCategoryId, $level + 1);
        }
    }
    return $options;
}
// --- End Helper functions ---

$is_editing = false;
$category_id = null;
$category_name = '';
$category_slug = '';
$category_description = '';
$category_parent_id = null; // Added for parent_id

// Fetch all categories for the dropdown
$all_categories_sql = "SELECT id, name, parent_id FROM categories ORDER BY name ASC";
$all_categories_result = $conn->query($all_categories_sql);
$all_categories_flat = ($all_categories_result && $all_categories_result->num_rows > 0) ? $all_categories_result->fetch_all(MYSQLI_ASSOC) : [];
$category_tree_for_select = build_category_tree($all_categories_flat);


$form_action_target = 'add_category_process.php'; // Default for adding
$field_name_prefix = 'category_'; // For add_category_process.php

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $is_editing = true;
    $category_id = (int)$_GET['id'];
    // For edit_category_process.php, it expects 'name', 'slug', 'description', 'parent_id'
    // The GET parameter for ID is 'id' as per the form action construction.
    $form_action_target = 'edit_category_process.php?id=' . $category_id;
    $field_name_prefix = ''; // For edit_category_process.php

    // Fetch current category details including parent_id
    $stmt = $conn->prepare("SELECT name, slug, description, parent_id FROM categories WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $category = $result->fetch_assoc();
            $category_name = $category['name'];
            $category_slug = $category['slug'];
            $category_description = $category['description'];
            $category_parent_id = $category['parent_id'];
        } else {
            $_SESSION['flash_message'] = "Category not found.";
            $_SESSION['flash_message_type'] = "error";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
            exit;
        }
        $stmt->close();
    } else {
        error_log("DB Error fetching category for edit: " . $conn->error);
        $_SESSION['flash_message'] = "Database error fetching category details.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
        exit;
    }
}
$form_action = $admin_base_url . 'actions/' . $form_action_target;

// Retrieve form data from session if validation failed
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Determine current parent ID for form (from form_data if exists, else from DB or null)
$current_parent_id_for_form = $form_data[$field_name_prefix . 'parent_id'] ?? ($form_data['parent_id'] ?? $category_parent_id);
$exclude_id_for_form = $is_editing ? $category_id : null;

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800"><?php echo $is_editing ? 'Edit Category' : 'Add New Category'; ?></h2>
        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" class="text-admin-primary hover:underline text-sm flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>Back to All Categories
        </a>
    </div>

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

    <form action="<?php echo esc_html($form_action); ?>" method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-6">
        <?php echo generate_csrf_input(); ?>
        
        <div>
            <label for="<?php echo $field_name_prefix; ?>name" class="block text-sm font-medium text-gray-700 mb-1">Category Name <span class="text-red-500">*</span></label>
            <input type="text" name="<?php echo $field_name_prefix; ?>name" id="<?php echo $field_name_prefix; ?>name"
                   value="<?php echo esc_html($form_data[$field_name_prefix . 'name'] ?? ($form_data['name'] ?? $category_name)); ?>" required
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"
                   oninput="document.getElementById('<?php echo $field_name_prefix; ?>slug').value = slugify(this.value);">
        </div>

        <div>
            <label for="<?php echo $field_name_prefix; ?>slug" class="block text-sm font-medium text-gray-700 mb-1">Slug (URL-friendly)</label>
            <input type="text" name="<?php echo $field_name_prefix; ?>slug" id="<?php echo $field_name_prefix; ?>slug"
                   value="<?php echo esc_html($form_data[$field_name_prefix . 'slug'] ?? ($form_data['slug'] ?? $category_slug)); ?>"
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm bg-gray-50"
                   placeholder="auto-generated-from-name">
            <p class="text-xs text-gray-500 mt-1">If left blank, this will be auto-generated. Must be unique.</p>
        </div>

        <div>
            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
            <select name="parent_id" id="parent_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                <option value="0">-- None (Top Level) --</option>
                <?php echo generate_category_options($category_tree_for_select, $current_parent_id_for_form, $exclude_id_for_form); ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">Select a parent category to create a hierarchy.</p>
        </div>

        <div>
            <label for="<?php echo $field_name_prefix; ?>description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="<?php echo $field_name_prefix; ?>description" id="<?php echo $field_name_prefix; ?>description" rows="4"
                      class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"><?php echo esc_html($form_data[$field_name_prefix . 'description'] ?? ($form_data['description'] ?? $category_description)); ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Optional. A brief description of the category.</p>
        </div>
        
        <div class="pt-5 border-t border-gray-200">
            <div class="flex justify-end space-x-3">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    Cancel
                </a>
                <button type="submit" name="submit_category" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2.5 px-5 rounded-lg shadow-md transition-colors flex items-center">
                    <i data-lucide="<?php echo $is_editing ? 'save' : 'plus-circle'; ?>" class="w-5 h-5 mr-2"></i>
                    <?php echo $is_editing ? 'Save Changes' : 'Add Category'; ?>
                </button>
            </div>
        </div>
    </form>
</div>
<script>
// Slugify function (can be in a global admin_script.js later)
function slugify(text) {
    if (typeof text !== 'string') return '';
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}

// Auto-update slug from name field (specific to the prefix used)
const nameField = document.getElementById('<?php echo $field_name_prefix; ?>name');
const slugField = document.getElementById('<?php echo $field_name_prefix; ?>slug');

if (nameField && slugField) {
    nameField.addEventListener('input', function() {
        if (!slugField.value.trim() || slugField.dataset.autoGenerated === 'true') {
            slugField.value = slugify(this.value);
            slugField.dataset.autoGenerated = 'true';
        }
    });
    slugField.addEventListener('input', function() {
        this.dataset.autoGenerated = 'false';
    });
}


if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
