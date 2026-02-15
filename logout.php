<?php
/**
 * logout.php — Secure session termination
 */
declare(strict_types=1);

require_once 'config.php';

// Unset all session variables first
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

header('Location: ' . SITE_URL . '/login.php', true, 302);
exit;
