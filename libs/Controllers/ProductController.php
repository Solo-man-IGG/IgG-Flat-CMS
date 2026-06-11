<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS\Controllers;

class ProductController extends BaseController
{
    public function handleProductsList(): void
    {
        $menuItems = $this->menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $products = [];
        $cacheKey = 'products_list';

        $cached = $this->cache->get('products', $cacheKey);
        if ($cached !== null) {
            $products = json_decode($cached, true);
        } else {
            try {
                $files = $this->fileHandler->listFiles('content/products', 'md');
                foreach ($files as $file) {
                    $path = 'content/products/' . $file;
                    $parsed = $this->parser->parseFile($this->fileHandler, $path);
                    $frontmatter = $parsed['frontmatter'];
                    $slug = $this->parser->getSlug($frontmatter, $file);

                    $products[] = [
                        'slug' => $slug,
                        'title' => $this->parser->getTitle($frontmatter, $parsed['content']),
                        'date' => $this->parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                        'price' => $frontmatter['price'] ?? '',
                        'sku' => $frontmatter['sku'] ?? '',
                        'stock' => $frontmatter['stock'] ?? '',
                        'image' => $frontmatter['image'] ?? '',
                        'sort_order' => $frontmatter['sort_order'] ?? 0,
                        'excerpt' => $this->parser->getExcerpt($parsed['content']),
                    ];
                }

                usort($products, function($a, $b) {
                    $orderA = (int)($a['sort_order'] ?? 0);
                    $orderB = (int)($b['sort_order'] ?? 0);
                    if ($orderA !== $orderB) {
                        return $orderA <=> $orderB;
                    }
                    return strtotime($b['date']) - strtotime($a['date']);
                });

                $this->cache->set('products', $cacheKey, json_encode($products));
            } catch (\Exception $e) {
                error_log('Products list error: ' . $e->getMessage());
            }
        }

        require __DIR__ . '/../../templates/default/products-list.php';
    }

    public function handleProduct(string $slug): void
    {
        $menuItems = $this->menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $cacheKey = 'product_' . $slug;

        $cached = $this->cache->get('products', $cacheKey);
        if ($cached !== null) {
            $product = json_decode($cached, true);
        } else {
            $product = null;
            try {
                $files = $this->fileHandler->listFiles('content/products', 'md');
                foreach ($files as $file) {
                    $path = 'content/products/' . $file;
                    $parsed = $this->parser->parseFile($this->fileHandler, $path);
                    $frontmatter = $parsed['frontmatter'];
                    $fileSlug = $this->parser->getSlug($frontmatter, $file);
                    if ($fileSlug === $slug) {

                        $product = [
                            'slug' => $slug,
                            'title' => $this->parser->getTitle($frontmatter, $parsed['content']),
                            'date' => $this->parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                            'price' => $frontmatter['price'] ?? '',
                            'sku' => $frontmatter['sku'] ?? '',
                            'stock' => $frontmatter['stock'] ?? '',
                            'image' => $frontmatter['image'] ?? '',
                            'sort_order' => $frontmatter['sort_order'] ?? 0,
                            'content' => $parsed['content'],
                        ];

                        $this->cache->set('products', $cacheKey, json_encode($product));
                        break;
                    }
                }
            } catch (\Exception $e) {
                error_log('Product error: ' . $e->getMessage());
            }
        }

        if (!$product) {
            $this->notFound();
            return;
        }

        $this->counter->increment('product', $slug);

        require __DIR__ . '/../../templates/default/product.php';
    }
}
