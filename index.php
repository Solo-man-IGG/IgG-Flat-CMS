<?php

define('CMS_ENTRY', true);

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

// Auto-install Composer dependencies if missing
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $output = [];
    $returnCode = 0;
    exec('composer install --no-dev --no-interaction --no-ansi 2>&1', $output, $returnCode);
    if ($returnCode !== 0 || !file_exists(__DIR__ . '/vendor/autoload.php')) {
        http_response_code(500);
        echo '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安裝相依套件</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 600px; margin: 4rem auto; padding: 0 1rem; line-height: 1.6; }
        h1 { color: #1f2937; }
        pre { background: #f1f5f9; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; }
        code { background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-size: 0.9em; }
        .error { color: #dc2626; background: #fee2e2; padding: 0.75rem 1rem; border-radius: 0.5rem; margin: 1rem 0; }
    </style>
</head>
<body>
    <h1>正在安裝相依套件…</h1>
    <p>系統嘗試自動安裝失敗，請在終端機執行：</p>
    <pre>composer install --no-dev</pre>
    <p>完成後重新整理頁面即可。</p>';
        if (!empty($output)) {
            echo '<div class="error"><strong>錯誤訊息：</strong><br>' . htmlspecialchars(implode("\n", $output), ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo '</body>
</html>';
        exit;
    }
}
require_once __DIR__ . '/vendor/autoload.php';

use CMS\Router;
use CMS\FileHandler;
use CMS\Logger;

// Secure session configuration
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Taipei');

// Initialize FileHandler
$fileHandler = new FileHandler(__DIR__);

// Initialize Logger
$logger = new Logger($fileHandler);

// Initialize Router
$router = new Router($fileHandler);

// Parse the request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Remove query string from URI
$requestUri = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Route the request
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$statusCode = 200;

try {
    $router->route($requestUri);
    $statusCode = http_response_code();
} catch (\Exception $e) {
    // Log error
    error_log('CMS Error: ' . $e->getMessage());
    $statusCode = 500;
    
    // Display error page
    http_response_code(500);
    echo '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系統錯誤</title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
</head>
<body>
    <main>
        <h1>系統錯誤</h1>
        <p>抱歉，系統發生錯誤。請稍後再試。</p>
        <p><a href="/">返回首頁</a></p>
    </main>
</body>
</html>';
}

// Log the request
$logger->logRequest($ip, $method, $requestUri, $statusCode, $userAgent);
