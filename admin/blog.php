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

$pageTitle = __('admin.blog.page_title');
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
        $error = __('admin.blog.error.csrf');
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
                                    $cache->clear('blog', 'blog_post_' . $slug);
                                    $cache->clear('blog', 'blog_list');
                                try {
                                    $counterFile = 'content/counters/blog-' . $slug . '.json';
                                    if ($fileHandler->exists($counterFile)) {
                                        $fileHandler->delete($counterFile);
                                    }
                                } catch (\Exception $e) {
                                    error_log('Failed to delete counter: ' . $e->getMessage());
                                }
                                $message = __('admin.blog.message.deleted');
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
                    $author = $_POST['author'] ?? '';
                    $tags = $_POST['tags'] ?? '';
                    $banner = $_POST['banner'] ?? '';
                    $published = isset($_POST['published']) ? 'true' : 'false';
                    
                    if (!$title || !$content) {
                        $error = __('admin.blog.error.empty_fields');
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
                        if ($subtitle) {
                            $frontmatterData['subtitle'] = $subtitle;
                        }
                        if ($banner) {
                            $frontmatterData['banner'] = $banner;
                        }
                        if (!empty($tagsArray)) {
                            $frontmatterData['tags'] = array_values($tagsArray);
                        }
                        $frontmatter = "---\n" . \Symfony\Component\Yaml\Yaml::dump($frontmatterData, 2, 2) . "---\n\n" . $content;
                        
                        $fileHandler->write('content/blog/' . $slug . '.md', $frontmatter);
                        $cache->clear('blog', 'blog_post_' . $slug);
                        $cache->clear('blog', 'blog_list');
                        (new Search($fileHandler))->rebuildIndex();
                        $message = __('admin.blog.message.saved');
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = __('admin.blog.error.operation_failed', $e->getMessage());
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
            'subtitle' => $frontmatter['subtitle'] ?? '',
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
    $error = __('admin.blog.error.load_failed', $e->getMessage());
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
        <h1><?php echo __('admin.blog.heading'); ?></h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo $editPost ? __('admin.blog.form.title_edit') : __('admin.blog.form.title_new'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editPost): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editPost['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title"><?php echo __('admin.blog.form.title'); ?></label>
                    <input type="text" id="title" name="title" value="<?php echo $editPost ? htmlspecialchars($editPost['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subtitle"><?php echo __('admin.blog.form.subtitle'); ?></label>
                    <input type="text" id="subtitle" name="subtitle" value="<?php echo $editPost ? htmlspecialchars($editPost['subtitle'] ?? '', ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="author"><?php echo __('admin.blog.form.author'); ?></label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($editPost['author'] ?? $username, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="tags"><?php echo __('admin.blog.form.tags'); ?></label>
                    <input type="text" id="tags" name="tags" value="<?php echo $editPost ? htmlspecialchars($editPost['tags'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="<?php echo __('admin.blog.form.tags_placeholder'); ?>">
                </div>
                
                    <div class="form-group">
                        <label for="banner"><?php echo __('admin.blog.form.banner'); ?></label>
                        <input type="text" id="banner" name="banner" value="<?php echo $editPost ? htmlspecialchars($editPost['banner'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="<?php echo __('admin.blog.form.banner_placeholder'); ?>">
                    </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="published" value="true" <?php echo (!$editPost || $editPost['published']) ? 'checked' : ''; ?>>
                        <?php echo __('admin.blog.form.published'); ?>
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="content"><?php echo __('admin.blog.form.content'); ?></label>
                    <textarea id="content" name="content" data-easymde rows="20" required><?php echo $editPost ? htmlspecialchars($editPost['rawContent'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>
                
                <?php if ($editPost): ?>
                    <a href="/admin/blog" class="btn btn-danger" style="text-decoration: none;"><?php echo __('admin.blog.form.cancel_edit'); ?></a>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editPost ? __('admin.blog.form.update') : __('admin.blog.form.save'); ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3><?php echo __('admin.blog.list.title'); ?></h3>
            <?php if (!empty($posts)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('admin.blog.list.col_title'); ?></th>
                            <th><?php echo __('admin.blog.list.col_author'); ?></th>
                            <th><?php echo __('admin.blog.list.col_date'); ?></th>
                            <th><?php echo __('admin.blog.list.col_status'); ?></th>
                            <th><?php echo __('admin.blog.list.col_views'); ?></th>
                            <th><?php echo __('admin.blog.list.col_actions'); ?></th>
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
                                        <span style="color: #10b981;"><?php echo __('admin.blog.list.status_published'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #6b7280;"><?php echo __('admin.blog.list.status_draft'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($post['views']); ?></td>
                                <td>
                                    <a href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn" target="_blank"><?php echo __('admin.blog.list.view'); ?></a>
                                    <a href="/admin/blog?edit=<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success"><?php echo __('admin.blog.list.edit'); ?></a>
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('admin.blog.list.confirm_delete'); ?>');"><?php echo __('admin.blog.list.delete'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo __('admin.blog.list.empty'); ?></p>
            <?php endif; ?>
        </div>
    </div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
