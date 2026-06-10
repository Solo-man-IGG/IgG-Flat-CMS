<?php

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
use CMS\ContactHandler;
use CMS\Mailer;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize components
$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);
$mailer = new Mailer($fileHandler);
$contactHandler = new ContactHandler($fileHandler, $mailer);

// Require authentication
$auth->requireAuth();

$pageTitle = '留言管理';
$currentPage = 'messages';
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
                case 'mark_replied':
                    $filename = $_POST['filename'] ?? '';
                    if ($filename) {
                        if ($contactHandler->markAsReplied($filename)) {
                            $message = '留言已標記為已回覆。';
                        } else {
                            $error = '操作失敗。';
                        }
                    }
                    break;
                    
                case 'delete':
                    $filename = $_POST['filename'] ?? '';
                    if ($filename) {
                        if ($contactHandler->deleteMessage($filename)) {
                            $message = '留言已刪除。';
                        } else {
                            $error = '操作失敗。';
                        }
                    }
                    break;
                    
                case 'send_reply':
                    $filename = $_POST['filename'] ?? '';
                    $replyBody = $_POST['reply_body'] ?? '';
                    
                    if ($filename && $replyBody) {
                        $msg = $contactHandler->getMessage($filename);
                        
                        if ($msg) {
                            if ($mailer->sendReply($msg['email'], $msg['name'], $replyBody)) {
                                $contactHandler->markAsReplied($filename);
                                $message = '回覆已發送。';
                            } else {
                                $error = '發送回覆失敗，請檢查郵件設定。';
                            }
                        } else {
                            $error = '找不到留言。';
                        }
                    } else {
                        $error = '請填寫回覆內容。';
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = '操作失敗：' . $e->getMessage();
        }
    }
}

// Get all messages
$messages = $contactHandler->getAllMessages();

$csrfField = $auth->getCsrfField();
$mailerConfigured = $mailer->isConfigured();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1>留言管理</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if (!$mailerConfigured): ?>
            <div class="alert alert-error">
                郵件功能尚未設定。請前往 <a href="/admin/settings">系統設定</a> 配置 SMTP 設定以啟用回覆功能。
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>留言列表</h3>
            <?php if (!empty($messages)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>姓名</th>
                            <th>電子郵件</th>
                            <th>主題</th>
                            <th>日期</th>
                            <th>狀態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($msg['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ($msg['replied']): ?>
                                        <span style="color: #10b981;">已回覆</span>
                                    <?php else: ?>
                                        <span style="color: #f59e0b;">未回覆</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn" onclick="showReplyModal('<?php echo htmlspecialchars($msg['filename'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8'); ?>')">回覆</button>
                                    
                                    <?php if (!$msg['replied']): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="action" value="mark_replied">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($msg['filename'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-success">標記已回覆</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($msg['filename'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此留言？');">刪除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>目前沒有留言。</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="replyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; padding: 2rem; border-radius: 0.5rem; max-width: 600px; margin: 100px auto; max-height: 80vh; overflow-y: auto;">
            <h3>回覆留言</h3>
            <div style="margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 0.375rem;">
                <p><strong>來自：</strong> <span id="modalName"></span> (<span id="modalEmail"></span>)</p>
                <p><strong>主題：</strong> <span id="modalSubject"></span></p>
                <p><strong>原始訊息：</strong></p>
                <p id="modalMessage" style="white-space: pre-wrap; background: white; padding: 0.5rem; border-radius: 0.25rem;"></p>
            </div>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="send_reply">
                <input type="hidden" name="filename" id="modalFilename">
                
                <div class="form-group">
                    <label for="reply_body">回覆內容 *</label>
                    <textarea id="reply_body" name="reply_body" rows="10" required></textarea>
                </div>
                
                <button type="submit" class="btn">發送回覆</button>
                <button type="button" class="btn btn-danger" onclick="hideReplyModal()">取消</button>
            </form>
        </div>
    </div>
    
    <script>
        var replyEditor = null;

        function showReplyModal(filename, name, email, subject, message) {
            document.getElementById('modalFilename').value = filename;
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalEmail').textContent = email;
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('replyModal').style.display = 'block';

            if (!replyEditor) {
                replyEditor = new EasyMDE({
                    element: document.getElementById('reply_body'),
                    forceSync: true,
                    spellChecker: false,
                    toolbar: typeof easyMdeToolbar !== 'undefined' ? easyMdeToolbar : undefined,
                    renderingConfig: {
                        singleLineBreaks: true,
                        codeSyntaxHighlighting: true
                    }
                });
            }
        }

        function hideReplyModal() {
            if (replyEditor) {
                replyEditor.toTextArea();
                replyEditor = null;
            }
            document.getElementById('replyModal').style.display = 'none';
            document.getElementById('reply_body').value = '';
        }

        document.getElementById('replyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideReplyModal();
            }
        });
    </script>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
