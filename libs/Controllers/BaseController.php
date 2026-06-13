<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS\Controllers;

use CMS\Counter;
use CMS\FileHandler;

abstract class BaseController
{
    protected FileHandler $fileHandler;
    protected Counter $counter;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        $this->counter = new Counter($fileHandler);
    }

    protected function loadSettings(): array
    {
        try {
            $settingsJson = $this->fileHandler->read('content/config/settings.json');
            return json_decode($settingsJson, true) ?? [];
        } catch (\Exception $e) {
            return ['site_title' => 'My Site', 'site_slogan' => ''];
        }
    }

    public function notFound(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html>
<html lang="' . __('lang.attr') . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . __('base.error_404.title') . '</title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
</head>
<body>
    <main>
        <h1>' . __('base.error_404.heading') . '</h1>
        <p>' . __('base.error_404.message') . '</p>
        <p><a href="/">' . __('base.error_404.back_home') . '</a></p>
    </main>
</body>
</html>';
    }
}
