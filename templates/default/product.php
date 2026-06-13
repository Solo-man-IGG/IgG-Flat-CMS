<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

$pageTitle = $product['title'] ?? __('product.page_title_default');
$siteTitle = $siteTitle ?? 'My Site';
$siteSlogan = $siteSlogan ?? '';
$subtitle = $product['subtitle'] ?? '';
$menuItems = $menuItems ?? [];
require __DIR__ . '/header.php';
?>

<article class="product-post">
    <?php if (!empty($product['image'])): ?>
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    <?php endif; ?>
    
    <h1><?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    
    <?php if (!empty($product['price'])): ?>
        <div class="product-price"><?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    
    <div class="content">
        <?php echo $product['content']; ?>
    </div>
    
    <?php if (!empty($product['sku']) || !empty($product['stock'])): ?>
        <div class="product-meta">
            <?php if (!empty($product['sku'])): ?>
                <span><?php echo __('product.sku_label'); ?><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if (!empty($product['stock'])): ?>
                <span><?php echo __('product.stock_label'); ?><?php echo htmlspecialchars($product['stock'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</article>

<style>
.product-post {
    max-width: 800px;
    margin: 0 auto;
}

.product-post h1 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.product-post .product-image {
    margin-bottom: 2rem;
    text-align: center;
}

.product-post .product-image img {
    max-width: 100%;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-post .product-price {
    font-size: 2rem;
    font-weight: bold;
    color: var(--secondary-color);
    margin: 1rem 0;
}

.product-post .content {
    line-height: 1.8;
}

.product-post .product-meta {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    color: #6b7280;
    display: flex;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .product-post .product-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<?php require __DIR__ . '/footer.php'; ?>
