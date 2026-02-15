<?php
/**
 * Auth Guard — include at the top of every protected student page.
 * Boots config + session, verifies user, exposes $currentUser.
 */

declare(strict_types=1);

$_rootDir = dirname(__DIR__);

if (!defined('DB_HOST'))              require_once $_rootDir . '/config.php';
if (!function_exists('db_row'))       require_once $_rootDir . '/includes/db.php';
if (!function_exists('t'))            require_once $_rootDir . '/includes/functions.php';
// getCsrfToken is already in functions.php — csrf.php is just a shim
if (!function_exists('getCsrfToken')) require_once $_rootDir . '/includes/csrf.php';

// ── Session guard ─────────────────────────────────────────────
if (empty($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: ' . SITE_URL . '/login.php?redirect=' . $returnUrl, true, 302);
    exit;
}

// ── Hydrate current user ──────────────────────────────────────
$currentUser = db_row(
    'SELECT id, username, full_name, email, role, level_id,
            xp_points, current_level, preferred_language, is_active
     FROM   users WHERE id = ?',
    [(int) $_SESSION['user_id']]
);

if (!$currentUser || !(bool) $currentUser['is_active']) {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php?error=disabled', true, 302);
    exit;
}

if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = $currentUser['preferred_language'];
}

/**
 * Restrict page to specific roles.
 * @param string|string[] $allowedRoles
 */
function auth_require_role($allowedRoles): void
{
    global $currentUser;
    $allowed = (array) $allowedRoles;
    if (!in_array($currentUser['role'] ?? '', $allowed, true)) {
        $home = in_array($currentUser['role'] ?? '', ['admin', 'super_admin', 'staff'], true)
            ? SITE_URL . '/admin/dashboard.php'
            : SITE_URL . '/dashboard.php';
        header('Location: ' . $home, true, 302);
        exit;
    }
}
