<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

class Auth
{
    private const USERS_FILE = 'content/config/users.json';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_MINUTES = 15;
    private const ATTEMPTS_DIR = 'logs/login_attempts';
    
    private FileHandler $fileHandler;
    private int $sessionTimeout = 3600; // 1 hour default
    private ?Logger $logger;
    
    public function __construct(FileHandler $fileHandler, int $sessionTimeout = 3600, ?Logger $logger = null)
    {
        $this->fileHandler = $fileHandler;
        $this->sessionTimeout = $sessionTimeout;
        $this->logger = $logger;
        
        // Secure session configuration
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }
    
    /**
     * Get path to the attempts file for a given IP
     */
    private function getAttemptsFile(string $ip): string
    {
        $hash = hash('sha256', $ip);
        return self::ATTEMPTS_DIR . '/' . $hash . '.json';
    }

    /**
     * Read stored attempts for an IP
     */
    private function readAttempts(string $ip): array
    {
        $file = $this->getAttemptsFile($ip);
        try {
            $content = $this->fileHandler->read($file);
            $attempts = json_decode($content, true);
            return is_array($attempts) ? $attempts : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Write attempts for an IP, pruning entries outside the lockout window
     */
    private function writeAttempts(string $ip, array $attempts): void
    {
        $cutoff = time() - self::LOGIN_LOCKOUT_MINUTES * 60;
        $attempts = array_values(array_filter($attempts, function($t) use ($cutoff) {
            return $t > $cutoff;
        }));

        if (empty($attempts)) {
            $this->deleteAttempts($ip);
            return;
        }

        $file = $this->getAttemptsFile($ip);
        $this->fileHandler->write($file, json_encode($attempts));
    }

    /**
     * Delete the attempts file for an IP
     */
    private function deleteAttempts(string $ip): void
    {
        $file = $this->getAttemptsFile($ip);
        try {
            if ($this->fileHandler->exists($file)) {
                $this->fileHandler->delete($file);
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }

    /**
     * Check if login is rate limited (file-based, persists across session resets)
     */
    private function isRateLimited(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attempts = $this->readAttempts($ip);

        if (count($attempts) < self::MAX_LOGIN_ATTEMPTS) {
            return false;
        }

        // Check if lockout period has passed
        $firstAttempt = min($attempts);
        if (time() - $firstAttempt > self::LOGIN_LOCKOUT_MINUTES * 60) {
            $this->deleteAttempts($ip);
            return false;
        }

        return true;
    }
    
    /**
     * Record a login attempt
     */
    private function recordLoginAttempt(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attempts = $this->readAttempts($ip);
        $attempts[] = time();
        $this->writeAttempts($ip, $attempts);
    }
    
    /**
     * Clear login attempts on successful login
     */
    private function clearLoginAttempts(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->deleteAttempts($ip);
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (!isset($_SESSION['login_time'])) {
            return false;
        }
        
        $loginTime = $_SESSION['login_time'];
        if (time() - $loginTime > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        // Check absolute timeout (4 hours max regardless of activity)
        if (isset($_SESSION['session_start_time'])) {
            $absoluteTimeout = 4 * 3600;
            if (time() - $_SESSION['session_start_time'] > $absoluteTimeout) {
                $this->logout();
                return false;
            }
        }
        
        // Refresh login time
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    /**
     * Attempt to log in a user
     * 
     * @param string $username
     * @param string $password
     * @return bool True if login successful
     */
    public function login(string $username, string $password): bool
    {
        // Check rate limiting
        if ($this->isRateLimited()) {
            $waitMinutes = self::LOGIN_LOCKOUT_MINUTES;
            error_log("Login rate limited for IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return false;
        }
        
        try {
            // Load users from JSON
            $usersJson = $this->fileHandler->read(self::USERS_FILE);
            $users = json_decode($usersJson, true);
            
            if (!is_array($users)) {
                $this->recordLoginAttempt();
                return false;
            }
            
            // Find user by username
            foreach ($users as $user) {
                if (isset($user['username']) && $user['username'] === $username) {
                    // Verify password
                    if (isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                        // Clear login attempts on success
                        $this->clearLoginAttempts();
                        
                        // Log successful login
                        if ($this->logger) {
                            $this->logger->logLoginSuccess(
                                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                                $username
                            );
                        }
                        
                        // Set session variables
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        $_SESSION['session_start_time'] = time();
                        $_SESSION['username'] = $username;
                        $_SESSION['user_id'] = $user['id'] ?? $username;
                        
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);
                        
                        return true;
                    }
                }
            }
            
            $this->recordLoginAttempt();
            
            // Log failed login
            if ($this->logger) {
                $this->logger->logLoginFailure(
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    $username,
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
            }
            
            return false;
        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log out the current user
     * 
     * @return void
     */
    public function logout(): void
    {
        // Unset session variables
        unset($_SESSION['logged_in']);
        unset($_SESSION['login_time']);
        unset($_SESSION['username']);
        unset($_SESSION['user_id']);
        unset($_SESSION['csrf_token']);
        
        // Destroy session
        session_destroy();
        
        // Start new session for CSRF token generation
        session_start();
    }
    
    /**
     * Get current logged in username
     * 
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public function validateCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     * 
     * @return void
     */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /admin/login');
            exit;
        }
    }
    
    /**
     * Require CSRF token validation
     * 
     * @param string $token
     * @return void
     */
    public function requireCsrf(string $token): void
    {
        if (!$this->validateCsrfToken($token)) {
            http_response_code(403);
            echo '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF 驗證失敗</title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
</head>
<body>
    <main>
        <h1>CSRF 驗證失敗</h1>
        <p>請求驗證失敗，請重新整理頁面後再試。</p>
        <p><a href="/admin/dashboard">返回後台</a></p>
    </main>
</body>
</html>';
            exit;
        }
    }
    
    /**
     * Hash a password
     * 
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehash
     * 
     * @param string $hash
     * @return bool
     */
    public function passwordNeedsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Get CSRF token for HTML forms
     * 
     * @return string HTML input field with CSRF token
     */
    public function getCsrfField(): string
    {
        $token = $this->generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Get all users
     * 
     * @return array
     */
    public function getAllUsers(): array
    {
        try {
            $usersJson = $this->fileHandler->read(self::USERS_FILE);
            $users = json_decode($usersJson, true);
            return is_array($users) ? $users : [];
        } catch (\Exception $e) {
            error_log('Get users error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param string $userId
     * @return array|null
     */
    public function getUserById(string $userId): ?array
    {
        $users = $this->getAllUsers();
        foreach ($users as $user) {
            if (isset($user['id']) && $user['id'] === $userId) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Get user by username
     * 
     * @param string $username
     * @return array|null
     */
    public function getUserByUsername(string $username): ?array
    {
        $users = $this->getAllUsers();
        foreach ($users as $user) {
            if (isset($user['username']) && $user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Create a new user
     * 
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $role
     * @return bool
     */
    public function createUser(string $username, string $password, string $email, string $role = 'admin'): bool
    {
        try {
            // Check if username already exists
            if ($this->getUserByUsername($username) !== null) {
                return false;
            }
            
            $users = $this->getAllUsers();
            
            // Generate unique ID
            $id = strtolower(preg_replace('/[^a-z0-9]+/', '', $username));
            if ($this->getUserById($id) !== null) {
                $id = $id . '_' . time();
            }
            
            $newUser = [
                'id' => $id,
                'username' => $username,
                'password_hash' => $this->hashPassword($password),
                'email' => $email,
                'role' => $role,
                'created_at' => date('c')
            ];
            
            $users[] = $newUser;
            
            $usersJson = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->fileHandler->write(self::USERS_FILE, $usersJson);
            
            return true;
        } catch (\Exception $e) {
            error_log('Create user error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user
     * 
     * @param string $userId
     * @param array $data
     * @return bool
     */
    public function updateUser(string $userId, array $data): bool
    {
        try {
            $users = $this->getAllUsers();
            $updated = false;
            
            foreach ($users as &$user) {
                if (isset($user['id']) && $user['id'] === $userId) {
                    // Update allowed fields
                    if (isset($data['email'])) {
                        $user['email'] = $data['email'];
                    }
                    if (isset($data['role'])) {
                        $user['role'] = $data['role'];
                    }
                    if (isset($data['password']) && !empty($data['password'])) {
                        $user['password_hash'] = $this->hashPassword($data['password']);
                    }
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                $usersJson = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $this->fileHandler->write(self::USERS_FILE, $usersJson);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log('Update user error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user
     * 
     * @param string $userId
     * @return bool
     */
    public function deleteUser(string $userId): bool
    {
        try {
            // Prevent deleting the last admin user
            $users = $this->getAllUsers();
            $adminCount = 0;
            foreach ($users as $user) {
                if (isset($user['role']) && $user['role'] === 'admin') {
                    $adminCount++;
                }
            }
            
            if ($adminCount <= 1) {
                return false;
            }
            
            $filteredUsers = array_filter($users, function($user) use ($userId) {
                return !isset($user['id']) || $user['id'] !== $userId;
            });
            
            if (count($filteredUsers) === count($users)) {
                return false;
            }
            
            $usersJson = json_encode(array_values($filteredUsers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->fileHandler->write(self::USERS_FILE, $usersJson);
            
            return true;
        } catch (\Exception $e) {
            error_log('Delete user error: ' . $e->getMessage());
            return false;
        }
    }
}
