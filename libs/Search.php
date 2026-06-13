<?php

namespace CMS;

class Search
{
    private FileHandler $fileHandler;
    private MarkdownParser $parser;
    private string $indexFile = 'cache/search_index.json';

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        $this->parser = new MarkdownParser();
    }

    public function rebuildIndex(): void
    {
        $index = [];

        $index = array_merge($index, $this->indexBlog());
        $index = array_merge($index, $this->indexProducts());
        $index = array_merge($index, $this->indexPages());

        $this->fileHandler->write($this->indexFile, json_encode($index, JSON_UNESCAPED_UNICODE));
    }

    private function indexBlog(): array
    {
        $items = [];
        try {
            $files = $this->fileHandler->listFiles('content/blog', 'md');
            foreach ($files as $file) {
                $path = 'content/blog/' . $file;
                $parsed = $this->parser->parseFile($this->fileHandler, $path);
                $fm = $parsed['frontmatter'];

                if (!($fm['published'] ?? true)) {
                    continue;
                }

                $slug = $this->parser->getSlug($fm, $file);
                $plainText = strip_tags($parsed['content']);

                $items[] = [
                    'type' => 'blog',
                    'slug' => $slug,
                    'title' => $this->parser->getTitle($fm, $parsed['content']),
                    'subtitle' => $fm['subtitle'] ?? '',
                    'excerpt' => $this->parser->getExcerpt($parsed['content']),
                    'content' => mb_substr($plainText, 0, 1000),
                    'date' => $this->parser->getDate($fm, $this->fileHandler->getModificationTime($path)),
                ];
            }
        } catch (\Exception $e) {
            error_log('Search index blog error: ' . $e->getMessage());
        }
        return $items;
    }

    private function indexProducts(): array
    {
        $items = [];
        try {
            $files = $this->fileHandler->listFiles('content/products', 'md');
            foreach ($files as $file) {
                $path = 'content/products/' . $file;
                $parsed = $this->parser->parseFile($this->fileHandler, $path);
                $fm = $parsed['frontmatter'];
                $slug = $this->parser->getSlug($fm, $file);
                $plainText = strip_tags($parsed['content']);

                $items[] = [
                    'type' => 'product',
                    'slug' => $slug,
                    'title' => $this->parser->getTitle($fm, $parsed['content']),
                    'subtitle' => $fm['subtitle'] ?? '',
                    'excerpt' => $this->parser->getExcerpt($parsed['content']),
                    'content' => mb_substr($plainText, 0, 1000),
                    'date' => $this->parser->getDate($fm, $this->fileHandler->getModificationTime($path)),
                ];
            }
        } catch (\Exception $e) {
            error_log('Search index products error: ' . $e->getMessage());
        }
        return $items;
    }

    private function indexPages(): array
    {
        $items = [];
        try {
            $files = $this->fileHandler->listFiles('content/pages', 'md');
            foreach ($files as $file) {
                $path = 'content/pages/' . $file;
                $parsed = $this->parser->parseFile($this->fileHandler, $path);
                $fm = $parsed['frontmatter'];
                $slug = $this->parser->getSlug($fm, $file);
                $plainText = strip_tags($parsed['content']);

                $items[] = [
                    'type' => 'page',
                    'slug' => $slug,
                    'title' => $this->parser->getTitle($fm, $parsed['content']),
                    'subtitle' => $fm['subtitle'] ?? '',
                    'excerpt' => $this->parser->getExcerpt($parsed['content']),
                    'content' => mb_substr($plainText, 0, 1000),
                    'date' => $this->parser->getDate($fm, $this->fileHandler->getModificationTime($path)),
                ];
            }
        } catch (\Exception $e) {
            error_log('Search index pages error: ' . $e->getMessage());
        }
        return $items;
    }

    public function search(string $query): array
    {
        if (empty(trim($query))) {
            return [];
        }

        $index = $this->loadIndex();
        if (empty($index)) {
            return [];
        }

        $keywords = preg_split('/\s+/', trim($query));
        $results = [];
        $seen = [];

        foreach ($index as $item) {
            $score = 0;
            $haystack = mb_strtolower($item['title'] . ' ' . $item['subtitle'] . ' ' . $item['excerpt'] . ' ' . $item['content']);

            foreach ($keywords as $kw) {
                $kw = mb_strtolower($kw);
                if ($kw === '') continue;

                if (mb_strpos(mb_strtolower($item['title']), $kw) !== false) {
                    $score += 10;
                }
                if (mb_strpos(mb_strtolower($item['subtitle']), $kw) !== false) {
                    $score += 5;
                }
                if (mb_strpos($haystack, $kw) !== false) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $key = $item['type'] . '/' . $item['slug'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $item['score'] = $score;
                    $results[] = $item;
                }
            }
        }

        usort($results, function ($a, $b) {
            return $b['score'] - $a['score'];
        });

        return $results;
    }

    private function loadIndex(): array
    {
        try {
            if (!$this->fileHandler->exists($this->indexFile)) {
                $this->rebuildIndex();
            }
            $json = $this->fileHandler->read($this->indexFile);
            return json_decode($json, true) ?? [];
        } catch (\Exception $e) {
            error_log('Search load index error: ' . $e->getMessage());
            return [];
        }
    }
}
