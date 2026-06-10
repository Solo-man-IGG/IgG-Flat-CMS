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

$pageTitle = '頁面管理';
$currentPage = 'pages';
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
                        $files = $fileHandler->listFiles('content/pages', 'md');
                        foreach ($files as $file) {
                            $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                            if ($fileSlug === $slug) {
                                $fileHandler->delete('content/pages/' . $file);
                                $cache->clear('pages', $slug);
                                try {
                                    $counterFile = 'content/counters/page-' . $slug . '.json';
                                    if ($fileHandler->exists($counterFile)) {
                                        $fileHandler->delete($counterFile);
                                    }
                                } catch (\Exception $e) {
                                    error_log('Failed to delete counter: ' . $e->getMessage());
                                }
                                $message = '頁面已刪除。';
                                break;
                            }
                        }
                    }
                    break;
                    
                case 'save':
                    $slug = $_POST['slug'] ?? '';
                    $title = $_POST['title'] ?? '';
                    $content = $_POST['content'] ?? '';
                    
                    if (!$title || !$content) {
                        $error = '標題和內容不能為空。';
                    } else {
                        // Generate slug if not provided
                        if (!$slug) {
                            // Convert Chinese to pinyin or use transliteration
                            // For now, use a timestamp-based slug for non-ASCII titles
                            if (preg_match('/[^\x00-\x7F]/', $title)) {
                                // Contains non-ASCII characters (like Chinese)
                                $slug = 'page-' . date('YmdHis');
                            } else {
                                $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
                                $slug = trim($slug, '-');
                            }
                        }
                        
                        // Create markdown with frontmatter
                        $frontmatterData = [
                            'title' => $title,
                            'slug' => $slug,
                            'date' => date('Y-m-d'),
                        ];
                        $markdown = "---\n" . \Symfony\Component\Yaml\Yaml::dump($frontmatterData, 2, 2) . "---\n\n" . $content;
                        
                        $fileHandler->write('content/pages/' . $slug . '.md', $markdown);
                        $cache->clear('pages', $slug);
                        $message = '頁面已儲存。';
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = '操作失敗：' . $e->getMessage();
        }
    }
}

// Get all pages
$pages = [];
$editPage = null;
try {
    $files = $fileHandler->listFiles('content/pages', 'md');
    foreach ($files as $file) {
        $path = 'content/pages/' . $file;
        $rawContent = $fileHandler->read($path);
        $parsed = $parser->parse($rawContent);
        $frontmatter = $parsed['frontmatter'];
        $slug = $parser->getSlug($frontmatter, $file);
        
        $rawMarkdown = $rawContent;
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $rawContent, $m)) {
            $rawMarkdown = $m[2];
        }
        
        $pages[] = [
            'slug' => $slug,
            'title' => $parser->getTitle($frontmatter, $parsed['content']),
            'date' => $parser->getDate($frontmatter, $fileHandler->getModificationTime($path)),
            'rawContent' => $rawMarkdown,
            'views' => $counter->get('page', $slug),
            'file' => $file
        ];
    }
} catch (\Exception $e) {
    $error = '載入頁面失敗：' . $e->getMessage();
}

// Sort pages by date (newest first)
usort($pages, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Handle edit mode
$editSlug = $_GET['edit'] ?? '';
if ($editSlug) {
    foreach ($pages as $p) {
        if ($p['slug'] === $editSlug) {
            $editPage = $p;
            break;
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1>頁面管理</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo $editPage ? '編輯頁面' : '新增頁面'; ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editPage): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editPage['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">標題 *</label>
                    <input type="text" id="title" name="title" value="<?php echo $editPage ? htmlspecialchars($editPage['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="content">內容 (Markdown) *</label>
                    <textarea id="content" name="content" data-easymde rows="15" required><?php echo $editPage ? htmlspecialchars($editPage['rawContent'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>
                
                <?php if ($editPage): ?>
                    <a href="/admin/pages" class="btn btn-danger" style="text-decoration: none;">取消編輯</a>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editPage ? '更新頁面' : '儲存頁面'; ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3>現有頁面</h3>
            <?php if (!empty($pages)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>標題</th>
                            <th>Slug</th>
                            <th>日期</th>
                            <th>瀏覽人次</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($page['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo number_format($page['views']); ?></td>
                                <td>
                                    <a href="/pages/<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn" target="_blank">檢視</a>
                                    <a href="/admin/pages?edit=<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">編輯</a>
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此頁面？');">刪除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>目前沒有頁面。</p>
            <?php endif; ?>
        </div>
    </div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
