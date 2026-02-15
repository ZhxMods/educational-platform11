<?php
/**
 * student/index.php — Student Dashboard (legacy redirect)
 * FIXED: Replaced getDB() with db(), fixed require paths
 * NOTE: The main dashboard is /dashboard.php — this page redirects there.
 */

declare(strict_types=1);

// Correct paths: student/ is one level below root
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect students to the proper dashboard
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}
redirect(SITE_URL . '/dashboard.php');
