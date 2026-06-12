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

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize components
$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);
$parser = new MarkdownParser();

// Require authentication
$auth->requireAuth();

$pageTitle = __('admin.documents.page_title');
$currentPage = 'documents';
$username = $auth->getUsername();

$message = '';
$error = '';

// Ensure documents directory exists
try {
    $fileHandler->createDirectory('content/documents');
} catch (\Exception $e) {
    $error = __('admin.documents.error.create_dir_failed', $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = __('admin.documents.error.csrf');
    } else {
        try {
            switch ($action) {
                case 'delete':
                    $slug = $_POST['slug'] ?? '';
                    if ($slug) {
                        $files = $fileHandler->listFiles('content/documents', 'md');
                        foreach ($files as $file) {
                            $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                            if ($fileSlug === $slug) {
                                $fileHandler->delete('content/documents/' . $file);
                                $message = __('admin.documents.message.deleted');
                                break;
                            }
                        }
                    }
                    break;

                case 'save':
                    $slug = $_POST['slug'] ?? '';
                    $title = $_POST['title'] ?? '';
                    $content = $_POST['content'] ?? '';
                    $category = $_POST['category'] ?? '';

                    if (!$title || !$content) {
                        $error = __('admin.documents.error.empty_fields');
                    } else {
                        if (!$slug) {
                            $slug = 'doc-' . time();
                        }

                        $frontmatterData = [
                            'title' => $title,
                            'slug' => $slug,
                            'date' => date('Y-m-d'),
                            'author' => $username,
                        ];
                        if ($category) {
                            $frontmatterData['category'] = $category;
                        }
                        $markdown = "---\n" . \Symfony\Component\Yaml\Yaml::dump($frontmatterData, 2, 2) . "---\n\n" . $content;

                        $fileHandler->write('content/documents/' . $slug . '.md', $markdown);
                        $message = __('admin.documents.message.saved');
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = __('admin.documents.error.operation_failed', $e->getMessage());
        }
    }
}

// Get all documents
$documents = [];
$editDoc = null;
try {
    $files = $fileHandler->listFiles('content/documents', 'md');
    foreach ($files as $file) {
        $path = 'content/documents/' . $file;
        $rawContent = $fileHandler->read($path);
        $parsed = $parser->parse($rawContent);
        $frontmatter = $parsed['frontmatter'];
        $slug = $parser->getSlug($frontmatter, $file);

        $rawMarkdown = $rawContent;
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $rawContent, $m)) {
            $rawMarkdown = $m[2];
        }

        $documents[] = [
            'slug' => $slug,
            'title' => $parser->getTitle($frontmatter, $parsed['content']),
            'date' => $parser->getDate($frontmatter, $fileHandler->getModificationTime($path)),
            'author' => $frontmatter['author'] ?? '',
            'category' => $frontmatter['category'] ?? '',
            'content' => $parsed['content'],
            'rawContent' => $rawMarkdown,
            'file' => $file
        ];
    }
} catch (\Exception $e) {
    $error = __('admin.documents.error.load_failed', $e->getMessage());
}

// Sort by date (newest first)
usort($documents, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Check if viewing a single document
$viewDoc = null;
$viewSlug = $_GET['view'] ?? '';
if ($viewSlug) {
    foreach ($documents as $doc) {
        if ($doc['slug'] === $viewSlug) {
            $viewDoc = $doc;
            break;
        }
    }
}

// Handle edit mode
$editSlug = $_GET['edit'] ?? '';
if ($editSlug) {
    foreach ($documents as $d) {
        if ($d['slug'] === $editSlug) {
            $editDoc = $d;
            break;
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1><?php echo __('admin.documents.heading'); ?></h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($viewDoc): ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($viewDoc['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="doc-meta">
                    <span><?php echo __('admin.documents.view.author_label'); ?><?php echo htmlspecialchars($viewDoc['author'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span><?php echo __('admin.documents.view.date_label'); ?><?php echo htmlspecialchars($viewDoc['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($viewDoc['category']): ?>
                        <span><?php echo __('admin.documents.view.category_label'); ?><?php echo htmlspecialchars($viewDoc['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="doc-content">
                    <?php echo $viewDoc['content']; ?>
                </div>
                <a href="/admin/documents" class="btn" style="margin-top: 1rem;"><?php echo __('admin.documents.view.back_list'); ?></a>
                <form method="POST" style="display: inline;">
                    <?php echo $csrfField; ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($viewDoc['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('admin.documents.view.confirm_delete'); ?>');"><?php echo __('admin.documents.view.delete'); ?></button>
                </form>
            </div>
        <?php else: ?>

        <div class="card">
            <h3><?php echo $editDoc ? __('admin.documents.form.title_edit') : __('admin.documents.form.title_new'); ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editDoc): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editDoc['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title"><?php echo __('admin.documents.form.title'); ?></label>
                    <input type="text" id="title" name="title" value="<?php echo $editDoc ? htmlspecialchars($editDoc['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="category"><?php echo __('admin.documents.form.category'); ?></label>
                    <input type="text" id="category" name="category" value="<?php echo $editDoc ? htmlspecialchars($editDoc['category'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="<?php echo __('admin.documents.form.category_placeholder'); ?>">
                </div>

                <div class="form-group">
                    <label for="content"><?php echo __('admin.documents.form.content'); ?></label>
                    <textarea id="content" name="content" data-easymde rows="15" required><?php echo $editDoc ? htmlspecialchars($editDoc['rawContent'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>

                <?php if ($editDoc): ?>
                    <a href="/admin/documents" class="btn btn-danger" style="text-decoration: none;"><?php echo __('admin.documents.form.cancel_edit'); ?></a>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editDoc ? __('admin.documents.form.update') : __('admin.documents.form.save'); ?></button>
            </form>
        </div>

        <div class="card">
            <h3><?php echo __('admin.documents.list.title'); ?></h3>
            <?php if (!empty($documents)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('admin.documents.list.col_title'); ?></th>
                            <th><?php echo __('admin.documents.list.col_category'); ?></th>
                            <th><?php echo __('admin.documents.list.col_author'); ?></th>
                            <th><?php echo __('admin.documents.list.col_date'); ?></th>
                            <th><?php echo __('admin.documents.list.col_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($doc['category'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($doc['author'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($doc['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a href="/admin/documents?view=<?php echo htmlspecialchars($doc['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn"><?php echo __('admin.documents.list.view'); ?></a>
                                    <a href="/admin/documents?edit=<?php echo htmlspecialchars($doc['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success"><?php echo __('admin.documents.list.edit'); ?></a>
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($doc['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('admin.documents.list.confirm_delete'); ?>');"><?php echo __('admin.documents.list.delete'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo __('admin.documents.list.empty'); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <style>
    .doc-meta {
        color: #6b7280;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .doc-content {
        line-height: 1.8;
    }

    .doc-content img {
        max-width: 100%;
        border-radius: 0.5rem;
    }
    </style>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
