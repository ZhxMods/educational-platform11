<?php
/**
 * auth.php — Legacy redirect. New pages are login.php and register.php.
 */
declare(strict_types=1);
require_once 'config.php';
require_once 'includes/functions.php';

$action = $_GET['action'] ?? 'login';
redirect(SITE_URL . '/' . ($action === 'register' ? 'register.php' : 'login.php'));
