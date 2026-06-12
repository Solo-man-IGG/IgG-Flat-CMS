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

$pageTitle = __('admin.messages.page_title');
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
        $error = __('admin.messages.error.csrf');
    } else {
        try {
            switch ($action) {
                case 'mark_replied':
                    $filename = $_POST['filename'] ?? '';
                    if ($filename) {
                        if ($contactHandler->markAsReplied($filename)) {
                            $message = __('admin.messages.message.marked_replied');
                        } else {
                            $error = __('admin.messages.error.operation_failed_short');
                        }
                    }
                    break;
                    
                case 'delete':
                    $filename = $_POST['filename'] ?? '';
                    if ($filename) {
                        if ($contactHandler->deleteMessage($filename)) {
                            $message = __('admin.messages.message.deleted');
                        } else {
                            $error = __('admin.messages.error.operation_failed_short');
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
                                $message = __('admin.messages.message.reply_sent');
                            } else {
                                $error = __('admin.messages.error.reply_failed');
                            }
                        } else {
                            $error = __('admin.messages.error.not_found');
                        }
                    } else {
                        $error = __('admin.messages.error.reply_empty');
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = __('admin.messages.error.operation_failed', $e->getMessage());
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
        <h1><?php echo __('admin.messages.heading'); ?></h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if (!$mailerConfigured): ?>
            <div class="alert alert-error">
                <?php echo __('admin.messages.warning.mail_not_configured'); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo __('admin.messages.list.title'); ?></h3>
            <?php if (!empty($messages)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('admin.messages.list.col_name'); ?></th>
                            <th><?php echo __('admin.messages.list.col_email'); ?></th>
                            <th><?php echo __('admin.messages.list.col_subject'); ?></th>
                            <th><?php echo __('admin.messages.list.col_date'); ?></th>
                            <th><?php echo __('admin.messages.list.col_status'); ?></th>
                            <th><?php echo __('admin.messages.list.col_actions'); ?></th>
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
                                        <span style="color: #10b981;"><?php echo __('admin.messages.list.status_replied'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #f59e0b;"><?php echo __('admin.messages.list.status_unreplied'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn" onclick="showReplyModal('<?php echo htmlspecialchars($msg['filename'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8'); ?>')"><?php echo __('admin.messages.list.reply'); ?></button>
                                    
                                    <?php if (!$msg['replied']): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="action" value="mark_replied">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($msg['filename'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-success"><?php echo __('admin.messages.list.mark_replied'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($msg['filename'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('admin.messages.list.confirm_delete'); ?>');"><?php echo __('admin.messages.list.delete'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo __('admin.messages.list.empty'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="replyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; padding: 2rem; border-radius: 0.5rem; max-width: 600px; margin: 100px auto; max-height: 80vh; overflow-y: auto;">
            <h3><?php echo __('admin.messages.modal.title'); ?></h3>
            <div style="margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 0.375rem;">
                <p><strong><?php echo __('admin.messages.modal.from_label', '<span id="modalName"></span> (<span id="modalEmail"></span>)'); ?></strong></p>
                <p><strong><?php echo __('admin.messages.modal.subject_label', '<span id="modalSubject"></span>'); ?></strong></p>
                <p><strong><?php echo __('admin.messages.modal.original_message_label'); ?></strong></p>
                <p id="modalMessage" style="white-space: pre-wrap; background: white; padding: 0.5rem; border-radius: 0.25rem;"></p>
            </div>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="send_reply">
                <input type="hidden" name="filename" id="modalFilename">
                
                <div class="form-group">
                    <label for="reply_body"><?php echo __('admin.messages.modal.reply_body'); ?></label>
                    <textarea id="reply_body" name="reply_body" rows="10" required></textarea>
                </div>
                
                <button type="submit" class="btn"><?php echo __('admin.messages.modal.send'); ?></button>
                <button type="button" class="btn btn-danger" onclick="hideReplyModal()"><?php echo __('admin.messages.modal.cancel'); ?></button>
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
