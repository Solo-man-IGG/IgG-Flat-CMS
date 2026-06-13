<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

$pageTitle = $post['title'] ?? __('blog.post.page_title_default');
$siteTitle = $siteTitle ?? 'My Site';
$siteSlogan = $siteSlogan ?? '';
$subtitle = $post['subtitle'] ?? '';
$menuItems = $menuItems ?? [];
require __DIR__ . '/header.php';
?>

<article class="blog-post">
    <?php if (!empty($post['banner'])): ?>
        <div class="blog-banner">
            <img src="<?php echo htmlspecialchars($post['banner'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    <?php endif; ?>

    <h1><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="meta">
        <span><?php echo __('blog.post.date_label'); ?><?php echo htmlspecialchars($post['date'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if (!empty($post['author'])): ?>
            <span><?php echo __('blog.post.author_label'); ?><?php echo htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if (!empty($post['tags'])): ?>
            <span><?php echo __('blog.post.tags_label'); ?>
                <?php foreach ($post['tags'] as $tag): ?>
                    <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                <?php endforeach; ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="content">
        <?php echo $post['content']; ?>
    </div>

    <?php if (!empty($relatedPosts)): ?>
        <div class="related-posts">
            <h3><?php echo __('blog.post.related_title'); ?></h3>
            <ul>
                <?php foreach ($relatedPosts as $rp): ?>
                    <li>
                        <a href="/blog/<?php echo htmlspecialchars($rp['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($rp['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <span class="related-date"><?php echo htmlspecialchars($rp['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php 
    // 读取全局签名
    $signature = '';
    try {
        $signaturePath = 'content/config/signature.txt';
        if (file_exists($signaturePath)) {
            $signature = file_get_contents($signaturePath);
        }
    } catch (\Exception $e) {
        // 忽略错误
    }
    
    if (!empty($signature)): ?>
        <footer class="post-signature" style="margin-top: 2rem; padding: 1rem; border-top: 1px solid #eee; background: #f8fafc; border-radius: 0.5rem; color: #666; font-style: italic; text-align: left;">
            <?php 
            $parser = new \CMS\MarkdownParser();
            $parsedSignature = $parser->parse(nl2br($signature));
            echo $parsedSignature['content']; 
            ?>
        </footer>
    <?php endif; ?>
</article>

<style>
.blog-banner {
    margin-bottom: 2rem;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.1);
}

.blog-banner img {
    width: 100%;
    height: auto;
    display: block;
}

.related-posts {
    margin-top: 3rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--border-color, #e2e8f0);
}

.related-posts h3 {
    margin-bottom: 1rem;
    color: var(--primary-color, #3b82f6);
}

.related-posts ul {
    list-style: none;
    padding: 0;
}

.related-posts li {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.related-posts li:last-child {
    border-bottom: none;
}

.related-posts a {
    font-weight: 500;
    text-decoration: none;
    color: var(--text-color, #1f2937);
}

.related-posts a:hover {
    color: var(--primary-color, #3b82f6);
}

.related-date {
    font-size: 0.85rem;
    color: #6b7280;
    white-space: nowrap;
    margin-left: 1rem;
}

@media (max-width: 768px) {
    .related-posts li {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .related-date {
        margin-left: 0;
    }
}
</style>

<?php require __DIR__ . '/footer.php'; ?>
