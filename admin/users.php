<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../libs/functions.php';

use CMS\Auth;
use CMS\FileHandler;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize components
$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);

// Require authentication
$auth->requireAuth();

$pageTitle = __('admin.users.page_title');
$currentPage = 'users';
$username = $auth->getUsername();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = __('admin.users.error.csrf');
    } else {
        try {
            switch ($action) {
                case 'delete':
                    $userId = $_POST['user_id'] ?? '';
                    if ($userId) {
                        if ($auth->deleteUser($userId)) {
                            $message = __('admin.users.message.deleted');
                        } else {
                            $error = __('admin.users.error.cannot_delete_last_admin');
                        }
                    }
                    break;
                    
                case 'create':
                    $newUsername = $_POST['username'] ?? '';
                    $newPassword = $_POST['password'] ?? '';
                    $newEmail = $_POST['email'] ?? '';
                    $newRole = $_POST['role'] ?? 'admin';
                    
                        if (!$newUsername || !$newPassword || !$newEmail) {
                            $error = __('admin.users.error.empty_fields');
                    } elseif (strlen($newPassword) < 6) {
                        $error = __('admin.users.error.password_too_short');
                    } else {
                        if ($auth->createUser($newUsername, $newPassword, $newEmail, $newRole)) {
                            $message = __('admin.users.message.created');
                        } else {
                            $error = __('admin.users.error.username_exists');
                        }
                    }
                    break;
                    
                case 'update':
                    $userId = $_POST['user_id'] ?? '';
                    $updateEmail = $_POST['email'] ?? '';
                    $updateRole = $_POST['role'] ?? 'admin';
                    $updatePassword = $_POST['password'] ?? '';
                    
                    if (!$userId || !$updateEmail) {
                        $error = __('admin.users.error.id_email_empty');
                    } else {
                        $data = [
                            'email' => $updateEmail,
                            'role' => $updateRole
                        ];
                        
                        if (!empty($updatePassword)) {
                            if (strlen($updatePassword) < 6) {
                                $error = __('admin.users.error.password_too_short');
                            } else {
                                $data['password'] = $updatePassword;
                            }
                        }
                        
                        if (!$error) {
                            if ($auth->updateUser($userId, $data)) {
                                $message = __('admin.users.message.updated');
                            } else {
                                $error = __('admin.users.error.update_failed');
                            }
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = __('admin.users.error.operation_failed', $e->getMessage());
        }
    }
}

// Get all users
$users = $auth->getAllUsers();

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1><?php echo __('admin.users.heading'); ?></h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo __('admin.users.add_section.title'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="username"><?php echo __('admin.users.add_section.username'); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo __('admin.users.add_section.password'); ?></label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="email"><?php echo __('admin.users.add_section.email'); ?></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="role"><?php echo __('admin.users.add_section.role'); ?></label>
                    <select id="role" name="role">
                        <option value="admin"><?php echo __('admin.users.role.admin'); ?></option>
                        <option value="editor"><?php echo __('admin.users.role.editor'); ?></option>
                    </select>
                </div>
                
                <button type="submit" class="btn"><?php echo __('admin.users.add_section.create'); ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.users.list.title'); ?></h3>
            <?php if (!empty($users)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('admin.users.list.col_username'); ?></th>
                            <th><?php echo __('admin.users.list.col_email'); ?></th>
                            <th><?php echo __('admin.users.list.col_role'); ?></th>
                            <th><?php echo __('admin.users.list.col_created'); ?></th>
                            <th><?php echo __('admin.users.list.col_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                                        <span style="color: #ef4444; font-weight: bold;"><?php echo __('admin.users.role.admin'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #2563eb;"><?php echo __('admin.users.role.editor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <button type="button" class="btn" onclick="showEditForm('<?php echo htmlspecialchars($user['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')"><?php echo __('admin.users.list.edit'); ?></button>
                                    <?php if (($user['username'] ?? '') !== $username): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('admin.users.list.confirm_delete'); ?>');"><?php echo __('admin.users.list.delete'); ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #6b7280; font-size: 0.875rem;"><?php echo __('admin.users.list.current_user_label'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo __('admin.users.list.empty'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="card" id="edit-card" style="display: none;">
            <h3><?php echo __('admin.users.edit_section.title'); ?></h3>
            <form method="POST" id="edit-form">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit-user-id">
                
                <div class="form-group">
                    <label><?php echo __('admin.users.edit_section.username'); ?></label>
                    <input type="text" id="edit-username" disabled style="background-color: #f3f4f6;">
                </div>
                
                <div class="form-group">
                    <label for="edit-email"><?php echo __('admin.users.edit_section.email'); ?></label>
                    <input type="email" id="edit-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-role"><?php echo __('admin.users.edit_section.role'); ?></label>
                    <select id="edit-role" name="role">
                        <option value="admin"><?php echo __('admin.users.role.admin'); ?></option>
                        <option value="editor"><?php echo __('admin.users.role.editor'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit-password"><?php echo __('admin.users.edit_section.password'); ?></label>
                    <input type="password" id="edit-password" name="password" minlength="6">
                    <small style="color: #6b7280;"><?php echo __('admin.users.edit_section.password_help'); ?></small>
                </div>
                
                <button type="submit" class="btn"><?php echo __('admin.users.edit_section.update'); ?></button>
                <button type="button" class="btn" onclick="hideEditForm()" style="background-color: #6b7280;"><?php echo __('admin.users.edit_section.cancel'); ?></button>
            </form>
        </div>
    </div>

    <script>
        function showEditForm(userId, username, email, role) {
            document.getElementById('edit-card').style.display = 'block';
            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-role').value = role;
            document.getElementById('edit-card').scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideEditForm() {
            document.getElementById('edit-card').style.display = 'none';
            document.getElementById('edit-form').reset();
        }
    </script>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
