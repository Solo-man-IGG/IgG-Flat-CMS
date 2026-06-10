<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

class Cache
{
    private FileHandler $fileHandler;
    private string $cacheDir;
    
    public function __construct(FileHandler $fileHandler, string $cacheDir = 'cache/pages')
    {
        $this->fileHandler = $fileHandler;
        $this->cacheDir = $cacheDir;
        
        // Ensure cache directory exists
        $this->fileHandler->createDirectory($cacheDir);
    }
    
    /**
     * Generate cache key for a content item
     * 
     * @param string $type Content type (blog, products, pages)
     * @param string $slug Content slug
     * @return string Cache key
     */
    private function getCacheKey(string $type, string $slug): string
    {
        return $this->cacheDir . '/' . $type . '_' . $slug . '.html';
    }
    
    /**
     * Check if cached version is valid (exists and source not modified)
     * 
     * @param string $type Content type
     * @param string $slug Content slug
     * @param string $sourcePath Path to source file
     * @return bool True if cache is valid
     */
    public function isValid(string $type, string $slug, string $sourcePath): bool
    {
        $cacheKey = $this->getCacheKey($type, $slug);
        
        // Check if cache file exists
        if (!$this->fileHandler->exists($cacheKey)) {
            return false;
        }
        
        try {
            // Get modification times
            $cacheMtime = $this->fileHandler->getModificationTime($cacheKey);
            $sourceMtime = $this->fileHandler->getModificationTime($sourcePath);
            
            // Cache is valid if it's newer than source
            return $cacheMtime >= $sourceMtime;
        } catch (\Exception $e) {
            // If we can't check modification times, assume cache is invalid
            return false;
        }
    }
    
    /**
     * Get cached content
     * 
     * @param string $type Content type
     * @param string $slug Content slug
     * @return string|null Cached HTML content or null if not found
     */
    public function get(string $type, string $slug): ?string
    {
        $cacheKey = $this->getCacheKey($type, $slug);
        
        try {
            return $this->fileHandler->read($cacheKey);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Set cached content
     * 
     * @param string $type Content type
     * @param string $slug Content slug
     * @param string $content HTML content to cache
     * @return bool True if successful
     */
    public function set(string $type, string $slug, string $content): bool
    {
        $cacheKey = $this->getCacheKey($type, $slug);
        
        try {
            $this->fileHandler->write($cacheKey, $content);
            return true;
        } catch (\Exception $e) {
            error_log('Cache write error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear cache for a specific item
     * 
     * @param string $type Content type
     * @param string $slug Content slug
     * @return bool True if successful
     */
    public function clear(string $type, string $slug): bool
    {
        $cacheKey = $this->getCacheKey($type, $slug);
        
        try {
            if ($this->fileHandler->exists($cacheKey)) {
                $this->fileHandler->delete($cacheKey);
            }
            return true;
        } catch (\Exception $e) {
            error_log('Cache clear error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all cache for a specific type
     * 
     * @param string $type Content type
     * @return bool True if successful
     */
    public function clearType(string $type): bool
    {
        try {
            $files = $this->fileHandler->listFiles($this->cacheDir, 'html');
            
            foreach ($files as $file) {
                // Check if file matches the type pattern
                if (strpos($file, $type . '_') === 0) {
                    $this->fileHandler->delete($this->cacheDir . '/' . $file);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Cache clear type error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all cache
     * 
     * @return bool True if successful
     */
    public function clearAll(): bool
    {
        try {
            $files = $this->fileHandler->listFiles($this->cacheDir, 'html');
            
            foreach ($files as $file) {
                $this->fileHandler->delete($this->cacheDir . '/' . $file);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Cache clear all error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function getStats(): array
    {
        try {
            $files = $this->fileHandler->listFiles($this->cacheDir, 'html');
            $totalSize = 0;
            
            foreach ($files as $file) {
                $path = $this->cacheDir . '/' . $file;
                $totalSize += filesize($path);
            }
            
            return [
                'count' => count($files),
                'size' => $totalSize,
                'size_human' => $this->formatBytes($totalSize)
            ];
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'size' => 0,
                'size_human' => '0 B'
            ];
        }
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
