<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS\Controllers;

class PageController extends BaseController
{
    public function handleHome(): void
    {
        $settings = $this->loadSettings();
        $homePageSlug = $settings['home_page'] ?? '';

        if ($homePageSlug) {
            $menuItems = $this->menuManager->getTemplateData();
            $siteTitle = $settings['site_title'] ?? 'My Site';

            $cacheKey = 'page_' . $homePageSlug;
            $page = null;

            $cached = $this->cache->get('pages', $cacheKey);
            if ($cached !== null) {
                $page = json_decode($cached, true);
            } else {
                try {
                    $files = $this->fileHandler->listFiles('content/pages', 'md');
                    foreach ($files as $file) {
                        $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                        if ($fileSlug === $homePageSlug) {
                            $path = 'content/pages/' . $file;
                            $parsed = $this->parser->parseFile($this->fileHandler, $path);
                            $frontmatter = $parsed['frontmatter'];

                            $page = [
                                'slug' => $homePageSlug,
                                'title' => $this->parser->getTitle($frontmatter, $parsed['content']),
                                'date' => $this->parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                                'content' => $parsed['content'],
                            ];

                            $this->cache->set('pages', $cacheKey, json_encode($page));
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Home page error: ' . $e->getMessage());
                }
            }

            if ($page) {
                $this->counter->increment('home', 'home');
                $pageTitle = $page['title'];
                require __DIR__ . '/../../templates/default/page.php';
                return;
            }
        }

        $menuItems = $this->menuManager->getTemplateData();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $this->counter->increment('home', 'home');

        require __DIR__ . '/../../templates/default/header.php';
        echo '<div class="home-page">
            <h1>歡迎使用 IgG CMS</h1>
            <p>這是一個基於 PHP 8.1+ 的輕量級 IgG Flat CMS - Lightweight Flat-File CMS。</p>
            <p><a href="/blog" class="btn">瀏覽部落格</a> <a href="/products" class="btn">查看產品</a> <a href="/contact" class="btn">聯絡我們</a></p>
        </div>';
        require __DIR__ . '/../../templates/default/footer.php';
    }

    public function handlePage(string $slug): void
    {
        $menuItems = $this->menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $cacheKey = 'page_' . $slug;

        $cached = $this->cache->get('pages', $cacheKey);
        if ($cached !== null) {
            $page = json_decode($cached, true);
        } else {
            $page = null;
            try {
                $files = $this->fileHandler->listFiles('content/pages', 'md');
                foreach ($files as $file) {
                    $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                    if ($fileSlug === $slug) {
                        $path = 'content/pages/' . $file;
                        $parsed = $this->parser->parseFile($this->fileHandler, $path);
                        $frontmatter = $parsed['frontmatter'];

                        $page = [
                            'slug' => $slug,
                            'title' => $this->parser->getTitle($frontmatter, $parsed['content']),
                            'date' => $this->parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                            'content' => $parsed['content'],
                        ];

                        $this->cache->set('pages', $cacheKey, json_encode($page));
                        break;
                    }
                }
            } catch (\Exception $e) {
                error_log('Page error: ' . $e->getMessage());
            }
        }

        if (!$page) {
            $this->notFound();
            return;
        }

        $this->counter->increment('page', $slug);

        require __DIR__ . '/../../templates/default/page.php';
    }
}
