<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

$pageTitle = '部落格';
$siteTitle = $siteTitle ?? 'My Site';
$menuItems = $menuItems ?? [];
require __DIR__ . '/header.php';
?>

<div class="blog-filter">
    <form method="GET" action="/blog">
        <label for="tag-filter">篩選分類：</label>
        <select name="tag" id="tag-filter" onchange="this.form.submit()">
            <option value="">全部文章</option>
            <?php foreach ($allTags as $tag): ?>
                <option value="<?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($selectedTag ?? '') === $tag ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">送出</button></noscript>
    </form>
</div>

<div class="blog-list" id="blog-list">
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
            <article class="blog-item">
                <h2>
                    <a href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </h2>
                <div class="meta">
                    <span>日期：<?php echo htmlspecialchars($post['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if (!empty($post['author'])): ?>
                        <span> | 作者：<?php echo htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="excerpt">
                    <?php echo htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="tags">
                    <?php foreach ($post['tags'] ?? [] as $tag): ?>
                        <span class="tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>
                <a href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="read-more">
                    閱讀更多 →
                </a>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p>目前沒有文章。</p>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="?<?php echo $selectedTag !== '' ? 'tag=' . urlencode($selectedTag) . '&' : ''; ?>page=<?php echo $currentPage - 1; ?>" class="page-link">&laquo; 上一頁</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $currentPage): ?>
            <span class="page-link current"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?<?php echo $selectedTag !== '' ? 'tag=' . urlencode($selectedTag) . '&' : ''; ?>page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($currentPage < $totalPages): ?>
        <a href="?<?php echo $selectedTag !== '' ? 'tag=' . urlencode($selectedTag) . '&' : ''; ?>page=<?php echo $currentPage + 1; ?>" class="page-link">下一頁 &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
