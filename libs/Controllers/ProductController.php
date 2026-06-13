<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS\Controllers;

use CMS\Cache;
use CMS\MarkdownParser;
use CMS\MenuManager;

class ProductController extends BaseController
{
    public function handleProductsList(): void
    {
        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';
        $siteSlogan = $settings['site_slogan'] ?? '';

        $parser = new MarkdownParser();
        $cache = new Cache($this->fileHandler);

        $products = [];
        $cacheKey = 'products_list';

        $cached = $cache->get('products', $cacheKey);
        if ($cached !== null) {
            $products = json_decode($cached, true);
        } else {
            try {
                $files = $this->fileHandler->listFiles('content/products', 'md');
                foreach ($files as $file) {
                    $path = 'content/products/' . $file;
                    $parsed = $parser->parseFile($this->fileHandler, $path);
                    $frontmatter = $parsed['frontmatter'];
                    $slug = $parser->getSlug($frontmatter, $file);

                    $products[] = [
                        'slug' => $slug,
                        'title' => $parser->getTitle($frontmatter, $parsed['content']),
                        'date' => $parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                        'price' => $frontmatter['price'] ?? '',
                        'sku' => $frontmatter['sku'] ?? '',
                        'stock' => $frontmatter['stock'] ?? '',
                        'image' => $frontmatter['image'] ?? '',
                        'sort_order' => $frontmatter['sort_order'] ?? 0,
                        'excerpt' => $parser->getExcerpt($parsed['content']),
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

                $cache->set('products', $cacheKey, json_encode($products));
            } catch (\Exception $e) {
                error_log('Products list error: ' . $e->getMessage());
            }
        }

        require __DIR__ . '/../../templates/default/products-list.php';
    }

    public function handleProduct(string $slug): void
    {
        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';
        $siteSlogan = $settings['site_slogan'] ?? '';

        $parser = new MarkdownParser();
        $cache = new Cache($this->fileHandler);

        $cacheKey = 'product_' . $slug;

        $cached = $cache->get('products', $cacheKey);
        if ($cached !== null) {
            $product = json_decode($cached, true);
        } else {
            $product = null;
            try {
                $files = $this->fileHandler->listFiles('content/products', 'md');
                foreach ($files as $file) {
                    $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                    if ($fileSlug === $slug) {
                        $path = 'content/products/' . $file;
                        $parsed = $parser->parseFile($this->fileHandler, $path);
                        $frontmatter = $parsed['frontmatter'];

                        $product = [
                            'slug' => $slug,
                            'title' => $parser->getTitle($frontmatter, $parsed['content']),
                            'subtitle' => $frontmatter['subtitle'] ?? '',
                            'date' => $parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                            'price' => $frontmatter['price'] ?? '',
                            'sku' => $frontmatter['sku'] ?? '',
                            'stock' => $frontmatter['stock'] ?? '',
                            'image' => $frontmatter['image'] ?? '',
                            'sort_order' => $frontmatter['sort_order'] ?? 0,
                            'content' => $parsed['content'],
                        ];

                        $cache->set('products', $cacheKey, json_encode($product));
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
