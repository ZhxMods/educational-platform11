<?php
/**
 * Educational Platform — Configuration
 * CORRECTED CREDENTIALS FOR INFINITYFREE
 */

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
//  DATABASE CONFIGURATION — CORRECT INFINITYFREE CREDENTIALS
// ═══════════════════════════════════════════════════════════════════════════

define('DB_HOST', 'sql305.infinityfree.com');      // Your InfinityFree MySQL hostname
define('DB_NAME', 'if0_41161464_testbymoha');       // Your database name
define('DB_USER', 'if0_41161464');                  // Your database username  
define('DB_PASS', 'YhOYcxnUnM');                    // CORRECT PASSWORD
define('DB_CHARSET', 'utf8mb4');

// ═══════════════════════════════════════════════════════════════════════════
//  SITE CONFIGURATION
// ═══════════════════════════════════════════════════════════════════════════

define('SITE_URL', 'https://testsitebymoha.free.nf');
define('SITE_NAME', 'منصة التعليم | Plateforme Éducative');

// ═══════════════════════════════════════════════════════════════════════════
//  LANGUAGE SETTINGS — DEFAULT: FRENCH
// ═══════════════════════════════════════════════════════════════════════════

define('DEFAULT_LANG', 'fr');                        // Changed to French
define('AVAILABLE_LANGS', ['ar', 'fr', 'en']);
define('LANG_DIR', __DIR__ . '/lang/');

// ═══════════════════════════════════════════════════════════════════════════
//  GAMIFICATION / XP SYSTEM
// ═══════════════════════════════════════════════════════════════════════════

define('XP_LESSON_COMPLETE', 10);                    // XP for completing a lesson
define('XP_QUIZ_PASS', 50);                          // XP for passing a quiz
define('XP_PER_LEVEL', 100);                         // XP needed per level

// ═══════════════════════════════════════════════════════════════════════════
//  SECURITY
// ═══════════════════════════════════════════════════════════════════════════

define('SESSION_LIFETIME', 86400);                   // 24 hours

// ═══════════════════════════════════════════════════════════════════════════
//  TIMEZONE (Morocco)
// ═══════════════════════════════════════════════════════════════════════════

date_default_timezone_set('Africa/Casablanca');

// ═══════════════════════════════════════════════════════════════════════════
//  ERROR REPORTING
//  ⚠️ PRODUCTION: Set display_errors to '0'
//  ⚠️ DEBUGGING: Set display_errors to '1'
// ═══════════════════════════════════════════════════════════════════════════

error_reporting(E_ALL);
ini_set('display_errors', '1');                      // Change to '0' for production
ini_set('log_errors', '1');

// Create logs directory
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

// ═══════════════════════════════════════════════════════════════════════════
//  SESSION CONFIGURATION (InfinityFree compatible)
// ═══════════════════════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',                             // Empty for InfinityFree
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    
    try {
        session_start();
    } catch (Exception $e) {
        error_log('[CONFIG] Session start failed: ' . $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  INFINITYFREE OPTIMIZATIONS
// ═══════════════════════════════════════════════════════════════════════════

// Root path
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Memory limit (InfinityFree can be restrictive)
@ini_set('memory_limit', '128M');

// Execution time
@set_time_limit(30);

// Output buffering (helps with InfinityFree)
if (!ob_get_level()) {
    ob_start();
}
