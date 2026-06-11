<?php

defined("CMS_ENTRY") or die("Direct access not allowed.");

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);

$auth->requireAuth();

$pageTitle = '檔案管理';
$currentPage = 'files';
$username = $auth->getUsername();

$message = '';
$error = '';

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'bmp'];
$maxFileSize = 20 * 1024 * 1024; // 20MB

// Resolve uploads directory path
$uploadsDir = realpath(__DIR__ . '/../uploads');
if ($uploadsDir === false || !is_dir($uploadsDir)) {
    // Try to create it
    try {
        $fileHandler->createDirectory('uploads');
        $uploadsDir = realpath(__DIR__ . '/../uploads');
    } catch (\Exception $e) {
        $error = '無法建立上傳目錄：' . $e->getMessage();
    }
}
if ($uploadsDir === false) {
    $error = '上傳目錄無法存取。';
}

// Helper: list image files from a directory
function listUploadedFiles(string $dir): array {
    $result = [];
    if (!is_dir($dir)) return $result;
    $items = @scandir($dir);
    if ($items === false) return $result;
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'bmp'];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $result[] = $item;
    }
    return $result;
}

// Handle JSON requests (for modal file browser)
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');
    if ($uploadsDir === false) {
        echo json_encode([]);
        exit;
    }
    $items = listUploadedFiles($uploadsDir);
    $result = [];
    foreach ($items as $file) {
        $fullPath = $uploadsDir . '/' . $file;
        $result[] = [
            'name' => $file,
            'url' => '/uploads/' . rawurlencode($file),
            'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'ext' => strtolower(pathinfo($file, PATHINFO_EXTENSION))
        ];
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle form actions (upload / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = 'CSRF 驗證失敗，請重新整理頁面後再試。';
    } else {
        $action = $_POST['action'] ?? '';

        // --- Delete ---
        if ($action === 'delete') {
            $filename = basename($_POST['filename'] ?? '');
            if ($filename) {
                try {
                    $fileHandler->delete('uploads/' . $filename);
                    $message = '檔案已刪除。';
                } catch (\Exception $e) {
                    $error = '刪除失敗：' . $e->getMessage();
                }
            }
        }

        // --- Upload ---
        if ($action === 'upload') {
            if (empty($_FILES['files']['name'][0])) {
                $error = '請選擇要上傳的檔案。';
            } elseif ($uploadsDir === false) {
                $error = '上傳目錄無法存取。';
            } else {
                $uploaded = 0;
                $failed = 0;

                $idx = 0;
                foreach ($_FILES['files']['name'] as $i => $name) {
                    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;

                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExtensions)) {
                        $failed++;
                        continue;
                    }

                    if ($_FILES['files']['size'][$i] > $maxFileSize) {
                        $failed++;
                        continue;
                    }

                    // Sanitize: ASCII only
                    $baseName = pathinfo($name, PATHINFO_FILENAME);
                    $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
                    $baseName = trim(preg_replace('/_+/', '_', $baseName), '_');
                    if ($baseName === '') $baseName = 'image';

                    $safeName = time() . '-' . $idx . '-' . $baseName . '.' . $ext;

                    $destPath = $uploadsDir . '/' . $safeName;
                    if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $destPath)) {
                        $uploaded++;
                    } else {
                        $failed++;
                    }
                    $idx++;
                }

                if ($uploaded > 0) {
                    $message = "成功上傳 {$uploaded} 個檔案。";
                }
                if ($failed > 0) {
                    $error = "{$failed} 個檔案上傳失敗（不支援的格式或超過大小限制）。";
                }
            }
        }
    }
}

// Get all uploaded files
$files = [];
if ($uploadsDir !== false) {
    $items = listUploadedFiles($uploadsDir);
    foreach ($items as $item) {
        $fullPath = $uploadsDir . '/' . $item;
        $files[] = [
            'name' => $item,
            'url' => '/uploads/' . rawurlencode($item),
            'size' => filesize($fullPath),
            'date' => date('Y-m-d H:i:s', filemtime($fullPath)),
            'ext' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
        ];
    }
}

// Sort by date (newest first)
usort($files, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1>檔案管理</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>上傳檔案</h3>
            <form method="POST" enctype="multipart/form-data">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="upload">

                <div class="form-group">
                    <label for="files">選擇圖片（可多選）</label>
                    <input type="file" id="files" name="files[]" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                    <p class="help-text">支援 JPG、PNG、GIF、WebP，單檔最大 20MB</p>
                </div>

                <div class="upload-drop-zone" id="dropZone">
                    <span>或將圖片拖曳到此區域上傳</span>
                </div>

                <button type="submit" class="btn">上傳檔案</button>
            </form>
        </div>

        <div class="card">
            <h3>已上傳檔案（<?php echo count($files); ?>）</h3>
            <?php if (!empty($files)): ?>
                <div class="file-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="file-item">
                            <a class="file-thumb" href="<?php echo htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="background-image: url('<?php echo htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8'); ?>')"></a>
                            <div class="file-info">
                                <div class="file-name" title="<?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="file-meta">
                                    <span><?php echo number_format($file['size'] / 1024, 1); ?> KB</span>
                                    <span><?php echo htmlspecialchars($file['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="file-url">
                                    <input type="text" value="<?php echo htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8'); ?>" readonly onclick="this.select()">
                                    <button class="btn btn-sm" onclick="copyUrl(this, '<?php echo htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8'); ?>')">複製</button>
                                </div>
                            </div>
                            <form method="POST" class="file-actions">
                                <?php echo $csrfField; ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('確定要刪除「<?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>」？')">刪除</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>尚未上傳任何檔案。</p>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .help-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .upload-drop-zone {
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            color: #94a3b8;
            margin-bottom: 1rem;
            transition: border-color 0.2s, background 0.2s;
            cursor: pointer;
        }

        .upload-drop-zone:hover,
        .upload-drop-zone.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }

        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .file-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .file-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .file-thumb {
            display: block;
            width: 100%;
            height: 160px;
            background: #f1f5f9;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            transition: opacity 0.2s;
        }

        .file-thumb:hover {
            opacity: 0.85;
        }

        .file-info {
            padding: 0.75rem;
        }

        .file-name {
            font-weight: 500;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }

        .file-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .file-url {
            display: flex;
            gap: 0.25rem;
        }

        .file-url input {
            flex: 1;
            font-size: 0.75rem;
            padding: 0.3rem 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            background: #fff;
            font-family: monospace;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .file-actions {
            padding: 0 0.75rem 0.75rem;
        }

        .file-actions .btn {
            width: 100%;
            text-align: center;
        }
    </style>

    <script>
        // Drag and drop support
        var dropZone = document.getElementById('dropZone');
        var fileInput = document.getElementById('files');

        dropZone.addEventListener('click', function() {
            fileInput.click();
        });

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
            }
        });

        // Copy URL helper with fallback
        function copyUrl(btn, url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    showCopied(btn);
                }).catch(function() {
                    fallbackCopy(btn, url);
                });
            } else {
                fallbackCopy(btn, url);
            }
        }

        function fallbackCopy(btn, url) {
            var input = btn.parentElement.querySelector('input');
            if (input) {
                input.value = url;
                input.select();
                document.execCommand('copy');
                showCopied(btn);
            }
        }

        function showCopied(btn) {
            var orig = btn.textContent;
            btn.textContent = '已複製!';
            btn.style.background = '#10b981';
            setTimeout(function() {
                btn.textContent = orig;
                btn.style.background = '';
            }, 1500);
        }
    </script>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
