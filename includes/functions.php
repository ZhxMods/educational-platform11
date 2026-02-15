<?php
/**
 * Helper Functions - MD5 VERSION
 * Compatible: PHP 7.4 / 8.0  |  InfinityFree
 * 
 * ⚠️ WARNING: This version uses MD5 for password hashing
 * MD5 is NOT secure! Only use for testing/compatibility.
 * Upgrade to bcrypt in production.
 */

declare(strict_types=1);

if (!defined('DEFAULT_LANG')) {
    require_once __DIR__ . '/../config.php';
}

// ══════════════════════════════════════════════
//  LANGUAGE
// ══════════════════════════════════════════════

function getCurrentLang(): string
{
    $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
    return in_array($lang, AVAILABLE_LANGS, true) ? $lang : DEFAULT_LANG;
}

function setLanguage(string $lang): bool
{
    if (in_array($lang, AVAILABLE_LANGS, true)) {
        $_SESSION['lang'] = $lang;
        return true;
    }
    return false;
}

function getDirection(): string
{
    return getCurrentLang() === 'ar' ? 'rtl' : 'ltr';
}

function loadLanguage(?string $lang = null): array
{
    $lang = $lang ?? getCurrentLang();
    $file = LANG_DIR . $lang . '.json';
    if (!file_exists($file)) {
        $file = LANG_DIR . DEFAULT_LANG . '.json';
    }
    $json = file_get_contents($file);
    if ($json === false) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function t(string $key): string
{
    static $translations = null;
    if ($translations === null) {
        $translations = loadLanguage();
    }
    return $translations[$key] ?? $key;
}

// ══════════════════════════════════════════════
//  AUTHENTICATION
// ══════════════════════════════════════════════

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/login.php');
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        $target = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin', 'staff'], true)
            ? SITE_URL . '/admin/dashboard.php'
            : SITE_URL . '/dashboard.php';
        redirect($target);
    }
}

function getCurrentUser(): ?array
{
    if (!isLoggedIn()) return null;
    if (!function_exists('db_row')) {
        require_once __DIR__ . '/db.php';
    }
    return db_row('SELECT * FROM users WHERE id = ?', [(int) $_SESSION['user_id']]);
}

// ══════════════════════════════════════════════
//  SECURITY — MD5 PASSWORD FUNCTIONS
// ══════════════════════════════════════════════

/**
 * Hash password using MD5
 * ⚠️ WARNING: MD5 is NOT secure! Only for compatibility.
 */
function hashPassword(string $password): string
{
    // Use MD5 for compatibility
    return md5($password);
}

/**
 * Verify password using MD5
 * ⚠️ WARNING: MD5 is NOT secure! Only for compatibility.
 */
function verifyPassword(string $password, string $hash): bool
{
    // Support both MD5 and bcrypt for migration
    
    // Check if it's a bcrypt hash (starts with $2y$)
    if (substr($hash, 0, 4) === '$2y$' || substr($hash, 0, 4) === '$2a$' || substr($hash, 0, 4) === '$2b$') {
        return password_verify($password, $hash);
    }
    
    // Otherwise use MD5
    return md5($password) === $hash;
}

// ══════════════════════════════════════════════
//  XP / GAMIFICATION
// ══════════════════════════════════════════════

function calculateLevel(int $xp): int
{
    return (int) floor($xp / XP_PER_LEVEL) + 1;
}

function getXPProgress(int $xp): float
{
    $level     = calculateLevel($xp);
    $xpInLevel = $xp - (($level - 1) * XP_PER_LEVEL);
    return round(($xpInLevel / XP_PER_LEVEL) * 100, 2);
}

function getXPForNextLevel(int $xp): int
{
    $level = calculateLevel($xp);
    return $level * XP_PER_LEVEL;
}

function addXP(int $userId, int $amount): bool
{
    if (!function_exists('db_row')) {
        require_once __DIR__ . '/db.php';
    }
    $user = db_row('SELECT xp_points FROM users WHERE id = ?', [$userId]);
    if (!$user) return false;
    $newXP    = (int) $user['xp_points'] + $amount;
    $newLevel = calculateLevel($newXP);
    db_run('UPDATE users SET xp_points = ?, current_level = ? WHERE id = ?', [$newXP, $newLevel, $userId]);
    return true;
}

// ══════════════════════════════════════════════
//  SANITISATION
// ══════════════════════════════════════════════

function sanitize(string $str): string
{
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ══════════════════════════════════════════════
//  FLASH MESSAGES
// ══════════════════════════════════════════════

function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $message];
}

function getFlashMessage(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ══════════════════════════════════════════════
//  REDIRECT
// ══════════════════════════════════════════════

function redirect(string $url): void
{
    header('Location: ' . $url, true, 302);
    exit;
}

// ══════════════════════════════════════════════
//  YOUTUBE EMBED
// ══════════════════════════════════════════════

function getYouTubeEmbedUrl(string $url): string
{
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)'
             . '|youtu\.be\/)([^"&?\/ ]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0';
    }
    return $url;
}

function isYouTubeUrl(string $url): bool
{
    return (bool) preg_match('/(?:youtube\.com|youtu\.be)/i', $url);
}

// ══════════════════════════════════════════════
//  CSRF TOKEN (kept for compatibility)
// ══════════════════════════════════════════════

if (!function_exists('getCsrfToken')) {
    function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(string $submitted): bool
    {
        $stored = $_SESSION['csrf_token'] ?? '';
        return $stored !== '' && hash_equals($stored, $submitted);
    }
}
