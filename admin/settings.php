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

$pageTitle = __('admin.settings.page_title');
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
        'site_slogan' => '',
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
        $error = __('admin.settings.error.csrf');
    } else {
        try {
            switch ($action) {
                case 'save_settings':
                    // Load existing settings
                    $currentSettingsJson = $fileHandler->read('content/config/settings.json');
                    $currentSettings = json_decode($currentSettingsJson, true) ?? [];
                    
                    $newSettings = $currentSettings;
                    $fields = [
                        'site_title', 'site_slogan', 'mail_host', 'mail_port',
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
                    $message = __('admin.settings.message.saved');
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
                    $message = __('admin.settings.message.menu_saved');
                    break;
                    
                case 'clear_cache':
                    $cache->clearAll();
                    $message = __('admin.settings.message.cache_cleared');
                    break;
                    
                case 'test_email':
                    $testTo = $_POST['test_email_to'] ?? '';
                    if (!$testTo || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                        $error = __('admin.settings.error.invalid_test_email');
                    } else {
                        $mailer = new Mailer($fileHandler);
                        $mailer->reloadSettings();
                        $siteTitle = $settings['site_title'] ?? 'IgG Flat CMS - Lightweight Flat-File CMS';
                        $subject = $siteTitle . __('admin.settings.email_test.subject_suffix');
                        $body = __('admin.settings.email_test.body_html', date('Y-m-d H:i:s'));
                        $altBody = __('admin.settings.email_test.body_plain', date('Y-m-d H:i:s'));
                        
                        if ($mailer->send($testTo, __('admin.settings.email_test.recipient_name'), $subject, $body, $altBody)) {
                            $message = __('admin.settings.message.test_email_sent', htmlspecialchars($testTo, ENT_QUOTES, 'UTF-8'));
                        } else {
                            $error = $mailer->getLastError() ? __('admin.settings.error.test_email_failed', $mailer->getLastError()) : __('admin.settings.error.unknown');
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = __('admin.settings.error.operation_failed', $e->getMessage());
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1><?php echo __('admin.settings.heading'); ?></h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo __('admin.settings.site_section.title'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-group">
                    <label for="site_title"><?php echo __('admin.settings.site_section.site_title'); ?></label>
                    <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="site_slogan"><?php echo __('admin.settings.site_section.site_slogan'); ?></label>
                    <input type="text" id="site_slogan" name="site_slogan" value="<?php echo htmlspecialchars($settings['site_slogan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <button type="submit" class="btn"><?php echo __('admin.settings.site_section.save'); ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.settings.mail_section.title'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-group">
                    <label for="mail_host"><?php echo __('admin.settings.mail_section.host'); ?></label>
                    <input type="text" id="mail_host" name="mail_host" value="<?php echo htmlspecialchars($settings['mail_host'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('admin.settings.mail_section.host_placeholder'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mail_port"><?php echo __('admin.settings.mail_section.port'); ?></label>
                    <input type="number" id="mail_port" name="mail_port" value="<?php echo htmlspecialchars($settings['mail_port'] ?? '587', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mail_username"><?php echo __('admin.settings.mail_section.username'); ?></label>
                    <input type="text" id="mail_username" name="mail_username" value="<?php echo htmlspecialchars($settings['mail_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mail_password"><?php echo __('admin.settings.mail_section.password'); ?></label>
                    <input type="password" id="mail_password" name="mail_password" placeholder="<?php echo $settings['mail_password'] ? __('admin.settings.mail_section.password_placeholder') : ''; ?>">
                    <p class="form-help"><?php echo __('admin.settings.mail_section.password_help'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="mail_from"><?php echo __('admin.settings.mail_section.from_email'); ?></label>
                    <input type="email" id="mail_from" name="mail_from" value="<?php echo htmlspecialchars($settings['mail_from'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mail_from_name"><?php echo __('admin.settings.mail_section.from_name'); ?></label>
                    <input type="text" id="mail_from_name" name="mail_from_name" value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <button type="submit" class="btn"><?php echo __('admin.settings.mail_section.save'); ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.settings.menu_section.title'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_menu">
                
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('admin.settings.menu_section.col_type'); ?></th>
                            <th><?php echo __('admin.settings.menu_section.col_label'); ?></th>
                            <th><?php echo __('admin.settings.menu_section.col_order'); ?></th>
                            <th><?php echo __('admin.settings.menu_section.col_enabled'); ?></th>
                            <th><?php echo __('admin.settings.menu_section.col_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="menu-items">
                        <?php foreach ($menuItems as $index => $item): ?>
                            <tr>
                                <td>
                                    <select name="type[]" required class="menu-type-select">
                                        <option value="blog" <?php echo $item['type'] === 'blog' ? 'selected' : ''; ?>><?php echo __('admin.settings.menu_section.type_blog'); ?></option>
                                        <option value="products" <?php echo $item['type'] === 'products' ? 'selected' : ''; ?>><?php echo __('admin.settings.menu_section.type_products'); ?></option>
                                        <?php if (!empty($existingPages)): ?>
                                            <optgroup label="<?php echo __('admin.settings.menu_section.optgroup_static_pages'); ?>">
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
                                    <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)"><?php echo __('admin.settings.menu_section.delete_item'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="button" class="btn" onclick="addMenuItem()"><?php echo __('admin.settings.menu_section.add_item'); ?></button>
                <button type="submit" class="btn"><?php echo __('admin.settings.menu_section.save'); ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.settings.home_section.title'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_settings">

                <div class="form-group">
                    <label for="home_page"><?php echo __('admin.settings.home_section.home_page'); ?></label>
                    <select id="home_page" name="home_page">
                        <option value=""><?php echo __('admin.settings.home_section.default_option'); ?></option>
                        <?php foreach ($existingPages as $slug => $title): ?>
                            <option value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($settings['home_page'] ?? '') === $slug ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help"><?php echo __('admin.settings.home_section.help'); ?></p>
                </div>

                <button type="submit" class="btn"><?php echo __('admin.settings.home_section.save'); ?></button>
            </form>
        </div>

        <div class="card">
            <h3><?php echo __('admin.settings.cache_section.title'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="clear_cache">
                <button type="submit" class="btn btn-danger"><?php echo __('admin.settings.cache_section.clear'); ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.settings.email_test.title'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="test_email">
                
                <div class="form-group">
                    <label for="test_email_to"><?php echo __('admin.settings.email_test.to_label'); ?></label>
                    <input type="email" id="test_email_to" name="test_email_to" required placeholder="<?php echo __('admin.settings.email_test.to_placeholder'); ?>">
                    <p class="form-help"><?php echo __('admin.settings.email_test.help'); ?></p>
                </div>
                
                <button type="submit" class="btn"><?php echo __('admin.settings.email_test.send'); ?></button>
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
                        <option value="blog"><?php echo __('admin.settings.menu_section.type_blog'); ?></option>
                        <option value="products"><?php echo __('admin.settings.menu_section.type_products'); ?></option>
                        <?php if (!empty($existingPages)): ?>
                            <optgroup label="<?php echo __('admin.settings.menu_section.optgroup_static_pages'); ?>">
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
                    <button type="button" class="btn btn-danger" onclick="removeMenuItem(this)"><?php echo __('admin.settings.menu_section.delete_item'); ?></button>
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
