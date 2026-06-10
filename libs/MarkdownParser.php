<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

use Parsedown;
use Symfony\Component\Yaml\Yaml;

class MarkdownParser
{
    private Parsedown $parsedown;
    
    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->parsedown->setSafeMode(false);
    }
    
    /**
     * Parse a markdown file with frontmatter
     * 
     * @param string $content The raw markdown content
     * @return array ['frontmatter' => array, 'content' => string (HTML)]
     */
    public function parse(string $content): array
    {
        $result = [
            'frontmatter' => [],
            'content' => ''
        ];
        
        // Check if content has frontmatter (starts with ---)
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            // Parse YAML frontmatter
            $frontmatterContent = $matches[1];
            $markdownContent = $matches[2];
            
            try {
                $result['frontmatter'] = Yaml::parse($frontmatterContent);
            } catch (\Exception $e) {
                // If YAML parsing fails, treat entire content as markdown
                $result['frontmatter'] = [];
                $markdownContent = $content;
            }
        } else {
            // No frontmatter, treat entire content as markdown
            $markdownContent = $content;
        }
        
        // Parse markdown to HTML
        $result['content'] = $this->parsedown->text($markdownContent);
        
        return $result;
    }
    
    /**
     * Parse a markdown file from path
     * 
     * @param FileHandler $fileHandler
     * @param string $path Path to the markdown file
     * @return array ['frontmatter' => array, 'content' => string (HTML)]
     */
    public function parseFile(FileHandler $fileHandler, string $path): array
    {
        $content = $fileHandler->read($path);
        return $this->parse($content);
    }
    
    /**
     * Extract slug from frontmatter or filename
     * 
     * @param array $frontmatter
     * @param string $filename
     * @return string
     */
    public function getSlug(array $frontmatter, string $filename): string
    {
        // If slug is defined in frontmatter, use it
        if (isset($frontmatter['slug']) && is_string($frontmatter['slug'])) {
            return $this->sanitizeSlug($frontmatter['slug']);
        }
        
        // Otherwise, use filename without extension
        $slug = pathinfo($filename, PATHINFO_FILENAME);
        return $this->sanitizeSlug($slug);
    }
    
    /**
     * Sanitize slug to only contain safe characters
     * 
     * @param string $slug
     * @return string
     */
    private function sanitizeSlug(string $slug): string
    {
        // Convert to lowercase
        $slug = strtolower($slug);
        
        // Replace spaces with hyphens
        $slug = preg_replace('/\s+/', '-', $slug);
        
        // Remove any characters that are not a-z, 0-9, or hyphen
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        
        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Get title from frontmatter or first heading
     * 
     * @param array $frontmatter
     * @param string $htmlContent
     * @return string
     */
    public function getTitle(array $frontmatter, string $htmlContent): string
    {
        // If title is defined in frontmatter, use it
        if (isset($frontmatter['title']) && is_string($frontmatter['title'])) {
            return $frontmatter['title'];
        }
        
        // Otherwise, extract from first h1 tag
        if (preg_match('/<h1>(.*?)<\/h1>/i', $htmlContent, $matches)) {
            return strip_tags($matches[1]);
        }
        
        return 'Untitled';
    }
    
    /**
     * Get date from frontmatter or file modification time
     * 
     * @param array $frontmatter
     * @param int|null $filemtime
     * @return string
     */
    public function getDate(array $frontmatter, ?int $filemtime = null): string
    {
        // If date is defined in frontmatter, use it
        if (isset($frontmatter['date']) && is_string($frontmatter['date'])) {
            return $frontmatter['date'];
        }
        
        // Otherwise, use file modification time
        if ($filemtime !== null) {
            return date('Y-m-d', $filemtime);
        }
        
        return date('Y-m-d');
    }
    
    /**
     * Get excerpt from content
     * 
     * @param string $htmlContent
     * @param int $length
     * @return string
     */
    public function getExcerpt(string $htmlContent, int $length = 200): string
    {
        // Strip HTML tags
        $text = strip_tags($htmlContent);
        
        // Trim whitespace
        $text = trim($text);
        
        // If text is shorter than length, return as is
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        // Truncate to length (multi-byte safe)
        $excerpt = mb_substr($text, 0, $length);
        
        // Find last space to avoid cutting words (multi-byte safe)
        $lastSpace = mb_strrpos($excerpt, ' ');
        if ($lastSpace !== false) {
            $excerpt = mb_substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
    
    /**
     * Get metadata from frontmatter with defaults
     * 
     * @param array $frontmatter
     * @return array
     */
    public function getMetadata(array $frontmatter): array
    {
        return [
            'title' => $frontmatter['title'] ?? '',
            'slug' => $frontmatter['slug'] ?? '',
            'date' => $frontmatter['date'] ?? '',
            'author' => $frontmatter['author'] ?? '',
            'tags' => $frontmatter['tags'] ?? [],
            'category' => $frontmatter['category'] ?? '',
            'published' => $frontmatter['published'] ?? true,
            'featured' => $frontmatter['featured'] ?? false,
            'description' => $frontmatter['description'] ?? '',
            'image' => $frontmatter['image'] ?? '',
        ];
    }
}
