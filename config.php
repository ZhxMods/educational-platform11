<?php
/**
 * Educational Platform — Configuration
 * Compatible: PHP 7.4 / 8.0  |  Host: InfinityFree / MySQL
 */

// ─────────────────────────────────────────────
//  DATABASE — InfinityFree Connection Details
// ─────────────────────────────────────────────
define('DB_HOST',    'sql305.infinityfree.com');  // MySQL Hostname
define('DB_NAME',    'if0_41161464_testbymoha'); // MySQL Database Name
define('DB_USER',    'if0_41161464');             // MySQL Username
define('DB_PASS',    'Yn0YcxnUnM');               // MySQL Password
define('DB_CHARSET', 'utf8mb4');

// ─────────────────────────────────────────────
//  SITE
// ─────────────────────────────────────────────
define('SITE_URL',  'https://yourdomain.rf.gd');  // CHANGE THIS: your actual domain (no trailing slash)
define('SITE_NAME', 'منصة التعليم | Plateforme Éducative');

// ─────────────────────────────────────────────
//  LANGUAGE
// ─────────────────────────────────────────────
define('DEFAULT_LANG',    'ar');
define('AVAILABLE_LANGS', ['ar', 'fr', 'en']);
define('LANG_DIR',        __DIR__ . '/lang/');

// ─────────────────────────────────────────────
//  GAMIFICATION / XP
// ─────────────────────────────────────────────
define('XP_LESSON_COMPLETE', 10);
define('XP_QUIZ_PASS',       50);
define('XP_PER_LEVEL',      100);

// ─────────────────────────────────────────────
//  SECURITY
// ─────────────────────────────────────────────
define('SESSION_LIFETIME', 86400);   // 24 hours in seconds

// ─────────────────────────────────────────────
//  TIMEZONE  (Morocco)
// ─────────────────────────────────────────────
date_default_timezone_set('Africa/Casablanca');

// ─────────────────────────────────────────────
//  ERROR REPORTING
// ─────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', '0'); // OFF for production (change to '1' for debugging)
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ─────────────────────────────────────────────
//  SESSION  (start once, safely)
// ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
