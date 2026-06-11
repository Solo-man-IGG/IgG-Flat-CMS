<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

class Counter
{
    private FileHandler $fileHandler;
    private string $counterDir;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        $this->counterDir = 'content/counters';

        try {
            $this->fileHandler->createDirectory($this->counterDir);
        } catch (\Exception $e) {
            error_log('Counter::init error: ' . $e->getMessage());
        }
    }

    public function increment(string $type, string $slug): int
    {
        $filename = $this->counterDir . '/' . $type . '-' . $slug . '.json';
        $views = 0;

        try {
            $dir = dirname($filename);
            if (!$this->fileHandler->exists($dir)) {
                $this->fileHandler->createDirectory($dir);
            }

            $fullPath = $this->resolvePath($filename);

            $fh = fopen($fullPath, 'c+');
            if (!$fh) {
                throw new \RuntimeException('Cannot open counter file');
            }

            if (!flock($fh, LOCK_EX)) {
                fclose($fh);
                throw new \RuntimeException('Cannot acquire lock');
            }

            // Read current value
            $size = filesize($fullPath);
            if ($size > 0) {
                $content = fread($fh, $size);
                $data = json_decode($content, true);
                $views = ($data['views'] ?? 0) + 1;
            } else {
                $views = 1;
            }

            // Write updated value under the same exclusive lock
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode(['views' => $views], JSON_PRETTY_PRINT));
            fflush($fh);

            flock($fh, LOCK_UN);
            fclose($fh);
        } catch (\Exception $e) {
            error_log('Counter::increment error: ' . $e->getMessage());
        }

        return $views;
    }

    /**
     * Resolve a relative path to an absolute one, matching FileHandler's convention
     */
    private function resolvePath(string $path): string
    {
        $basePath = realpath(__DIR__ . '/..');
        return $basePath . '/' . ltrim($path, '/');
    }

    public function get(string $type, string $slug): int
    {
        $filename = $this->counterDir . '/' . $type . '-' . $slug . '.json';

        try {
            if ($this->fileHandler->exists($filename)) {
                $content = $this->fileHandler->read($filename);
                $data = json_decode($content, true);
                return $data['views'] ?? 0;
            }
        } catch (\Exception $e) {
            error_log('Counter::get error: ' . $e->getMessage());
        }

        return 0;
    }
}
