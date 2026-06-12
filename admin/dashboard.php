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

$pageTitle = __('admin.dashboard.page_title');
$currentPage = 'dashboard';
$username = $auth->getUsername();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1><?php echo __('admin.dashboard.welcome', htmlspecialchars($username, ENT_QUOTES, 'UTF-8')); ?></h1>
        
        <div class="stat-grid">
            <div class="stat-card">
                <div class="number"><?php echo $stats['blog_posts']; ?></div>
                <div class="label"><?php echo __('admin.dashboard.stat_blog_posts'); ?></div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['products']; ?></div>
                <div class="label"><?php echo __('admin.dashboard.stat_products'); ?></div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['pages']; ?></div>
                <div class="label"><?php echo __('admin.dashboard.stat_pages'); ?></div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['messages']; ?></div>
                <div class="label"><?php echo __('admin.dashboard.stat_messages'); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.dashboard.system_info.title'); ?></h3>
            <table>
                <tr>
                    <th><?php echo __('admin.dashboard.system_info.col_item'); ?></th>
                    <th><?php echo __('admin.dashboard.system_info.col_value'); ?></th>
                </tr>
                <tr>
                    <td><?php echo __('admin.dashboard.system_info.php_version'); ?></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><?php echo __('admin.dashboard.system_info.server_time'); ?></td>
                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                </tr>
                <tr>
                    <td><?php echo __('admin.dashboard.system_info.cache_count'); ?></td>
                    <td><?php echo $cacheStats['count']; ?></td>
                </tr>
                <tr>
                    <td><?php echo __('admin.dashboard.system_info.cache_size'); ?></td>
                    <td><?php echo $cacheStats['size_human']; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.dashboard.quick_actions.title'); ?></h3>
            <p>
                <a href="/admin/blog" class="btn"><?php echo __('admin.dashboard.quick_actions.new_blog'); ?></a>
                <a href="/admin/products" class="btn"><?php echo __('admin.dashboard.quick_actions.new_product'); ?></a>
                <a href="/admin/pages" class="btn"><?php echo __('admin.dashboard.quick_actions.new_page'); ?></a>
                <a href="/admin/settings" class="btn"><?php echo __('admin.dashboard.quick_actions.settings'); ?></a>
            </p>
        </div>
    </div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
