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

$pageTitle = '產品管理';
$currentPage = 'products';
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
                        $files = $fileHandler->listFiles('content/products', 'md');
                        foreach ($files as $file) {
                            $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                            if ($fileSlug === $slug) {
                                $fileHandler->delete('content/products/' . $file);
                                $cache->clear('products', $slug);
                                try {
                                    $counterFile = 'content/counters/product-' . $slug . '.json';
                                    if ($fileHandler->exists($counterFile)) {
                                        $fileHandler->delete($counterFile);
                                    }
                                } catch (\Exception $e) {
                                    error_log('Failed to delete counter: ' . $e->getMessage());
                                }
                                $message = '產品已刪除。';
                                break;
                            }
                        }
                    }
                    break;
                    
                case 'save':
                    $slug = $_POST['slug'] ?? '';
                    $title = $_POST['title'] ?? '';
                    $content = $_POST['content'] ?? '';
                    $price = $_POST['price'] ?? '';
                    $sku = $_POST['sku'] ?? '';
                    $stock = $_POST['stock'] ?? '';
                    $image = $_POST['image'] ?? '';
                    $sortOrder = $_POST['sort_order'] ?? '0';
                    
                    if (!$title || !$content) {
                        $error = '標題和內容不能為空。';
                    } else {
                        if (!$slug) {
                            $slug = 'product-' . time();
                        }
                        
                        // Create markdown with frontmatter
                        $frontmatterData = [
                            'title' => $title,
                            'slug' => $slug,
                            'date' => date('Y-m-d'),
                            'sort_order' => (int)$sortOrder,
                        ];
                        if ($price) {
                            $frontmatterData['price'] = $price;
                        }
                        if ($sku) {
                            $frontmatterData['sku'] = $sku;
                        }
                        if ($stock) {
                            $frontmatterData['stock'] = $stock;
                        }
                        if ($image) {
                            $frontmatterData['image'] = $image;
                        }
                        $frontmatter = "---\n" . \Symfony\Component\Yaml\Yaml::dump($frontmatterData, 2, 2) . "---\n\n" . $content;
                        
                        $fileHandler->write('content/products/' . $slug . '.md', $frontmatter);
                        $cache->clear('products', $slug);
                        $message = '產品已儲存。';
                    }
                    break;

                case 'reorder':
                    $orders = $_POST['order'] ?? [];
                    foreach ($orders as $slug => $sortOrder) {
                        $files = $fileHandler->listFiles('content/products', 'md');
                        foreach ($files as $file) {
                            $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                            if ($fileSlug === $slug) {
                                $path = 'content/products/' . $file;
                                $content = $fileHandler->read($path);
                                // Replace or add sort_order in frontmatter
                                if (preg_match('/^(---\s*\n.*?\nsort_order: )\d+/s', $content)) {
                                    $content = preg_replace('/^(---\s*\n.*?\nsort_order: )\d+/s', '$1' . $sortOrder, $content);
                                } else {
                                    $content = preg_replace('/^(---\s*\n)/s', '$1sort_order: ' . $sortOrder . "\n", $content);
                                }
                                $fileHandler->write($path, $content);
                                $cache->clear('products', $slug);
                                break;
                            }
                        }
                    }
                    $message = '產品排序已更新。';
                    break;
            }
        } catch (\Exception $e) {
            $error = '操作失敗：' . $e->getMessage();
        }
    }
}

// Get all products
$products = [];
$editProduct = null;
try {
    $files = $fileHandler->listFiles('content/products', 'md');
    foreach ($files as $file) {
        $path = 'content/products/' . $file;
        $rawContent = $fileHandler->read($path);
        $parsed = $parser->parse($rawContent);
        $frontmatter = $parsed['frontmatter'];
        $slug = $parser->getSlug($frontmatter, $file);
        
        $rawMarkdown = $rawContent;
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $rawContent, $m)) {
            $rawMarkdown = $m[2];
        }
        
        $products[] = [
            'slug' => $slug,
            'title' => $parser->getTitle($frontmatter, $parsed['content']),
            'date' => $parser->getDate($frontmatter, $fileHandler->getModificationTime($path)),
            'price' => $frontmatter['price'] ?? '',
            'sku' => $frontmatter['sku'] ?? '',
            'stock' => $frontmatter['stock'] ?? '',
            'image' => $frontmatter['image'] ?? '',
            'sort_order' => $frontmatter['sort_order'] ?? 0,
            'rawContent' => $rawMarkdown,
            'views' => $counter->get('product', $slug),
            'file' => $file
        ];
    }
} catch (\Exception $e) {
    $error = '載入產品失敗：' . $e->getMessage();
}

// Sort by sort_order (asc), then by date (newest first) as tiebreaker
usort($products, function($a, $b) {
    $orderA = (int)($a['sort_order'] ?? 0);
    $orderB = (int)($b['sort_order'] ?? 0);
    if ($orderA !== $orderB) {
        return $orderA <=> $orderB;
    }
    return strtotime($b['date']) - strtotime($a['date']);
});

// Handle edit mode
$editSlug = $_GET['edit'] ?? '';
if ($editSlug) {
    foreach ($products as $p) {
        if ($p['slug'] === $editSlug) {
            $editProduct = $p;
            break;
        }
    }
}

$csrfField = $auth->getCsrfField();

require __DIR__ . '/../templates/admin/header.php';
require __DIR__ . '/../templates/admin/sidebar.php';
?>

    <div class="admin-content">
        <h1>產品管理</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo $editProduct ? '編輯產品' : '新增產品'; ?></h3>
            <form method="POST">
                <?php echo $csrfField; ?>
                <input type="hidden" name="action" value="save">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editProduct['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">產品名稱 *</label>
                    <input type="text" id="title" name="title" value="<?php echo $editProduct ? htmlspecialchars($editProduct['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="price">價格</label>
                    <input type="text" id="price" name="price" value="<?php echo $editProduct ? htmlspecialchars($editProduct['price'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="例如：19900 或 客製報價">
                </div>
                
                <div class="form-group">
                    <label for="sku">SKU</label>
                    <input type="text" id="sku" name="sku" value="<?php echo $editProduct ? htmlspecialchars($editProduct['sku'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock">庫存數量</label>
                    <input type="number" id="stock" name="stock" value="<?php echo $editProduct ? htmlspecialchars($editProduct['stock'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="image">圖片 URL</label>
                    <input type="url" id="image" name="image" value="<?php echo $editProduct ? htmlspecialchars($editProduct['image'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="https://example.com/image.jpg">
                </div>
                
                <div class="form-group">
                    <label for="sort_order">排序順序 (數字越小越前面)</label>
                    <input type="number" id="sort_order" name="sort_order" value="<?php echo $editProduct ? htmlspecialchars($editProduct['sort_order'], ENT_QUOTES, 'UTF-8') : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="content">產品描述 (Markdown) *</label>
                    <textarea id="content" name="content" data-easymde rows="15" required><?php echo $editProduct ? htmlspecialchars($editProduct['rawContent'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>
                
                <?php if ($editProduct): ?>
                    <a href="/admin/products" class="btn btn-danger" style="text-decoration: none;">取消編輯</a>
                <?php endif; ?>
                <button type="submit" class="btn"><?php echo $editProduct ? '更新產品' : '儲存產品'; ?></button>
            </form>
        </div>
        
        <div class="card">
            <h3>現有產品</h3>
            <?php if (!empty($products)): ?>
                <form method="POST">
                    <?php echo $csrfField; ?>
                    <input type="hidden" name="action" value="reorder">
                    <table>
                        <thead>
                            <tr>
                                <th>排序</th>
                                <th>產品名稱</th>
                                <th>價格</th>
                                <th>SKU</th>
                                <th>庫存</th>
                                <th>日期</th>
                                <th>瀏覽人次</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <input type="number" name="order[<?php echo htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo (int)($product['sort_order'] ?? 0); ?>" style="width: 60px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($product['price'] !== '' && $product['price'] !== null): ?>
                                            <?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($product['stock'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($product['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo number_format($product['views']); ?></td>
                                    <td>
                                        <a href="/products/<?php echo htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn" target="_blank">檢視</a>
                                        <a href="/admin/products?edit=<?php echo htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">編輯</a>
                                        <form method="POST" style="display: inline;">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此產品？');">刪除</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn" style="margin-top: 1rem;">更新排序</button>
                </form>
            <?php else: ?>
                <p>目前沒有產品。</p>
            <?php endif; ?>
        </div>
    </div>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>
