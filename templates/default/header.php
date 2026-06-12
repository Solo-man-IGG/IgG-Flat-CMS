<!DOCTYPE html>
<html lang="<?php echo __('lang.attr'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'IgG CMS', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/templates/default/style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
    <?php if (file_exists(__DIR__ . '/../default/custom.css')): ?>
        <link rel="stylesheet" href="/templates/default/custom.css">
    <?php endif; ?>
    <?php
    $navStyle = 'list';
    try {
        $themeHandler = new \CMS\FileHandler(__DIR__ . '/../..');
        if ($themeHandler->exists('content/config/theme.json')) {
            $themeJson = $themeHandler->read('content/config/theme.json');
            $theme = json_decode($themeJson, true);
            if ($theme && !empty($theme['colors'])) {
                echo '<style>:root{' . "\n";
                foreach ($theme['colors'] as $key => $value) {
                    $safeKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                    $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    echo "--{$safeKey}: {$safeValue};\n";
                }
                echo '}</style>' . "\n";
            }
            if (!empty($theme['nav_style'])) {
                $navStyle = $theme['nav_style'];
            }
        }
    } catch (\Exception $e) {
        // Use defaults if theme file can't be loaded
    } ?>
    <?php if ($navStyle === 'hamburger'): ?>
    <style>
        .hamburger-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            margin-left: auto;
            font-size: 1.5rem;
            line-height: 1;
            color: var(--header-text, #ffffff);
            padding: 0 4px;
            height: 100%;
            align-items: center;
        }
        @media (max-width: 768px) {
            .hamburger-btn {
                display: flex;
            }
            .nav-menu {
                display: none;
                flex-direction: column;
                width: 100%;
                background: var(--header-bg, #0f172a);
                position: absolute;
                top: 100%;
                left: 0;
                padding: 1rem;
                z-index: 100;
            }
            .nav-menu.open {
                display: flex;
            }
            nav {
                position: relative;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
            }
            .nav-brand {
                flex: 1;
            }
        }
    </style>
    <?php endif; ?>
</head>
<body>
    <header>
        <nav>
            <div class="nav-brand">
                <a href="/"><?php echo htmlspecialchars($siteTitle ?? 'My Site', ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
            <?php if ($navStyle === 'hamburger'): ?>
            <button class="hamburger-btn" onclick="var btn=this;var menu=document.querySelector('.nav-menu');menu.classList.toggle('open');btn.classList.toggle('active');btn.textContent=btn.classList.contains('active')?'×':'☰';btn.setAttribute('aria-expanded',menu.classList.contains('open'))" aria-label="<?php echo __('header.nav.aria_label'); ?>" aria-expanded="false">☰</button>
            <?php endif; ?>
            <ul class="nav-menu">
                <?php if (isset($menuItems) && is_array($menuItems)): ?>
                    <?php foreach ($menuItems as $item): ?>
                        <?php if ($item['enabled'] ?? false): ?>
                            <li>
                                <?php 
                                $type = $item['type'] ?? '';
                                $href = '/' . $type;
                                // Handle page:slug format
                                if (strpos($type, 'page:') === 0) {
                                    $slug = substr($type, 5);
                                    $href = '/pages/' . $slug;
                                }
                                ?>
                                <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/contact"><?php echo __('header.nav.contact'); ?></a></li>
            </ul>
        </nav>
    </header>
    <main>
