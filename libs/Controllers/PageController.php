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

class PageController extends BaseController
{
    public function handleHome(): void
    {
        $settings = $this->loadSettings();
        $homePageSlug = $settings['home_page'] ?? '';

        $siteSlogan = $settings['site_slogan'] ?? '';

        if ($homePageSlug) {
            $menuManager = new MenuManager($this->fileHandler);
            $menuItems = $menuManager->getTemplateData();
            $siteTitle = $settings['site_title'] ?? 'My Site';

            $parser = new MarkdownParser();
            $cache = new Cache($this->fileHandler);

            $cacheKey = 'page_' . $homePageSlug;
            $page = null;

            $cached = $cache->get('pages', $cacheKey);
            if ($cached !== null) {
                $page = json_decode($cached, true);
            } else {
                try {
                    $files = $this->fileHandler->listFiles('content/pages', 'md');
                    foreach ($files as $file) {
                        $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                        if ($fileSlug === $homePageSlug) {
                            $path = 'content/pages/' . $file;
                            $parsed = $parser->parseFile($this->fileHandler, $path);
                            $frontmatter = $parsed['frontmatter'];

                            $page = [
                                'slug' => $homePageSlug,
                                'title' => $parser->getTitle($frontmatter, $parsed['content']),
                                'subtitle' => $frontmatter['subtitle'] ?? '',
                                'date' => $parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                                'content' => $parsed['content'],
                            ];

                            $cache->set('pages', $cacheKey, json_encode($page));
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

        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $this->counter->increment('home', 'home');

        require __DIR__ . '/../../templates/default/header.php';
        echo '<div class="home-page">
            <h1>' . __('page.home.welcome') . '</h1>
            <p>' . __('page.home.description') . '</p>
            <p><a href="/blog" class="btn">' . __('page.home.browse_blog') . '</a> <a href="/products" class="btn">' . __('page.home.view_products') . '</a> <a href="/contact" class="btn">' . __('page.home.contact_us') . '</a></p>
        </div>';
        require __DIR__ . '/../../templates/default/footer.php';
    }

    public function handlePage(string $slug): void
    {
        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';
        $siteSlogan = $settings['site_slogan'] ?? '';

        $parser = new MarkdownParser();
        $cache = new Cache($this->fileHandler);

        $cacheKey = 'page_' . $slug;

        $cached = $cache->get('pages', $cacheKey);
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
                        $parsed = $parser->parseFile($this->fileHandler, $path);
                        $frontmatter = $parsed['frontmatter'];

                        $page = [
                            'slug' => $slug,
                            'title' => $parser->getTitle($frontmatter, $parsed['content']),
                            'subtitle' => $frontmatter['subtitle'] ?? '',
                            'date' => $parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                            'content' => $parsed['content'],
                        ];

                        $cache->set('pages', $cacheKey, json_encode($page));
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
