<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

class Logger
{
    private string $logDir;
    private FileHandler $fileHandler;

    // Log types
    public const TYPE_ACCESS   = 'access';
    public const TYPE_LOGIN    = 'login';
    public const TYPE_ATTACK   = 'attack';
    public const TYPE_BOT      = 'bot';

    // Known bot patterns
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
        'facebookexternalhit', 'baiduspider', 'yandex', 'sogou',
        'exabot', 'ia_archiver', 'semrush', 'ahrefs', 'mj12bot',
        'dotbot', 'petalbot', 'bytespider', 'gptbot', 'chatgpt',
        'ccbot', 'claudebot', 'anthropic', 'googlebot', 'bingbot',
        'applebot', 'discordbot', 'telegrambot', 'whatsapp',
    ];

    // Attack patterns
    private const ATTACK_PATTERNS = [
        '\.\./', '\.\.\\', 'etc/passwd', 'etc/shadow',
        'union.*select', 'select.*from', 'insert.*into',
        'drop.*table', 'or.*1\s*=\s*1', 'and.*1\s*=\s*1',
        '<script', 'javascript:', 'onerror=', 'onload=',
        'eval\(', 'exec\(', 'system\(', 'passthru\(',
        'base64_decode', 'cmd\.exe', '/bin/sh',
        '\.env', 'wp-admin', 'wp-login', 'xmlrpc\.php',
        'phpmyadmin', 'admin\.php', '\.git/',
        'actuator', 'swagger', 'api/v1/admin',
        'UNION', 'SELECT', 'FROM.*WHERE',
    ];

    public function __construct(FileHandler $fileHandler, string $logDir = 'logs')
    {
        $this->fileHandler = $fileHandler;
        $this->logDir = $logDir;

        try {
            $this->fileHandler->createDirectory($logDir);
        } catch (\Exception $e) {
            error_log('Logger init error: ' . $e->getMessage());
        }
    }

    /**
     * Log a request
     */
    public function logRequest(string $ip, string $method, string $uri, int $statusCode, string $userAgent = ''): void
    {
        $type = $this->classifyRequest($uri, $userAgent, $statusCode);
        $this->writeLog($type, $ip, $method, $uri, $statusCode, $userAgent);
    }

    /**
     * Log a failed login attempt
     */
    public function logLoginFailure(string $ip, string $username, string $userAgent = ''): void
    {
        $line = $this->formatLine(self::TYPE_LOGIN, $ip, 'POST', '/admin/login', 401, $userAgent,
            "user={$username}");
        $this->appendLog(self::TYPE_LOGIN, $line);
    }

    /**
     * Log a successful login
     */
    public function logLoginSuccess(string $ip, string $username): void
    {
        $line = $this->formatLine(self::TYPE_LOGIN, $ip, 'POST', '/admin/login', 200, '',
            "user={$username} status=success");
        $this->appendLog(self::TYPE_LOGIN, $line);
    }

    /**
     * Classify request type
     */
    private function classifyRequest(string $uri, string $userAgent, int $statusCode): string
    {
        $ua = strtolower($userAgent);

        // Check for attack patterns
        foreach (self::ATTACK_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $uri) || preg_match('/' . $pattern . '/i', $userAgent)) {
                return self::TYPE_ATTACK;
            }
        }

        // 404 on sensitive paths = probe
        if ($statusCode === 404) {
            $probePatterns = ['wp-admin', 'wp-login', 'xmlrpc', 'phpmyadmin', '.env', '.git', 'admin.php'];
            foreach ($probePatterns as $p) {
                if (stripos($uri, $p) !== false) {
                    return self::TYPE_ATTACK;
                }
            }
        }

        // Check for bots
        foreach (self::BOT_PATTERNS as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return self::TYPE_BOT;
            }
        }

        return self::TYPE_ACCESS;
    }

    /**
     * Format a log line
     */
    private function formatLine(string $type, string $ip, string $method, string $uri, int $statusCode, string $userAgent, string $extra = ''): string
    {
        $time = date('Y-m-d H:i:s');
        $line = "[{$time}] [{$type}] {$ip} {$method} {$uri} {$statusCode}";
        if ($userAgent) {
            $line .= ' ua="' . substr($userAgent, 0, 120) . '"';
        }
        if ($extra) {
            $line .= ' ' . $extra;
        }
        return $line . "\n";
    }

    /**
     * Write to the appropriate log file
     */
    private function writeLog(string $type, string $ip, string $method, string $uri, int $statusCode, string $userAgent): void
    {
        $line = $this->formatLine($type, $ip, $method, $uri, $statusCode, $userAgent);
        $this->appendLog($type, $line);
    }

    /**
     * Append a line to a log file
     */
    private function appendLog(string $type, string $line): void
    {
        try {
            $filename = $this->logDir . '/' . $type . '.log';
            $fullPath = realpath(__DIR__ . '/../' . $filename);
            if ($fullPath === false) {
                $fullPath = __DIR__ . '/../' . $filename;
            }
            file_put_contents($fullPath, $line, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            error_log('Logger write error: ' . $e->getMessage());
        }
    }
}
