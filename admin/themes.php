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

$pageTitle = '主題管理';
$currentPage = 'themes';
$username = $auth->getUsername();

$message = '';
$error = '';

$presets = [
    'default' => [
        'label' => '預設',
        'colors' => [
            'primary-color' => '#3b82f6',
            'secondary-color' => '#1d4ed8',
            'accent-color' => '#10b981',
            'text-color' => '#1f2937',
            'bg-color' => '#f8fafc',
            'border-color' => '#e2e8f0',
            'header-bg' => '#0f172a',
            'header-text' => '#ffffff',
            'footer-bg' => '#0f172a',
            'footer-text' => '#e2e8f0',
        ],
    ],
    'dark' => [
        'label' => '暗色',
        'colors' => [
            'primary-color' => '#6366f1',
            'secondary-color' => '#818cf8',
            'accent-color' => '#22d3ee',
            'text-color' => '#e2e8f0',
            'bg-color' => '#0f172a',
            'border-color' => '#334155',
            'header-bg' => '#020617',
            'header-text' => '#f1f5f9',
            'footer-bg' => '#020617',
            'footer-text' => '#94a3b8',
        ],
    ],
    'nature' => [
        'label' => '自然',
        'colors' => [
            'primary-color' => '#059669',
            'secondary-color' => '#047857',
            'accent-color' => '#d97706',
            'text-color' => '#1c1917',
            'bg-color' => '#fefce8',
            'border-color' => '#d9d99e',
            'header-bg' => '#065f46',
            'header-text' => '#fefce8',
            'footer-bg' => '#065f46',
            'footer-text' => '#a7f3d0',
        ],
    ],
    'ocean' => [
        'label' => '海洋',
        'colors' => [
            'primary-color' => '#0891b2',
            'secondary-color' => '#0e7490',
            'accent-color' => '#f59e0b',
            'text-color' => '#164e63',
            'bg-color' => '#ecfeff',
            'border-color' => '#a5f3fc',
            'header-bg' => '#083344',
            'header-text' => '#ecfeff',
            'footer-bg' => '#083344',
            'footer-text' => '#67e8f9',
        ],
    ],
    'sunset' => [
        'label' => '夕陽',
        'colors' => [
            'primary-color' => '#ea580c',
            'secondary-color' => '#c2410c',
            'accent-color' => '#d946ef',
            'text-color' => '#292524',
            'bg-color' => '#fff7ed',
            'border-color' => '#fed7aa',
            'header-bg' => '#7c2d12',
            'header-text' => '#fff7ed',
            'footer-bg' => '#7c2d12',
            'footer-text' => '#fdba74',
        ],
    ],
];

$colorLabels = [
    'primary-color' => '主要顏色',
    'secondary-color' => '次要顏色',
    'accent-color' => '強調色',
    'text-color' => '文字顏色',
    'bg-color' => '背景顏色',
    'border-color' => '邊框顏色',
    'header-bg' => '頁首背景',
    'header-text' => '頁首文字',
    'footer-bg' => '頁尾背景',
    'footer-text' => '頁尾文字',
];

// Load existing theme config
$themeConfig = [];
try {
    $themeJson = $fileHandler->read('content/config/theme.json');
    $themeConfig = json_decode($themeJson, true) ?? [];
} catch (\Exception $e) {
    $themeConfig = [];
}

$currentColors = array_merge($presets['default']['colors'], $themeConfig['colors'] ?? []);
$currentScheme = $themeConfig['scheme'] ?? 'default';
$currentNavStyle = $themeConfig['nav_style'] ?? 'list';
$customCss = $themeConfig['custom_css'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = 'CSRF 驗證失敗，請重新整理頁面後再試。';
    } else {
        try {
            switch ($action) {
                case 'save_theme':
                    $newColors = [];
                    foreach ($colorLabels as $key => $label) {
                        $newColors[$key] = $_POST[$key] ?? $presets['default']['colors'][$key];
                    }

                    $themeConfig = [
                        'scheme' => $_POST['scheme'] ?? 'custom',
                        'colors' => $newColors,
                        'nav_style' => $_POST['nav_style'] ?? 'list',
                        'custom_css' => $_POST['custom_css'] ?? '',
                    ];

                    $fileHandler->write('content/config/theme.json', json_encode($themeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    // Also save custom.css for backward compatibility
                    $fileHandler->write('templates/default/custom.css', $_POST['custom_css'] ?? '');

                    $currentColors = $newColors;
                    $currentScheme = $themeConfig['scheme'];
                    $currentNavStyle = $themeConfig['nav_style'];
                    $customCss = $themeConfig['custom_css'];
                    $message = '主題設定已儲存。';
                    break;

                case 'reset_theme':
                    $themeConfig = [
                        'scheme' => 'default',
                        'colors' => $presets['default']['colors'],
                        'nav_style' => 'list',
                        'custom_css' => '',
                    ];

                    $fileHandler->write('content/config/theme.json', json_encode($themeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $fileHandler->write('templates/default/custom.css', '');

                    $currentColors = $presets['default']['colors'];
                    $currentScheme = 'default';
                    $customCss = '';
                    $message = '主題已重置為預設。';
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
        <h1>主題管理</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" id="theme-form">
            <?php echo $csrfField; ?>
            <input type="hidden" name="action" value="save_theme">
            <input type="hidden" name="scheme" id="scheme-input" value="<?php echo htmlspecialchars($currentScheme, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="card">
                <h3>配色方案</h3>
                <p>選擇一個預設方案，或自行調整下方的顏色。</p>
                <div class="preset-grid">
                    <?php foreach ($presets as $key => $preset): ?>
                        <button type="button" class="preset-btn <?php echo $currentScheme === $key ? 'active' : ''; ?>" data-scheme="<?php echo $key; ?>" onclick="applyPreset('<?php echo $key; ?>')">
                            <div class="preset-preview">
                                <?php foreach (['primary-color', 'bg-color', 'text-color', 'header-bg'] as $ck): ?>
                                    <span style="background: <?php echo $preset['colors'][$ck]; ?>; width: 20px; height: 20px; display: inline-block; border-radius: 4px;"></span>
                                <?php endforeach; ?>
                            </div>
                            <span><?php echo htmlspecialchars($preset['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h3>自訂顏色</h3>
                <div class="color-grid">
                    <?php foreach ($colorLabels as $key => $label): ?>
                        <div class="color-item">
                            <label for="<?php echo $key; ?>"><?php echo $label; ?></label>
                            <div class="color-input-wrap">
                                <input type="color" id="<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($currentColors[$key] ?? '#000000', ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="text" class="color-hex" value="<?php echo htmlspecialchars($currentColors[$key] ?? '#000000', ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('<?php echo $key; ?>').value=this.value;document.getElementById('<?php echo $key; ?>').dispatchEvent(new Event('input'))" onfocus="this.select()">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h3>導覽樣式</h3>
                <p>選擇選單的顯示方式。</p>
                <div class="nav-style-grid">
                    <label class="nav-style-option <?php echo $currentNavStyle === 'list' ? 'active' : ''; ?>">
                        <input type="radio" name="nav_style" value="list" <?php echo $currentNavStyle === 'list' ? 'checked' : ''; ?> onchange="document.querySelectorAll('.nav-style-option').forEach(function(e){e.classList.remove('active')});this.closest('.nav-style-option').classList.add('active')">
                        <div class="nav-style-preview">
                            <div class="mock-nav">
                                <span class="mock-brand">Logo</span>
                                <span class="mock-links"><span>選項1</span><span>選項2</span><span>選項3</span></span>
                            </div>
                        </div>
                        <span>水平列表</span>
                    </label>
                    <label class="nav-style-option <?php echo $currentNavStyle === 'hamburger' ? 'active' : ''; ?>">
                        <input type="radio" name="nav_style" value="hamburger" <?php echo $currentNavStyle === 'hamburger' ? 'checked' : ''; ?> onchange="document.querySelectorAll('.nav-style-option').forEach(function(e){e.classList.remove('active')});this.closest('.nav-style-option').classList.add('active')">
                        <div class="nav-style-preview">
                            <div class="mock-nav">
                                <span class="mock-brand">Logo</span>
                                <span class="mock-hamburger"><span></span><span></span><span></span></span>
                            </div>
                        </div>
                        <span>漢堡選單</span>
                    </label>
                </div>
            </div>

            <div class="card">
                <h3>自訂 CSS</h3>
                <p>進階：直接撰寫 CSS 覆蓋樣式，適合熟悉 CSS 的使用者。</p>
                <div class="form-group">
                    <textarea id="custom_css" name="custom_css" rows="20" style="font-family: monospace; font-size: 14px; width: 100%;"><?php echo htmlspecialchars($customCss, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">儲存主題設定</button>
                <button type="button" class="btn btn-danger" onclick="if(confirm('確定要重置所有主題設定？')){document.getElementById('scheme-input').name='';var f=document.getElementById('theme-form');var i=document.createElement('input');i.type='hidden';i.name='action';i.value='reset_theme';f.appendChild(i);f.submit()}">重置為預設</button>
            </div>
        </form>
    </div>

    <script>
        const presets = <?php echo json_encode($presets, JSON_UNESCAPED_UNICODE); ?>;

        function applyPreset(key) {
            const preset = presets[key];
            if (!preset) return;

            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`.preset-btn[data-scheme="${key}"]`).classList.add('active');
            document.getElementById('scheme-input').value = key;

            Object.keys(preset.colors).forEach(cKey => {
                const input = document.getElementById(cKey);
                if (input) {
                    input.value = preset.colors[cKey];
                    const hexInput = input.parentElement.querySelector('.color-hex');
                    if (hexInput) hexInput.value = preset.colors[cKey];
                }
            });
        }

        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('input', function() {
                const hexInput = this.parentElement.querySelector('.color-hex');
                if (hexInput) hexInput.value = this.value;
                document.getElementById('scheme-input').value = 'custom';
                document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            });
        });
    </script>

    <style>
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .preset-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            border: 2px solid var(--border-color, #e2e8f0);
            border-radius: 0.5rem;
            background: var(--bg-color, #f8fafc);
            cursor: pointer;
            transition: all 0.2s;
        }

        .preset-btn:hover {
            border-color: var(--primary-color, #3b82f6);
            transform: translateY(-2px);
        }

        .preset-btn.active {
            border-color: var(--primary-color, #3b82f6);
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .preset-preview {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .color-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .color-item label {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .color-input-wrap {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .color-input-wrap input[type="color"] {
            width: 48px;
            height: 36px;
            padding: 2px;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 0.4rem;
            cursor: pointer;
        }

        .color-hex {
            flex: 1;
            font-family: monospace;
            font-size: 0.9rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .nav-style-grid {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
        }

        .nav-style-option {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            border: 2px solid var(--border-color, #e2e8f0);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            max-width: 200px;
        }

        .nav-style-option:hover {
            border-color: var(--primary-color, #3b82f6);
        }

        .nav-style-option.active {
            border-color: var(--primary-color, #3b82f6);
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .nav-style-option input {
            display: none;
        }

        .nav-style-preview {
            width: 100%;
            background: #fff;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 0.4rem;
            padding: 0.75rem;
        }

        .mock-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mock-brand {
            font-weight: bold;
            font-size: 0.85rem;
            color: #1f2937;
        }

        .mock-links {
            display: flex;
            gap: 0.5rem;
            font-size: 0.7rem;
        }

        .mock-links span {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 3px;
            color: #4b5563;
        }

        .mock-hamburger {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 4px;
        }

        .mock-hamburger span {
            display: block;
            width: 18px;
            height: 2px;
            background: #4b5563;
            border-radius: 1px;
        }
    </style>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
