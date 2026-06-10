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

$pageTitle = '內部文件';
$currentPage = 'documents';
$username = $auth->getUsername();

$message = '';
$error = '';

// Ensure documents directory exists
try {
    $fileHandler->createDirectory('content/documents');
} catch (\Exception $e) {
    $error = '無法建立文件目錄：' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = 'CSRF 驗證失敗，請重新整理頁面後再試。';
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
                                $message = '文件已刪除。';
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
                        $error = '標題和內容不能為空。';
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
                        $message = '文件已儲存。';
                    }
                    break;
            }
        } catch (\Exception $e) {
            $error = '操作失敗：' . $e->getMessage();
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
    $error = '載入文件失敗：' . $e->getMessage();
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
        <h1>內部文件</h1>

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
                    <span>作者：<?php echo htmlspecialchars($viewDoc['author'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>｜日期：<?php echo htmlspecialchars($viewDoc['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($viewDoc['category']): ?>
                        <span>｜分類：<?php echo htmlspecialchars($viewDoc['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="doc-content">
                    <?php echo $viewDoc['content']; ?>
                </div>
                <a href="/admin/documents" class="btn" style="margin-top: 1rem;">← 返回文件列表</a>
                <form method="POST" style="display: inline;">
                    <?php echo $csrfField; ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($viewDoc['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此文件？');">刪除文件</button>
                </form>
            </div>
        <?php else: ?>

        <div class="card">
            <h3><?php echo $editDoc ? '編輯文件' : '新增文件'; ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editDoc): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editDoc['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">標題 *</label>
                    <input type="text" id="title" name="title" value="<?php echo $editDoc ? htmlspecialchars($editDoc['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">分類</label>
                    <input type="text" id="category" name="category" value="<?php echo $editDoc ? htmlspecialchars($editDoc['category'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="例如：規範、會議、手冊">
                </div>

                <div class="form-group">
                    <label for="content">內容 (Markdown) *</label>
                    <textarea id="content" name="content" data-easymde rows="15" required><?php echo $editDoc ? htmlspecialchars($editDoc['rawContent'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>

                <?php if ($editDoc): ?>
                    <a href="/admin/documents" class="btn btn-danger" style="text-decoration: none;">取消編輯</a>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editDoc ? '更新文件' : '儲存文件'; ?></button>
            </form>
        </div>

        <div class="card">
            <h3>現有文件</h3>
            <?php if (!empty($documents)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>標題</th>
                            <th>分類</th>
                            <th>作者</th>
                            <th>日期</th>
                            <th>操作</th>
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
                                    <a href="/admin/documents?view=<?php echo htmlspecialchars($doc['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn">檢視</a>
                                    <a href="/admin/documents?edit=<?php echo htmlspecialchars($doc['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">編輯</a>
                                    <form method="POST" style="display: inline;">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($doc['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此文件？');">刪除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>目前沒有文件。</p>
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
