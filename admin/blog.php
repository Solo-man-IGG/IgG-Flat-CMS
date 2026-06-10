<?php

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
use CMS\MarkdownParser;
use CMS\Cache;
use CMS\Counter;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize components
$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);
$cache = new Cache($fileHandler);
$counter = new Counter($fileHandler);
$parser = new MarkdownParser();

// Require authentication
$auth->requireAuth();

$pageTitle = '文章管理';
$currentPage = 'blog';
$username = $auth->getUsername();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = 'CSRF 驗證失敗，請重新整理頁面後再試。';
    } else {
        try {
            switch ($action) {
                case 'delete':
                    $slug = $_POST['slug'] ?? '';
                    if ($slug) {
                        $files = $fileHandler->listFiles('content/blog', 'md');
                        foreach ($files as $file) {
                            $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                            if ($fileSlug === $slug) {
                                $fileHandler->delete('content/blog/' . $file);
                                $cache->clear('blog', $slug);
                                $cache->clear('blog', 'blog_list');
                                try {
                                    $counterFile = 'content/counters/blog-' . $slug . '.json';
                                    if ($fileHandler->exists($counterFile)) {
                                        $fileHandler->delete($counterFile);
                                    }
                                } catch (\Exception $e) {
                                    error_log('Failed to delete counter: ' . $e->getMessage());
                                }
                                $message = '文章已刪除。';
                                break;
                            }
                        }
                    }
                    break;
                    
                case 'save':
                    $slug = $_POST['slug'] ?? '';
                    $title = $_POST['title'] ?? '';
                    $content = $_POST['content'] ?? '';
                    $author = $_POST['author'] ?? '';
                    $tags = $_POST['tags'] ?? '';
                    $banner = $_POST['banner'] ?? '';
                    $published = isset($_POST['published']) ? 'true' : 'false';
                    
                    if (!$title || !$content) {
                        $error = '標題和內容不能為空。';
                    } else {
                        // Generate slug if not provided
                        if (!$slug) {
                            if ($title) {
                                // Check if title contains non-ASCII characters (like Chinese)
                                if (preg_match('/[^\x00-\x7F]/', $title)) {
                                    $slug = 'post-' . date('YmdHis');
                                } else {
                                    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
                                    $slug = trim($slug, '-');
                                }
                            } else {
                                // If title is also empty, generate a timestamp-based slug
                                $slug = 'post-' . time();
                            }
                        }
                        
                        // Ensure slug is not empty
                        if (empty($slug)) {
                            $slug = 'post-' . time();
                        }
                        
                        // Parse tags
                        $tagsArray = array_filter(array_map('trim', explode(',', $tags)));
                        
                        // Create markdown with frontmatter
                        $frontmatterData = [
                            'title' => $title,
                            'slug' => $slug,
                            'date' => date('Y-m-d'),
                            'author' => $author,
                            'published' => $published === 'true',
                        ];
                        if ($banner) {
                            $frontmatterData['banner'] = $banner;
                        }
                        if (!empty($tagsArray)) {
                            $frontmatterData['tags'] = array_values($tagsArray);
                        }
                        $frontmatter = "---\n" . \Symfony\Component\Yaml\Yaml::dump($frontmatterData, 2, 2) . "---\n\n" . $content;
                        
                        $fileHandler->write('content/blog/' . $slug . '.md', $frontmatter);
                        $cache->clear('blog', $slug);
                        $cache->clear('blog', 'blog_list');
                        $message = '文章已儲存。';
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = '操作失敗：' . $e->getMessage();
        }
    }
}

// Get all blog posts
$posts = [];
$editPost = null;
try {
    $files = $fileHandler->listFiles('content/blog', 'md');
    foreach ($files as $file) {
        $path = 'content/blog/' . $file;
        $rawContent = $fileHandler->read($path);
        $parsed = $parser->parse($rawContent);
        $frontmatter = $parsed['frontmatter'];
        $slug = $parser->getSlug($frontmatter, $file);
        
        $tags = isset($frontmatter['tags']) && is_array($frontmatter['tags'])
            ? implode(', ', $frontmatter['tags'])
            : '';
        
        // Extract raw markdown (strip frontmatter) for editing
        $rawMarkdown = $rawContent;
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $rawContent, $m)) {
            $rawMarkdown = $m[2];
        }

        $posts[] = [
            'slug' => $slug,
            'title' => $parser->getTitle($frontmatter, $parsed['content']),
            'date' => $parser->getDate($frontmatter, $fileHandler->getModificationTime($path)),
            'author' => $frontmatter['author'] ?? '',
            'published' => $frontmatter['published'] ?? true,
            'tags' => $tags,
            'banner' => $frontmatter['banner'] ?? '',
            'rawContent' => $rawMarkdown,
            'views' => $counter->get('blog', $slug),
            'file' => $file
        ];
    }
} catch (\Exception $e) {
    $error = '載入文章失敗：' . $e->getMessage();
}

// Sort posts by date (newest first)
usort($posts, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Handle edit mode
$editSlug = $_GET['edit'] ?? '';
if ($editSlug) {
    foreach ($posts as $p) {
        if ($p['slug'] === $editSlug) {
            $editPost = $p;
            break;
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1>文章管理</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo $editPost ? '編輯文章' : '新增文章'; ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editPost): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editPost['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">標題 *</label>
                    <input type="text" id="title" name="title" value="<?php echo $editPost ? htmlspecialchars($editPost['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="author">作者</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($editPost['author'] ?? $username, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="tags">標籤 (用逗號分隔)</label>
                    <input type="text" id="tags" name="tags" value="<?php echo $editPost ? htmlspecialchars($editPost['tags'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="例如：技術, 教程">
                </div>
                
                <div class="form-group">
                    <label for="banner">Banner 圖片 URL</label>
                    <input type="url" id="banner" name="banner" value="<?php echo $editPost ? htmlspecialchars($editPost['banner'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="https://example.com/banner.jpg">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="published" value="true" <?php echo (!$editPost || $editPost['published']) ? 'checked' : ''; ?>>
                        發布文章
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="content">內容 (Markdown) *</label>
                    <textarea id="content" name="content" data-easymde rows="20" required><?php echo $editPost ? htmlspecialchars($editPost['rawContent'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>
                
                <?php if ($editPost): ?>
                    <a href="/admin/blog" class="btn btn-danger" style="text-decoration: none;">取消編輯</a>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editPost ? '更新文章' : '儲存文章'; ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3>現有文章</h3>
            <?php if (!empty($posts)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>標題</th>
                            <th>作者</th>
                            <th>日期</th>
                            <th>狀態</th>
                            <th>瀏覽人次</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($post['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ($post['published']): ?>
                                        <span style="color: #10b981;">已發布</span>
                                    <?php else: ?>
                                        <span style="color: #6b7280;">草稿</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($post['views']); ?></td>
                                <td>
                                    <a href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn" target="_blank">檢視</a>
                                    <a href="/admin/blog?edit=<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">編輯</a>
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此文章？');">刪除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>目前沒有文章。</p>
            <?php endif; ?>
        </div>
    </div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
