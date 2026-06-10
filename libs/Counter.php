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
            if ($this->fileHandler->exists($filename)) {
                $content = $this->fileHandler->read($filename);
                $data = json_decode($content, true);
                $views = ($data['views'] ?? 0) + 1;
            } else {
                $views = 1;
            }

            $this->fileHandler->write($filename, json_encode(['views' => $views], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            error_log('Counter::increment error: ' . $e->getMessage());
        }

        return $views;
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
