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
            return ['site_title' => 'My Site'];
        }
    }

    protected function notFound(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>頁面不存在</title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
</head>
<body>
    <main>
        <h1>頁面不存在</h1>
        <p>抱歉，您尋找的頁面不存在。</p>
        <p><a href="/">返回首頁</a></p>
    </main>
</body>
</html>';
    }
}
