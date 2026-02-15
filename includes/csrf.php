<?php
/**
 * includes/csrf.php
 * Thin shim — actual getCsrfToken() / verifyCsrfToken() live in functions.php.
 * Including this file is safe even if functions.php was already loaded.
 */

declare(strict_types=1);

if (!function_exists('getCsrfToken')) {
    require_once __DIR__ . '/functions.php';
}
