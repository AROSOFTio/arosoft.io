<?php
// admin/pages/manage_users.php
defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
global $conn;

// ini_set('display_errors', 1); // Uncomment for debugging
// error_reporting(E_ALL);

$sql = "SELECT id, username, email, full_name, role, created_at FROM admin_users ORDER BY username ASC";
$result = $conn->query($sql);
$users = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Manage Admin Users</h2>
        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_user" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors flex items-center">
            <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i>Add New User
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded-md <?php echo $_SESSION['flash_message_type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>" role="alert">
            <?php echo esc_html($_SESSION['flash_message']); ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-lg rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user_item): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 hover:text-admin-primary">
                                    <a href="<?php echo $admin_base_url; ?>index.php?admin_page=edit_user&id=<?php echo (int)($user_item['id'] ?? 0); ?>">
                                        <?php echo esc_html($user_item['username'] ?? 'N/A'); ?>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo esc_html($user_item['full_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo esc_html($user_item['email'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo esc_html(ucfirst($user_item['role'] ?? 'N/A')); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($user_item['created_at'] ?? null, 'M j, Y'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <?php
                                    $user_id_val = $user_item['id'] ?? null;
                                    $csrf_token_val = '';
                                     if (function_exists('generate_csrf_token')) {
                                        $csrf_token_val = generate_csrf_token();
                                    }

                                    if (!empty($user_id_val)) {
                                        $edit_link = $admin_base_url . 'index.php?admin_page=edit_user&id=' . (int)$user_id_val;
                                        echo "<a href='" . esc_html($edit_link) . "' class='text-indigo-600 hover:text-indigo-900 inline-block p-1' title='Edit User'><i data-lucide='edit-2' class='w-4 h-4'></i></a>";
                                        
                                        $can_delete = true;
                                        if (isset($_SESSION['admin_user_id']) && $_SESSION['admin_user_id'] == $user_id_val) {
                                            $can_delete = false; 
                                        }
                                        // Add logic for last admin if needed

                                        if ($can_delete) {
                                            if (!empty($csrf_token_val)) {
                                                $delete_link = $admin_base_url . 'actions/delete_user.php?id=' . (int)$user_id_val . '&csrf_token=' . $csrf_token_val;
                                                echo "<a href='" . esc_html($delete_link) . "' onclick=\"return confirm('Are you sure you want to delete this user?');\" class='text-red-600 hover:text-red-900 inline-block p-1' title='Delete User'><i data-lucide='trash-2' class='w-4 h-4'></i></a>";
                                            } else {
                                                 echo "";
                                            }
                                        } else {
                                            echo "<span class='text-gray-400 inline-block p-1' title='Cannot delete self'><i data-lucide='trash-2' class='w-4 h-4'></i></span>";
                                        }
                                    } else {
                                        echo "";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                            No admin users found. <a href="<?php echo $admin_base_url; ?>index.php?admin_page=add_user" class="text-admin-primary hover:underline">Add one now!</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
