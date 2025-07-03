<?php
// admin/pages/add_edit_user.php
defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
global $conn;

$is_editing = false;
$user_id = null;
$username = '';
$email = '';
$full_name = '';
$role = 'editor'; // Default role for new users

$form_action_target = 'add_user_process.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $is_editing = true;
    $user_id = (int)$_GET['id'];
    $form_action_target = 'edit_user_process.php?id=' . $user_id;

    $stmt = $conn->prepare("SELECT username, email, full_name, role FROM admin_users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $username = $user_data['username'];
            $email = $user_data['email'];
            $full_name = $user_data['full_name'];
            $role = $user_data['role'];
        } else {
            $_SESSION['flash_message'] = "User not found.";
            $_SESSION['flash_message_type'] = "error";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
            exit;
        }
        $stmt->close();
    } else {
        error_log("DB Error fetching user for edit: " . $conn->error);
        $_SESSION['flash_message'] = "Database error fetching user details.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=users');
        exit;
    }
}
$form_action = $admin_base_url . 'actions/' . $form_action_target;

// Retrieve form data from session if validation failed
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

$available_roles = ['admin', 'editor']; // Define available roles
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800"><?php echo $is_editing ? 'Edit User' : 'Add New User'; ?></h2>
        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=users" class="text-admin-primary hover:underline text-sm flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>Back to All Users
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

    <form action="<?php echo $form_action; ?>" method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-6">
        <?php echo generate_csrf_input(); ?>
        
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
            <input type="text" name="username" id="username" value="<?php echo esc_html($form_data['username'] ?? $username); ?>" required 
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
            <input type="email" name="email" id="email" value="<?php echo esc_html($form_data['email'] ?? $email); ?>" required
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
        </div>
        
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="<?php echo esc_html($form_data['full_name'] ?? $full_name); ?>"
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <?php echo !$is_editing ? '<span class="text-red-500">*</span>' : '(Leave blank to keep current)'; ?></label>
            <input type="password" name="password" id="password" <?php echo !$is_editing ? 'required' : ''; ?>
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
            <?php if ($is_editing): ?>
            <p class="text-xs text-gray-500 mt-1">Only fill this field if you want to change the user's password.</p>
            <?php endif; ?>
        </div>
        
        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <?php echo !$is_editing ? '<span class="text-red-500">*</span>' : ''; ?></label>
            <input type="password" name="password_confirm" id="password_confirm" <?php echo !$is_editing ? 'required' : ''; ?>
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
             <p class="text-xs text-gray-500 mt-1">Required if setting or changing password.</p>
        </div>

        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
            <select name="role" id="role" required class="mt-1 block w-full px-3 py-2.5 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                <?php foreach ($available_roles as $role_value): ?>
                    <option value="<?php echo $role_value; ?>" <?php echo (($form_data['role'] ?? $role) === $role_value ? 'selected' : ''); ?>>
                        <?php echo ucfirst($role_value); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="pt-5 border-t border-gray-200">
            <div class="flex justify-end space-x-3">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=users" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    Cancel
                </a>
                <button type="submit" name="submit_user" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2.5 px-5 rounded-lg shadow-md transition-colors flex items-center">
                    <i data-lucide="<?php echo $is_editing ? 'save' : 'user-plus'; ?>" class="w-5 h-5 mr-2"></i>
                    <?php echo $is_editing ? 'Save Changes' : 'Add User'; ?>
                </button>
            </div>
        </div>
    </form>
</div>
<script>
if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
