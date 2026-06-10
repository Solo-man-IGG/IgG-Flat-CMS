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

// Get statistics
$stats = [
    'blog_posts' => 0,
    'products' => 0,
    'pages' => 0,
    'messages' => 0,
];

try {
    $stats['blog_posts'] = count($fileHandler->listFiles('content/blog', 'md'));
    $stats['products'] = count($fileHandler->listFiles('content/products', 'md'));
    $stats['pages'] = count($fileHandler->listFiles('content/pages', 'md'));
    $stats['messages'] = count($fileHandler->listFiles('content/messages', 'json'));
} catch (\Exception $e) {
    // Keep default values if error occurs
}

$cacheStats = $cache->getStats();

$pageTitle = '儀表板';
$currentPage = 'dashboard';
$username = $auth->getUsername();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1>歡迎回來，<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></h1>
        
        <div class="stat-grid">
            <div class="stat-card">
                <div class="number"><?php echo $stats['blog_posts']; ?></div>
                <div class="label">文章數量</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['products']; ?></div>
                <div class="label">產品數量</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['pages']; ?></div>
                <div class="label">頁面數量</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['messages']; ?></div>
                <div class="label">留言數量</div>
            </div>
        </div>
        
        <div class="card">
            <h3>系統資訊</h3>
            <table>
                <tr>
                    <th>項目</th>
                    <th>數值</th>
                </tr>
                <tr>
                    <td>PHP 版本</td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td>伺服器時間</td>
                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                </tr>
                <tr>
                    <td>快取檔案數量</td>
                    <td><?php echo $cacheStats['count']; ?></td>
                </tr>
                <tr>
                    <td>快取大小</td>
                    <td><?php echo $cacheStats['size_human']; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h3>快速操作</h3>
            <p>
                <a href="/admin/blog" class="btn">新增文章</a>
                <a href="/admin/products" class="btn">新增產品</a>
                <a href="/admin/pages" class="btn">新增頁面</a>
                <a href="/admin/settings" class="btn">系統設定</a>
            </p>
        </div>
    </div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
