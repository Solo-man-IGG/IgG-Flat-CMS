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
use CMS\Lang;
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

$pageTitle = __('admin.language.page_title');
$currentPage = 'language';
$username = $auth->getUsername();

$message = '';
$error = '';

$availableLangs = Lang::getAvailableLanguages();
$activeLang = Lang::getActiveLanguage();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = __('admin.settings.error.csrf');
    } else {
        try {
            switch ($action) {
                case 'save_all':
                    $overrides = [];
                    $keys = $_POST['key'] ?? [];
                    $values = $_POST['value'] ?? [];
                    foreach ($keys as $i => $key) {
                        $key = trim($key);
                        if ($key !== '' && isset($values[$i])) {
                            $overrides[$key] = $values[$i];
                        }
                    }
                    Lang::saveCustom($overrides);
                    $message = __('admin.language.message.saved');
                    break;

                case 'add_key':
                    $newKey = trim($_POST['new_key'] ?? '');
                    $newValue = trim($_POST['new_value'] ?? '');
                    if ($newKey === '') {
                        $error = __('admin.language.key_placeholder');
                    } else {
                        $custom = Lang::allCustom();
                        $custom[$newKey] = $newValue;
                        Lang::saveCustom($custom);
                        $message = __('admin.language.message.key_added');
                    }
                    break;

                case 'delete_key':
                    $delKey = trim($_POST['delete_key'] ?? '');
                    $custom = Lang::allCustom();
                    if (isset($custom[$delKey])) {
                        unset($custom[$delKey]);
                        Lang::saveCustom($custom);
                        $message = __('admin.language.message.key_deleted');
                    }
                    break;

                case 'switch_language':
                    $newLang = trim($_POST['switch_lang'] ?? '');
                    if ($newLang !== '' && isset($availableLangs[$newLang])) {
                        Lang::switchLanguage($newLang);
                        header('Location: /admin/language');
                        exit;
                    }
                    $error = __('admin.settings.error.operation_failed', '');
                    break;
            }
        } catch (\Exception $e) {
            $error = __('admin.settings.error.operation_failed', $e->getMessage());
        }
    }
}

$base = Lang::allBase();
$custom = Lang::allCustom();
$csrfField = $auth->getCsrfField();

// Merge all keys: base first, then custom on top
$allKeys = array_unique(array_merge(array_keys($base), array_keys($custom)));
sort($allKeys);

$tab = $_GET['tab'] ?? 'all';

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1><?php echo __('admin.language.heading'); ?></h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="card card-lang-switch">
            <div class="lang-switch-form">
                <form method="POST" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <?php echo $csrfField; ?>
                    <input type="hidden" name="action" value="switch_language">
                    <label for="switch_lang" style="font-weight: 600; white-space: nowrap;"><?php echo __('admin.language.switch_label'); ?>：</label>
                    <select name="switch_lang" id="switch_lang" style="flex: 0 1 auto; min-width: 160px;">
                        <?php foreach ($availableLangs as $code => $name): ?>
                            <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $code === $activeLang ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn"><?php echo __('admin.language.switch_button'); ?></button>
                    <span style="color: #6b7280; font-size: 0.85rem;"><?php echo __('admin.language.current_lang'); ?> <strong><?php echo htmlspecialchars($availableLangs[$activeLang] ?? $activeLang, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="tab-bar">
                <a href="?tab=all" class="tab-btn <?php echo $tab === 'all' ? 'active' : ''; ?>"><?php echo __('admin.language.all_tab'); ?> (<?php echo count($allKeys); ?>)</a>
                <a href="?tab=overrides" class="tab-btn <?php echo $tab === 'overrides' ? 'active' : ''; ?>"><?php echo __('admin.language.overrides_tab'); ?> (<?php echo count($custom); ?>)</a>
            </div>

            <div style="margin: 1rem 0;">
                <form method="POST" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <?php echo $csrfField; ?>
                    <input type="hidden" name="action" value="add_key">
                    <input type="text" name="new_key" placeholder="<?php echo __('admin.language.key_placeholder'); ?>" required style="flex: 1; min-width: 200px;">
                    <input type="text" name="new_value" placeholder="<?php echo __('admin.language.value_placeholder'); ?>" required style="flex: 2; min-width: 200px;">
                    <button type="submit" class="btn"><?php echo __('admin.language.add_key'); ?></button>
                </form>
            </div>

            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save_all">

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo __('admin.language.key'); ?></th>
                                <th><?php echo __('admin.language.default_value'); ?></th>
                                <th><?php echo __('admin.language.custom_value'); ?></th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allKeys as $key):
                                $isCustom = isset($custom[$key]);
                                if ($tab === 'overrides' && !$isCustom) continue;
                            ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td>
                                        <span style="color: #6b7280; word-break: break-word;">
                                            <?php echo htmlspecialchars($base[$key] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <input type="hidden" name="key[]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="value[]" value="<?php echo htmlspecialchars($custom[$key] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            placeholder="<?php echo htmlspecialchars($base[$key] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            style="width: 100%; <?php echo $isCustom ? 'border-color: #3b82f6;' : ''; ?>">
                                    </td>
                                    <td>
                                        <?php if ($isCustom): ?>
                                            <form method="POST" onsubmit="return confirm('<?php echo __('admin.language.confirm_delete'); ?>')">
                                                <?php echo $csrfField; ?>
                                                <input type="hidden" name="action" value="delete_key">
                                                <input type="hidden" name="delete_key" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><?php echo __('admin.language.reset'); ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($tab === 'overrides' && empty($custom)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6b7280;"><?php echo __('admin.language.no_overrides'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($tab === 'overrides' || !empty($custom)): ?>
                <div class="form-actions" style="margin-top: 1rem;">
                    <button type="submit" class="btn"><?php echo __('admin.language.save'); ?></button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <style>
        .card-lang-switch {
            background: #f8fafc;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        .lang-switch-form select {
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }
        .tab-bar {
            display: flex;
            gap: 0;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color, #e2e8f0);
        }
        .tab-btn {
            padding: 0.5rem 1.25rem;
            text-decoration: none;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: var(--primary-color, #3b82f6);
        }
        .tab-btn.active {
            color: var(--primary-color, #3b82f6);
            border-bottom-color: var(--primary-color, #3b82f6);
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table-responsive table td code {
            font-size: 0.8rem;
            word-break: break-all;
        }
        .table-responsive table th:nth-child(1),
        .table-responsive table td:nth-child(1) {
            width: 25%;
            min-width: 180px;
        }
        .table-responsive table th:nth-child(2),
        .table-responsive table td:nth-child(2) {
            width: 30%;
            min-width: 200px;
        }
        .table-responsive table th:nth-child(3),
        .table-responsive table td:nth-child(3) {
            width: 40%;
            min-width: 250px;
        }
        .table-responsive table th:nth-child(4),
        .table-responsive table td:nth-child(4) {
            width: 5%;
        }
    </style>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
