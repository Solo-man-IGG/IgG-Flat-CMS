<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../libs/functions.php';

use CMS\Auth;
use CMS\FileHandler;
use CMS\MarkdownParser;
use CMS\Cache;
use CMS\Counter;
use CMS\Search;

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

$pageTitle = __('admin.pages.page_title');
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
        $error = __('admin.pages.error.csrf');
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
                                $message = __('admin.pages.message.deleted');
                                (new Search($fileHandler))->rebuildIndex();
                                break;
                            }
                        }
                    }
                    break;
                    
                case 'save':
                    $slug = $_POST['slug'] ?? '';
                    $title = $_POST['title'] ?? '';
                    $subtitle = $_POST['subtitle'] ?? '';
                    $content = $_POST['content'] ?? '';
                    
                    if (!$title || !$content) {
                        $error = __('admin.pages.error.empty_fields');
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
                        if ($subtitle) {
                            $frontmatterData['subtitle'] = $subtitle;
                        }
                        $markdown = "---\n" . \Symfony\Component\Yaml\Yaml::dump($frontmatterData, 2, 2) . "---\n\n" . $content;
                        
                        $fileHandler->write('content/pages/' . $slug . '.md', $markdown);
                        $cache->clear('pages', $slug);
                        (new Search($fileHandler))->rebuildIndex();
                        $message = __('admin.pages.message.saved');
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = __('admin.pages.error.operation_failed', $e->getMessage());
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
            'subtitle' => $frontmatter['subtitle'] ?? '',
            'date' => $parser->getDate($frontmatter, $fileHandler->getModificationTime($path)),
            'rawContent' => $rawMarkdown,
            'views' => $counter->get('page', $slug),
            'file' => $file
        ];
    }
} catch (\Exception $e) {
    $error = __('admin.pages.error.load_failed', $e->getMessage());
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
        <h1><?php echo __('admin.pages.heading'); ?></h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo $editPage ? __('admin.pages.form.title_edit') : __('admin.pages.form.title_new'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editPage): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editPage['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title"><?php echo __('admin.pages.form.title'); ?></label>
                    <input type="text" id="title" name="title" value="<?php echo $editPage ? htmlspecialchars($editPage['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subtitle"><?php echo __('admin.pages.form.subtitle'); ?></label>
                    <input type="text" id="subtitle" name="subtitle" value="<?php echo $editPage ? htmlspecialchars($editPage['subtitle'] ?? '', ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="content"><?php echo __('admin.pages.form.content'); ?></label>
                    <textarea id="content" name="content" data-easymde rows="15" required><?php echo $editPage ? htmlspecialchars($editPage['rawContent'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>
                
                <?php if ($editPage): ?>
                    <a href="/admin/pages" class="btn btn-danger" style="text-decoration: none;"><?php echo __('admin.pages.form.cancel_edit'); ?></a>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editPage ? __('admin.pages.form.update') : __('admin.pages.form.save'); ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.pages.list.title'); ?></h3>
            <?php if (!empty($pages)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('admin.pages.list.col_title'); ?></th>
                            <th><?php echo __('admin.pages.list.col_slug'); ?></th>
                            <th><?php echo __('admin.pages.list.col_date'); ?></th>
                            <th><?php echo __('admin.pages.list.col_views'); ?></th>
                            <th><?php echo __('admin.pages.list.col_actions'); ?></th>
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
                                    <a href="/pages/<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn" target="_blank"><?php echo __('admin.pages.list.view'); ?></a>
                                    <a href="/admin/pages?edit=<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success"><?php echo __('admin.pages.list.edit'); ?></a>
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('admin.pages.list.confirm_delete'); ?>');"><?php echo __('admin.pages.list.delete'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo __('admin.pages.list.empty'); ?></p>
            <?php endif; ?>
        </div>
    </div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
