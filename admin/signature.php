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
use CMS\Cache;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize components
$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);
$cache = new Cache($fileHandler);

// Require authentication
$auth->requireAuth();

$pageTitle = '文章簽名管理';
$currentPage = 'signature';
$username = $auth->getUsername();

$message = '';
$error = '';

// Read current signature
$currentSignature = '';
try {
    $signaturePath = 'content/config/signature.txt';
    if ($fileHandler->exists($signaturePath)) {
        $currentSignature = $fileHandler->read($signaturePath);
    }
} catch (\Exception $e) {
    $error = '讀取簽名失败：' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $signature = $_POST['signature'] ?? '';
    
    // Validate CSRF token
    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = 'CSRF 驗證失敗，請重新整理頁面後再試。';
    } else {
        try {
            // Save signature
            $fileHandler->write('content/config/signature.txt', $signature);
            
            // Clear all cache to make sure changes appear immediately
            $cache->clear('blog', '');
            $cache->clear('pages', '');
            
            $message = '簽名已儲存。';
        } catch (\Exception $e) {
            $error = '儲存失敗：' . $e->getMessage();
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

<div class="admin-content">
    <h1>文章簽名管理</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3>設定文章簽名</h3>
        <form method="POST">
            <?php echo $csrfField; ?>
            
            <div class="form-group">
                <label for="signature">文章簽名</label>
                <textarea id="signature" name="signature" data-easymde rows="5" placeholder="例如：感謝閱讀，歡迎留言。"><?php echo htmlspecialchars($currentSignature, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <p class="help-text">此簽名將自動附加到所有文章末尾。</p>
            </div>
            
            <button type="submit" class="btn">儲存簽名</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>