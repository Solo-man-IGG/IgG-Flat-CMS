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

$pageTitle = __('admin.signature.page_title');
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
    $error = __('admin.signature.error.load_failed', $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $signature = $_POST['signature'] ?? '';
    
    // Validate CSRF token
    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = __('admin.signature.error.csrf');
    } else {
        try {
            // Save signature
            $fileHandler->write('content/config/signature.txt', $signature);
            
            // Clear all cache to make sure changes appear immediately
            $cache->clear('blog', '');
            $cache->clear('pages', '');
            
            $message = __('admin.signature.message.saved');
        } catch (\Exception $e) {
            $error = __('admin.signature.error.save_failed', $e->getMessage());
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

<div class="admin-content">
    <h1><?php echo __('admin.signature.heading'); ?></h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3><?php echo __('admin.signature.form.title'); ?></h3>
        <form method="POST">
            <?php echo $csrfField; ?>
            
            <div class="form-group">
                <label for="signature"><?php echo __('admin.signature.form.signature'); ?></label>
                <textarea id="signature" name="signature" data-easymde rows="5" placeholder="<?php echo __('admin.signature.form.placeholder'); ?>"><?php echo htmlspecialchars($currentSignature, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <p class="help-text"><?php echo __('admin.signature.form.help'); ?></p>
            </div>
            
            <button type="submit" class="btn"><?php echo __('admin.signature.form.save'); ?></button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>