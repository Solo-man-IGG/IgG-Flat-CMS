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
use CMS\MenuManager;
use CMS\Cache;
use CMS\MarkdownParser;
use CMS\Mailer;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize components
$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);
$menuManager = new MenuManager($fileHandler);
$cache = new Cache($fileHandler);
$parser = new MarkdownParser();

// Require authentication
$auth->requireAuth();

$pageTitle = '系統設定';
$currentPage = 'settings';
$username = $auth->getUsername();

$message = '';
$error = '';

// Load settings
$settings = [];
try {
    $settingsJson = $fileHandler->read('content/config/settings.json');
    $settings = json_decode($settingsJson, true) ?? [];
} catch (\Exception $e) {
    $settings = [
        'site_title' => 'My Site',
        'mail_host' => '',
        'mail_port' => '587',
        'mail_username' => '',
        'mail_password' => '',
        'mail_from' => '',
        'mail_from_name' => '',
    ];
}

// Load menu items
$menuItems = $menuManager->getMenuItems();

// Load existing pages for dropdown
$existingPages = [];
try {
    $files = $fileHandler->listFiles('content/pages', 'md');
    foreach ($files as $file) {
        $path = 'content/pages/' . $file;
        $parsed = $parser->parseFile($fileHandler, $path);
        $frontmatter = $parsed['frontmatter'];
        $slug = $parser->getSlug($frontmatter, $file);
        $title = $parser->getTitle($frontmatter, $parsed['content']);
        $existingPages[$slug] = $title;
    }
} catch (\Exception $e) {
    $existingPages = [];
}

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
                case 'save_settings':
                    // Load existing settings
                    $currentSettingsJson = $fileHandler->read('content/config/settings.json');
                    $currentSettings = json_decode($currentSettingsJson, true) ?? [];
                    
                    $newSettings = $currentSettings;
                    $fields = [
                        'site_title', 'mail_host', 'mail_port',
                        'mail_username', 'mail_from', 'mail_from_name',
                        'home_page'
                    ];

                    foreach ($fields as $field) {
                        if (isset($_POST[$field])) {
                            $newSettings[$field] = $_POST[$field];
                        }
                    }
                    
                    // Only update password if a new one is provided
                    if (!empty($_POST['mail_password'])) {
                        $newSettings['mail_password'] = $_POST['mail_password'];
                    }
                    
                    $fileHandler->write('content/config/settings.json', json_encode($newSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $settings = $newSettings;
                    $message = '設定已儲存。';
                    break;
                    
                case 'save_menu':
                    $menuData = [];
                    $types = $_POST['type'] ?? [];
                    $labels = $_POST['label'] ?? [];
                    $menuNums = $_POST['menu_num'] ?? [];
                    $enabled = $_POST['enabled'] ?? [];
                    
                    foreach ($types as $index => $type) {
                        $menuData[] = [
                            'type' => $type,
                            'label' => $labels[$index] ?? '',
                            'menu_num' => intval($menuNums[$index] ?? 0),
                            'enabled' => isset($enabled[$index]),
                        ];
                    }
                    
                    $menuManager->saveMenu($menuData);
                    $cache->clearAll();
                    $menuItems = $menuManager->getMenuItems();
                    $message = '選單已儲存。';
                    break;
                    
                case 'clear_cache':
                    $cache->clearAll();
                    $message = '快取已清除。';
                    break;
                    
                case 'test_email':
                    $testTo = $_POST['test_email_to'] ?? '';
                    if (!$testTo || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                        $error = '請輸入有效的測試收件信箱。';
                    } else {
                        $mailer = new Mailer($fileHandler);
                        $mailer->reloadSettings();
                        $siteTitle = $settings['site_title'] ?? 'IgG Flat CMS - Lightweight Flat-File CMS';
                        $subject = $siteTitle . ' 郵件測試';
                        $body = '<h3>這是一封測試郵件</h3><p>若您收到此信，表示 SMTP 設定正常。</p><p>時間：' . date('Y-m-d H:i:s') . '</p>';
                        $altBody = '這是一封測試郵件。若您收到此信，表示 SMTP 設定正常。時間：' . date('Y-m-d H:i:s');
                        
                        if ($mailer->send($testTo, '測試收件者', $subject, $body, $altBody)) {
                            $message = '測試郵件已發送至 ' . htmlspecialchars($testTo, ENT_QUOTES, 'UTF-8');
                        } else {
                            $error = '發送失敗：' . ($mailer->getLastError() ?: '未知錯誤，請檢查 error_log');
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = '操作失敗：' . $e->getMessage();
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1>系統設定</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>網站設定</h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-group">
                    <label for="site_title">網站標題</label>
                    <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <button type="submit" class="btn">儲存設定</button>
            </form>
        </div>
        
        <div class="card">
            <h3>郵件設定</h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-group">
                    <label for="mail_host">SMTP 主機</label>
                    <input type="text" id="mail_host" name="mail_host" value="<?php echo htmlspecialchars($settings['mail_host'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="smtp.example.com">
                </div>
                
                <div class="form-group">
                    <label for="mail_port">SMTP 埠號</label>
                    <input type="number" id="mail_port" name="mail_port" value="<?php echo htmlspecialchars($settings['mail_port'] ?? '587', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mail_username">SMTP 使用者名稱</label>
                    <input type="text" id="mail_username" name="mail_username" value="<?php echo htmlspecialchars($settings['mail_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mail_password">SMTP 密碼</label>
                    <input type="password" id="mail_password" name="mail_password" placeholder="<?php echo $settings['mail_password'] ? '（已設定，留空不修改）' : ''; ?>">
                    <p class="form-help">亦可透過環境變數 MAIL_PASSWORD 設定（優先於此欄位）</p>
                </div>
                
                <div class="form-group">
                    <label for="mail_from">寄件者信箱</label>
                    <input type="email" id="mail_from" name="mail_from" value="<?php echo htmlspecialchars($settings['mail_from'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mail_from_name">寄件者名稱</label>
                    <input type="text" id="mail_from_name" name="mail_from_name" value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <button type="submit" class="btn">儲存郵件設定</button>
            </form>
        </div>
        
        <div class="card">
            <h3>選單設定</h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_menu">
                
                <table>
                    <thead>
                        <tr>
                            <th>類型</th>
                            <th>標籤</th>
                            <th>順序</th>
                            <th>啟用</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="menu-items">
                        <?php foreach ($menuItems as $index => $item): ?>
                            <tr>
                                <td>
                                    <select name="type[]" required class="menu-type-select">
                                        <option value="blog" <?php echo $item['type'] === 'blog' ? 'selected' : ''; ?>>部落格</option>
                                        <option value="products" <?php echo $item['type'] === 'products' ? 'selected' : ''; ?>>產品</option>
                                        <?php if (!empty($existingPages)): ?>
                                            <optgroup label="靜態頁面">
                                                <?php foreach ($existingPages as $slug => $title): ?>
                                                    <option value="page:<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $item['type'] === 'page:' . $slug ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="label[]" value="<?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="menu_num[]" value="<?php echo htmlspecialchars($item['menu_num'], ENT_QUOTES, 'UTF-8'); ?>" required class="menu-num-input">
                                </td>
                                <td>
                                    <input type="checkbox" name="enabled[]" value="<?php echo $index; ?>" <?php echo $item['enabled'] ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)">刪除</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="button" class="btn" onclick="addMenuItem()">新增選單項目</button>
                <button type="submit" class="btn">儲存選單</button>
            </form>
        </div>
        
        <div class="card">
            <h3>首頁設定</h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_settings">

                <div class="form-group">
                    <label for="home_page">首頁內容</label>
                    <select id="home_page" name="home_page">
                        <option value="">預設首頁（系統內建）</option>
                        <?php foreach ($existingPages as $slug => $title): ?>
                            <option value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($settings['home_page'] ?? '') === $slug ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help">選擇要作為首頁的頁面，選「預設首頁」則使用系統內建首頁。</p>
                </div>

                <button type="submit" class="btn">儲存首頁設定</button>
            </form>
        </div>

        <div class="card">
            <h3>快取管理</h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="clear_cache">
                <button type="submit" class="btn btn-danger">清除所有快取</button>
            </form>
        </div>
        
        <div class="card">
            <h3>郵件測試</h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="test_email">
                
                <div class="form-group">
                    <label for="test_email_to">測試收件信箱 *</label>
                    <input type="email" id="test_email_to" name="test_email_to" required placeholder="test@example.com">
                    <p class="form-help">輸入一個信箱進行測試發送，確認 SMTP 設定是否正常</p>
                </div>
                
                <button type="submit" class="btn">發送測試郵件</button>
            </form>
        </div>
    </div>

    <script>
        function addMenuItem() {
            const tbody = document.getElementById('menu-items');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select name="type[]" required class="menu-type-select">
                        <option value="blog">部落格</option>
                        <option value="products">產品</option>
                        <?php if (!empty($existingPages)): ?>
                            <optgroup label="靜態頁面">
                                <?php foreach ($existingPages as $slug => $title): ?>
                                    <option value="page:<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="label[]" value="" required>
                </td>
                <td>
                    <input type="number" name="menu_num[]" value="" required class="menu-num-input">
                </td>
                <td>
                    <input type="checkbox" name="enabled[]" value="">
                </td>
                <td>
                    <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)">刪除</button>
                </td>
            `;
            tbody.appendChild(newRow);
        }

        function removeMenuItem(button) {
            const row = button.closest('tr');
            row.remove();
        }
    </script>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
