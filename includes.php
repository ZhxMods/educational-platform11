<?php
/**
 * Master Include File - Load All Dependencies in Correct Order
 * 
 * Use this at the top of ANY PHP file in your project:
 * require_once 'includes.php';
 * 
 * This ensures everything loads in the right order and prevents errors
 */

// Prevent direct access
if (!defined('ALLOW_INCLUDES')) {
    define('ALLOW_INCLUDES', true);
}

// Step 1: Load configuration (defines constants)
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// Step 2: Load database layer (creates connection)
if (!function_exists('db_connect')) {
    require_once __DIR__ . '/includes/db.php';
}

// Step 3: Load helper functions (translations, utilities)
if (!function_exists('t')) {
    require_once __DIR__ . '/includes/functions.php';
}

// All dependencies loaded successfully
define('INCLUDES_LOADED', true);
