<?php
/**
 * includes/admin_auth.php
 * Admin-only session guard. Include at top of every admin page.
 * Allowed roles: 'admin', 'super_admin'
 */

declare(strict_types=1);

$_rootDir = dirname(__DIR__);

if (!defined('DB_HOST'))              require_once $_rootDir . '/config.php';
if (!function_exists('db_row'))       require_once $_rootDir . '/includes/db.php';
if (!function_exists('t'))            require_once $_rootDir . '/includes/functions.php';
if (!function_exists('getCsrfToken')) require_once $_rootDir . '/includes/csrf.php';

// ── 1. Must be logged in ──────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''), true, 302);
    exit;
}

// ── 2. Must be admin or super_admin ──────────────────────────
$_allowedRoles = ['admin', 'super_admin'];
if (!in_array($_SESSION['user_role'] ?? '', $_allowedRoles, true)) {
    header('Location: ' . SITE_URL . '/dashboard.php', true, 302);
    exit;
}

// ── 3. Hydrate admin user from DB ────────────────────────────
$adminUser = db_row(
    "SELECT id, username, full_name, email, role, preferred_language, is_active
     FROM   users
     WHERE  id = ? AND role IN ('admin', 'super_admin')",
    [(int) $_SESSION['user_id']]
);

if (!$adminUser || !(bool) $adminUser['is_active']) {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php?error=disabled', true, 302);
    exit;
}

// ── 4. Sync session language ──────────────────────────────────
if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = $adminUser['preferred_language'];
}

// ── 5. Convenience globals ────────────────────────────────────
$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = ($dir === 'rtl');
$csrfToken   = getCsrfToken();

// ── 6. CSRF check helper ──────────────────────────────────────
function adminVerifyCsrf(): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verifyCsrfToken((string) $sent)) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
    }
}
