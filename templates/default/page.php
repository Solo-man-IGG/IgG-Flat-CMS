<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

$pageTitle = $page['title'] ?? '頁面';
$siteTitle = $siteTitle ?? 'My Site';
$menuItems = $menuItems ?? [];
require __DIR__ . '/header.php';
?>

<article class="page-content">
    <h1><?php echo htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="content">
        <?php echo $page['content']; ?>
    </div>
</article>

<style>
.page-content {
    max-width: 800px;
    margin: 0 auto;
}

.page-content h1 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.page-content .content {
    line-height: 1.8;
}

.page-content .content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}
</style>

<?php require __DIR__ . '/footer.php'; ?>
