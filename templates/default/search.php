<?php

$pageTitle = __('search.page_title');
$siteTitle = $siteTitle ?? 'My Site';
$siteSlogan = $siteSlogan ?? '';
$subtitle = $subtitle ?? '';
$menuItems = $menuItems ?? [];
require __DIR__ . '/header.php';
?>

<div class="search-page">
    <h1><?php echo __('search.heading'); ?></h1>

    <form method="GET" action="/search" class="search-form">
        <div class="search-input-group">
            <input type="text" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('search.placeholder'); ?>" autofocus>
            <button type="submit"><?php echo __('search.submit'); ?></button>
        </div>
    </form>

    <?php if ($query): ?>
        <?php if (empty($results)): ?>
            <div class="search-info">
                <p><?php echo __('search.no_results', htmlspecialchars($query, ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        <?php else: ?>
            <div class="search-info">
                <p><?php echo __('search.results_count', count($results), htmlspecialchars($query, ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
            <div class="search-results">
                <?php foreach ($results as $item): ?>
                    <article class="search-item">
                        <h2>
                            <?php
                            $urlMap = ['blog' => '/blog/', 'product' => '/products/', 'page' => '/pages/'];
                            $itemUrl = ($urlMap[$item['type']] ?? '/' . $item['type'] . '/') . htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8');
                            ?>
                            <a href="<?php echo $itemUrl; ?>">
                                <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h2>
                        <?php if (!empty($item['subtitle'])): ?>
                            <div class="search-item-subtitle">
                                <?php echo htmlspecialchars($item['subtitle'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="search-item-meta">
                            <span class="search-item-type"><?php echo __($item['type'] === 'blog' ? 'search.type_blog' : ($item['type'] === 'product' ? 'search.type_product' : 'search.type_page')); ?></span>
                            <span class="search-item-date"><?php echo htmlspecialchars($item['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="search-item-excerpt">
                            <?php echo htmlspecialchars($item['excerpt'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.search-page {
    max-width: 800px;
    margin: 0 auto;
}

.search-page h1 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.search-form {
    margin-bottom: 2rem;
}

.search-input-group {
    display: flex;
    gap: 0.5rem;
}

.search-input-group input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1.1rem;
    outline: none;
    transition: border-color 0.2s;
}

.search-input-group input:focus {
    border-color: var(--primary-color);
}

.search-input-group button {
    padding: 0.75rem 1.5rem;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-input-group button:hover {
    background-color: var(--secondary-color);
}

.search-info {
    margin-bottom: 1.5rem;
    color: #6b7280;
}

.search-results {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.search-item {
    padding: 1.25rem;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    transition: box-shadow 0.2s;
}

.search-item:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.search-item h2 {
    margin-bottom: 0.25rem;
    font-size: 1.25rem;
}

.search-item h2 a {
    text-decoration: none;
    color: var(--text-color);
}

.search-item h2 a:hover {
    color: var(--primary-color);
}

.search-item-subtitle {
    font-size: 0.95rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.search-item-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #9ca3af;
    margin-bottom: 0.75rem;
}

.search-item-type {
    display: inline-block;
    padding: 0.1rem 0.5rem;
    background: #f1f5f9;
    border-radius: 0.25rem;
    font-size: 0.8rem;
}

.search-item-excerpt {
    line-height: 1.6;
    color: #4b5563;
}

@media (max-width: 768px) {
    .search-input-group {
        flex-direction: column;
    }

    .search-input-group button {
        width: 100%;
    }
}
</style>

<?php require __DIR__ . '/footer.php'; ?>
