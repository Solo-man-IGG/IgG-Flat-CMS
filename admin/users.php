<?php

defined("CMS_ENTRY") or die("Direct access not allowed.");

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';

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

$pageTitle = '使用者管理';
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
        $error = 'CSRF 驗證失敗，請重新整理頁面後再試。';
    } else {
        try {
            switch ($action) {
                case 'delete':
                    $userId = $_POST['user_id'] ?? '';
                    if ($userId) {
                        if ($auth->deleteUser($userId)) {
                            $message = '使用者已刪除。';
                        } else {
                            $error = '無法刪除使用者（可能是最後一個管理員）。';
                        }
                    }
                    break;
                    
                case 'create':
                    $newUsername = $_POST['username'] ?? '';
                    $newPassword = $_POST['password'] ?? '';
                    $newEmail = $_POST['email'] ?? '';
                    $newRole = $_POST['role'] ?? 'admin';
                    
                    if (!$newUsername || !$newPassword || !$newEmail) {
                        $error = '使用者名稱、密碼和信箱不能為空。';
                    } elseif (strlen($newPassword) < 6) {
                        $error = '密碼至少需要 6 個字元。';
                    } else {
                        if ($auth->createUser($newUsername, $newPassword, $newEmail, $newRole)) {
                            $message = '使用者已建立。';
                        } else {
                            $error = '使用者名稱已存在。';
                        }
                    }
                    break;
                    
                case 'update':
                    $userId = $_POST['user_id'] ?? '';
                    $updateEmail = $_POST['email'] ?? '';
                    $updateRole = $_POST['role'] ?? 'admin';
                    $updatePassword = $_POST['password'] ?? '';
                    
                    if (!$userId || !$updateEmail) {
                        $error = '使用者 ID 和信箱不能為空。';
                    } else {
                        $data = [
                            'email' => $updateEmail,
                            'role' => $updateRole
                        ];
                        
                        if (!empty($updatePassword)) {
                            if (strlen($updatePassword) < 6) {
                                $error = '密碼至少需要 6 個字元。';
                            } else {
                                $data['password'] = $updatePassword;
                            }
                        }
                        
                        if (!$error) {
                            if ($auth->updateUser($userId, $data)) {
                                $message = '使用者已更新。';
                            } else {
                                $error = '更新使用者失敗。';
                            }
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = '操作失敗：' . $e->getMessage();
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
        <h1>使用者管理</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>新增使用者</h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="username">使用者名稱 *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密碼 * (至少 6 個字元)</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="email">信箱 *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="role">角色</label>
                    <select id="role" name="role">
                        <option value="admin">管理員</option>
                        <option value="editor">編輯者</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">建立使用者</button>
            </form>
        </div>
        
        <div class="card">
            <h3>現有使用者</h3>
            <?php if (!empty($users)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>使用者名稱</th>
                            <th>信箱</th>
                            <th>角色</th>
                            <th>建立日期</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                                        <span style="color: #ef4444; font-weight: bold;">管理員</span>
                                    <?php else: ?>
                                        <span style="color: #2563eb;">編輯者</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <button type="button" class="btn" onclick="showEditForm('<?php echo htmlspecialchars($user['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">編輯</button>
                                    <?php if (($user['username'] ?? '') !== $username): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此使用者？');">刪除</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #6b7280; font-size: 0.875rem;">（目前登入）</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>目前沒有使用者。</p>
            <?php endif; ?>
        </div>
        
        <div class="card" id="edit-card" style="display: none;">
            <h3>編輯使用者</h3>
            <form method="POST" id="edit-form">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit-user-id">
                
                <div class="form-group">
                    <label>使用者名稱</label>
                    <input type="text" id="edit-username" disabled style="background-color: #f3f4f6;">
                </div>
                
                <div class="form-group">
                    <label for="edit-email">信箱 *</label>
                    <input type="email" id="edit-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-role">角色</label>
                    <select id="edit-role" name="role">
                        <option value="admin">管理員</option>
                        <option value="editor">編輯者</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit-password">新密碼 (留空則不變更)</label>
                    <input type="password" id="edit-password" name="password" minlength="6">
                    <small style="color: #6b7280;">若要變更密碼，請輸入至少 6 個字元的新密碼</small>
                </div>
                
                <button type="submit" class="btn">更新使用者</button>
                <button type="button" class="btn" onclick="hideEditForm()" style="background-color: #6b7280;">取消</button>
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
