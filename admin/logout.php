<?php

defined("CMS_ENTRY") or die("Direct access not allowed.");

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CMS\Auth;
use CMS\FileHandler;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize Auth
$fileHandler = new FileHandler(__DIR__ . '/..');
$auth = new Auth($fileHandler);

// Logout
$auth->logout();

// Redirect to login
header('Location: /admin/login');
exit;
