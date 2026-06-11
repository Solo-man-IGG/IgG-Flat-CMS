<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS\Controllers;

class BlogController extends BaseController
{
    public function handleBlogList(): void
    {
        $menuItems = $this->menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $perPage = 12;
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $selectedTag = $_GET['tag'] ?? '';

        $posts = [];
        $cacheKey = 'blog_list';

        $cached = $this->cache->get('blog', $cacheKey);
        if ($cached !== null) {
            $posts = json_decode($cached, true);
        } else {
            try {
                $files = $this->fileHandler->listFiles('content/blog', 'md');
                foreach ($files as $file) {
                    $path = 'content/blog/' . $file;
                    $parsed = $this->parser->parseFile($this->fileHandler, $path);
                    $frontmatter = $parsed['frontmatter'];
                    $slug = $this->parser->getSlug($frontmatter, $file);

                    if (!($frontmatter['published'] ?? true)) {
                        continue;
                    }

                    $tags = $frontmatter['tags'] ?? [];
                    if (!is_array($tags)) {
                        $tags = $tags ? [$tags] : [];
                    }

                    $posts[] = [
                        'slug' => $slug,
                        'title' => $this->parser->getTitle($frontmatter, $parsed['content']),
                        'date' => $this->parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                        'author' => $frontmatter['author'] ?? '',
                        'excerpt' => $this->parser->getExcerpt($parsed['content']),
                        'tags' => $tags,
                    ];
                }

                usort($posts, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });

                $this->cache->set('blog', $cacheKey, json_encode($posts));
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
        $menuItems = $this->menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $cacheKey = 'blog_post_' . $slug;

        $cached = $this->cache->get('blog', $cacheKey);
        if ($cached !== null) {
            $post = json_decode($cached, true);
        } else {
            $post = null;
            try {
                $files = $this->fileHandler->listFiles('content/blog', 'md');
                foreach ($files as $file) {
                    $path = 'content/blog/' . $file;
                    $parsed = $this->parser->parseFile($this->fileHandler, $path);
                    $frontmatter = $parsed['frontmatter'];
                    $fileSlug = $this->parser->getSlug($frontmatter, $file);
                    if ($fileSlug === $slug) {

                        if (!($frontmatter['published'] ?? true)) {
                            $this->notFound();
                            return;
                        }

                        $post = [
                            'slug' => $slug,
                            'title' => $this->parser->getTitle($frontmatter, $parsed['content']),
                            'date' => $this->parser->getDate($frontmatter, $this->fileHandler->getModificationTime($path)),
                            'author' => $frontmatter['author'] ?? '',
                            'tags' => $frontmatter['tags'] ?? [],
                            'banner' => $frontmatter['banner'] ?? '',
                            'content' => $parsed['content'],
                        ];

                        $this->cache->set('blog', $cacheKey, json_encode($post));
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
                    $relatedPath = 'content/blog/' . $file;
                    $relatedParsed = $this->parser->parseFile($this->fileHandler, $relatedPath);
                    $relatedFrontmatter = $relatedParsed['frontmatter'];
                    $fileSlug = $this->parser->getSlug($relatedFrontmatter, $file);
                    if ($fileSlug === $slug) {
                        continue;
                    }
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
                            'slug' => $this->parser->getSlug($relatedFrontmatter, $file),
                            'title' => $this->parser->getTitle($relatedFrontmatter, $relatedParsed['content']),
                            'date' => $this->parser->getDate($relatedFrontmatter, $this->fileHandler->getModificationTime($relatedPath)),
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
