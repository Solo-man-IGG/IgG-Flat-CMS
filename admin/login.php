<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../libs/functions.php';

use CMS\Auth;
use CMS\Lang;
use CMS\FileHandler;
use CMS\Logger;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize components
Lang::init(__DIR__ . '/../data');
$fileHandler = new FileHandler(__DIR__ . '/..');
$logger = new Logger($fileHandler);
$auth = new Auth($fileHandler, 3600, $logger);

// Redirect to dashboard if already logged in
if ($auth->isLoggedIn()) {
    header('Location: /admin/dashboard');
    exit;
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!$auth->validateCsrfToken($csrfToken)) {
        $error = __('admin.login.error.csrf');
    } else {
        // Attempt login
        if ($auth->login($username, $password)) {
            header('Location: /admin/dashboard');
            exit;
        } else {
            $error = __('admin.login.error.invalid_credentials');
        }
    }
    
    // Check if rate limited and show appropriate message
    if (empty($error) && method_exists($auth, 'isRateLimited') && $auth->isRateLimited()) {
        $error = __('admin.login.error.rate_limited', 15);
    }
}

$csrfField = $auth->getCsrfField();
?>
<!DOCTYPE html>
<html lang="<?php echo __('lang.attr'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin.login.page_title'); ?></title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f3f4f6;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-container h1 {
            text-align: center;
            color: #2563eb;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        button[type="submit"] {
            width: 100%;
            background-color: #2563eb;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #1e40af;
        }
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border: 1px solid #ef4444;
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        .back-link a {
            color: #2563eb;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><?php echo __('admin.login.heading'); ?></h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo $csrfField; ?>
            
            <div class="form-group">
                <label for="username"><?php echo __('admin.login.form.username'); ?></label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password"><?php echo __('admin.login.form.password'); ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit"><?php echo __('admin.login.form.submit'); ?></button>
        </form>
        
        <div class="back-link">
            <a href="/"><?php echo __('admin.login.back_home'); ?></a>
        </div>
    </div>
</body>
</html>
