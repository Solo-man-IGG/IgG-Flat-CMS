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

class BlogController extends BaseController
{
    public function handleBlogList(): void
    {
        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';
        $siteSlogan = $settings['site_slogan'] ?? '';

        $parser = new MarkdownParser();
        $cache = new Cache($this->fileHandler);

        $perPage = 12;
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $selectedTag = $_GET['tag'] ?? '';

        $posts = [];
        $cacheKey = 'blog_list';

        $cached = $cache->get('blog', $cacheKey);
        if ($cached !== null) {
            $posts = json_decode($cached, true);
        } else {
            try {
                $files = $this->fileHandler->listFiles('content/blog', 'md');
                foreach ($files as $file) {
                    $path = 'content/blog/' . $file;
                    $parsed = $parser->parseFile($this->fileHandler, $path);
                    $frontmatter = $parsed['frontmatter'];
                    $slug = $parser->getSlug($frontmatter, $file);

                    if (!($frontmatter['published'] ?? true)) {
                        continue;
                    }

                    $tags = $frontmatter['tags'] ?? [];
                    if (!is_array($tags)) {
                        $tags = $tags ? [$tags] : [];
                    }

                    $posts[] = [
                        'slug' => $slug,
                        'title' => $parser->getTitle($frontmatter, $parsed['content']),
                        'date' => $parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                        'author' => $frontmatter['author'] ?? '',
                        'excerpt' => $parser->getExcerpt($parsed['content']),
                        'tags' => $tags,
                    ];
                }

                usort($posts, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });

                $cache->set('blog', $cacheKey, json_encode($posts));
            } catch (\Exception $e) {
                error_log('Blog list error: ' . $e->getMessage());
            }
        }

        $allTags = [];
        foreach ($posts as $p) {
            foreach ($p['tags'] ?? [] as $tag) {
                $allTags[$tag] = true;
            }
        }
        $allTags = array_keys($allTags);
        sort($allTags);

        if ($selectedTag !== '') {
            $posts = array_values(array_filter($posts, function($p) use ($selectedTag) {
                return in_array($selectedTag, $p['tags'] ?? []);
            }));
        }

        $totalPosts = count($posts);
        $totalPages = max(1, (int)ceil($totalPosts / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        $offset = ($currentPage - 1) * $perPage;
        $posts = array_slice($posts, $offset, $perPage);

        require __DIR__ . '/../../templates/default/blog-list.php';
    }

    public function handleBlogPost(string $slug): void
    {
        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';
        $siteSlogan = $settings['site_slogan'] ?? '';

        $parser = new MarkdownParser();
        $cache = new Cache($this->fileHandler);

        $cacheKey = 'blog_post_' . $slug;

        $cached = $cache->get('blog', $cacheKey);
        if ($cached !== null) {
            $post = json_decode($cached, true);
        } else {
            $post = null;
            try {
                $files = $this->fileHandler->listFiles('content/blog', 'md');
                foreach ($files as $file) {
                    $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                    if ($fileSlug === $slug) {
                        $path = 'content/blog/' . $file;
                        $parsed = $parser->parseFile($this->fileHandler, $path);
                        $frontmatter = $parsed['frontmatter'];

                        if (!($frontmatter['published'] ?? true)) {
                            $this->notFound();
                            return;
                        }

                        $post = [
                            'slug' => $slug,
                            'title' => $parser->getTitle($frontmatter, $parsed['content']),
                            'subtitle' => $frontmatter['subtitle'] ?? '',
                            'date' => $parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                            'author' => $frontmatter['author'] ?? '',
                            'tags' => $frontmatter['tags'] ?? [],
                            'banner' => $frontmatter['banner'] ?? '',
                            'content' => $parsed['content'],
                        ];

                        $cache->set('blog', $cacheKey, json_encode($post));
                        break;
                    }
                }
            } catch (\Exception $e) {
                error_log('Blog post error: ' . $e->getMessage());
            }
        }

        if (!$post) {
            $this->notFound();
            return;
        }

        $relatedPosts = [];
        $currentTags = $post['tags'] ?? [];
        if (!empty($currentTags)) {
            try {
                $allFiles = $this->fileHandler->listFiles('content/blog', 'md');
                foreach ($allFiles as $file) {
                    $fileSlug = pathinfo($file, PATHINFO_FILENAME);
                    if ($fileSlug === $slug) {
                        continue;
                    }
                    $relatedPath = 'content/blog/' . $file;
                    $relatedParsed = $parser->parseFile($this->fileHandler, $relatedPath);
                    $relatedFrontmatter = $relatedParsed['frontmatter'];
                    if (!($relatedFrontmatter['published'] ?? true)) {
                        continue;
                    }
                    $relatedTags = $relatedFrontmatter['tags'] ?? [];
                    if (!is_array($relatedTags)) {
                        $relatedTags = [];
                    }
                    $sharedTags = array_intersect($currentTags, $relatedTags);
                    if (!empty($sharedTags)) {
                        $relatedPosts[] = [
                            'slug' => $parser->getSlug($relatedFrontmatter, $file),
                            'title' => $parser->getTitle($relatedFrontmatter, $relatedParsed['content']),
                            'date' => $parser->getDate($relatedFrontmatter, $this->fileHandler->getModificationTime($relatedPath)),
                        ];
                    }
                }
                usort($relatedPosts, function ($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                $relatedPosts = array_slice($relatedPosts, 0, 5);
            } catch (\Exception $e) {
                error_log('Related posts error: ' . $e->getMessage());
            }
        }

        $this->counter->increment('blog', $slug);

        require __DIR__ . '/../../templates/default/blog-post.php';
    }
}
