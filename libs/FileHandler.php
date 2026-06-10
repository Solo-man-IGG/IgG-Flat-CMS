<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

class FileHandler
{
    private string $basePath;
    private array $allowedDirectories;
    
    public function __construct(string $basePath = __DIR__ . '/..')
    {
        $this->basePath = realpath($basePath) ?: $basePath;
        $this->allowedDirectories = [
            'content',
            'cache',
            'logs',
            'templates',
            'uploads'
        ];
    }
    
    /**
     * Validate and sanitize a file path to prevent path traversal attacks
     */
    private function validatePath(string $path): string
    {
        // Remove null bytes
        $path = str_replace("\0", '', $path);
        
        // Normalize path separators
        $path = str_replace('\\', '/', $path);
        
        // Remove any attempt at parent directory traversal
        $path = str_replace('..', '', $path);
        
        // Remove leading slashes to prevent absolute paths
        $path = ltrim($path, '/');
        
        // Validate that the path only contains safe characters
        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $path)) {
            throw new \InvalidArgumentException("Invalid file path: contains unsafe characters");
        }
        
        // Construct full path
        $fullPath = $this->basePath . '/' . $path;
        
        // Resolve to real path to prevent symlink attacks
        $realPath = realpath($fullPath);
        
        // If file doesn't exist yet, validate the directory
        if ($realPath === false) {
            $directory = dirname($fullPath);
            $realDir = realpath($directory);
            
            if ($realDir === false) {
                throw new \InvalidArgumentException("Invalid directory: {$directory}");
            }
            
            // Ensure the resolved directory is within base path
            if (strpos($realDir, $this->basePath) !== 0) {
                throw new \InvalidArgumentException("Path traversal attempt detected");
            }
            
            return $fullPath;
        }
        
        // Ensure the resolved path is within base path
        if (strpos($realPath, $this->basePath) !== 0) {
            throw new \InvalidArgumentException("Path traversal attempt detected");
        }
        
        return $realPath;
    }
    
    /**
     * Check if a path is within an allowed directory
     */
    private function isAllowedDirectory(string $path): bool
    {
        $pathParts = explode('/', $path);
        if (empty($pathParts)) {
            return false;
        }
        
        $firstDir = $pathParts[0];
        return in_array($firstDir, $this->allowedDirectories, true);
    }
    
    /**
     * Read file contents safely
     */
    public function read(string $path): string
    {
        // Validate directory access
        if (!$this->isAllowedDirectory($path)) {
            throw new \InvalidArgumentException("Access denied to directory: {$path}");
        }
        
        $fullPath = $this->validatePath($path);
        
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        if (!is_readable($fullPath)) {
            throw new \RuntimeException("File not readable: {$path}");
        }
        
        $content = file_get_contents($fullPath);
        
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$path}");
        }
        
        return $content;
    }
    
    /**
     * Write file contents safely
     */
    public function write(string $path, string $content): void
    {
        // Validate directory access
        if (!$this->isAllowedDirectory($path)) {
            throw new \InvalidArgumentException("Access denied to directory: {$path}");
        }
        
        $fullPath = $this->validatePath($path);
        
        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException("Failed to create directory: {$directory}");
            }
        }
        
        $result = file_put_contents($fullPath, $content, LOCK_EX);
        
        if ($result === false) {
            throw new \RuntimeException("Failed to write file: {$path}");
        }
    }
    
    /**
     * Check if a file exists
     */
    public function exists(string $path): bool
    {
        try {
            $fullPath = $this->validatePath($path);
            return file_exists($fullPath);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
    
    /**
     * Delete a file safely
     */
    public function delete(string $path): void
    {
        // Validate directory access
        if (!$this->isAllowedDirectory($path)) {
            throw new \InvalidArgumentException("Access denied to directory: {$path}");
        }
        
        $fullPath = $this->validatePath($path);
        
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        if (!unlink($fullPath)) {
            throw new \RuntimeException("Failed to delete file: {$path}");
        }
    }
    
    /**
     * Get file modification time
     */
    public function getModificationTime(string $path): int
    {
        // Validate directory access
        if (!$this->isAllowedDirectory($path)) {
            throw new \InvalidArgumentException("Access denied to directory: {$path}");
        }
        
        $fullPath = $this->validatePath($path);
        
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        $mtime = filemtime($fullPath);
        
        if ($mtime === false) {
            throw new \RuntimeException("Failed to get modification time: {$path}");
        }
        
        return $mtime;
    }
    
    /**
     * List files in a directory
     */
    public function listFiles(string $directory, string $extension = null): array
    {
        // Validate directory access
        if (!$this->isAllowedDirectory($directory)) {
            throw new \InvalidArgumentException("Access denied to directory: {$directory}");
        }
        
        $fullPath = $this->validatePath($directory);
        
        if (!is_dir($fullPath)) {
            throw new \RuntimeException("Directory not found: {$directory}");
        }
        
        if (!is_readable($fullPath)) {
            throw new \RuntimeException("Directory not readable: {$directory}");
        }
        
        $files = [];
        
        $items = scandir($fullPath);
        if ($items === false) {
            throw new \RuntimeException("Failed to scan directory: {$directory}");
        }
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $itemPath = $fullPath . '/' . $item;
            
            if (is_file($itemPath)) {
                if ($extension === null || pathinfo($item, PATHINFO_EXTENSION) === $extension) {
                    $files[] = $item;
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Create a directory safely
     */
    public function createDirectory(string $path): void
    {
        // Validate directory access
        if (!$this->isAllowedDirectory($path)) {
            throw new \InvalidArgumentException("Access denied to directory: {$path}");
        }
        
        $fullPath = $this->validatePath($path);
        
        if (is_dir($fullPath)) {
            return; // Directory already exists
        }
        
        if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
            throw new \RuntimeException("Failed to create directory: {$path}");
        }
    }
}
