<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

$pageTitle = __('products.list.page_title');
$siteTitle = $siteTitle ?? 'My Site';
$menuItems = $menuItems ?? [];
require __DIR__ . '/header.php';
?>

<div class="products-list">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
            <div class="product-item">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?>" style="max-width: 100%; border-radius: 0.5rem;">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="price">
                    <?php if (!empty($product['price'])): ?>
                        <?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                <div class="description">
                    <?php echo htmlspecialchars($product['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <a href="/products/<?php echo htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="read-more">
                    <?php echo __('products.list.view_details'); ?>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center"><?php echo __('products.list.empty'); ?></p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
