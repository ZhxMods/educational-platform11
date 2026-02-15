<?php
/**
 * ajax/complete_lesson.php
 * Secure AJAX endpoint — marks a lesson complete and awards XP.
 *
 * Method  : POST
 * Params  : lesson_id (int), csrf_token (string)
 * Returns : JSON
 */

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Bootstrap (FIXED: path is one level up from /ajax/) ───────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

ob_start();
header('Content-Type: application/json; charset=UTF-8');

function jsonOut(bool $success, string $message, array $data = []): never
{
    ob_end_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ── Session guard ─────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    jsonOut(false, 'Unauthenticated');
}

$userId = (int) $_SESSION['user_id'];

// ── CSRF check ────────────────────────────────────────────────
$sentToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verifyCsrfToken((string) $sentToken)) {
    http_response_code(403);
    jsonOut(false, 'Invalid CSRF token');
}

// ── Input ─────────────────────────────────────────────────────
$lessonId = (int) ($_POST['lesson_id'] ?? 0);
if ($lessonId <= 0) {
    http_response_code(400);
    jsonOut(false, 'Invalid lesson ID');
}

// ── Fetch user ────────────────────────────────────────────────
$user = db_row(
    'SELECT id, level_id, xp_points, current_level, role FROM users WHERE id = ? AND is_active = 1',
    [$userId]
);

if (!$user || $user['role'] !== 'student') {
    http_response_code(403);
    jsonOut(false, 'Access denied');
}

// ── Fetch lesson ──────────────────────────────────────────────
$lesson = db_row(
    'SELECT l.id, l.xp_reward, l.title_ar, l.title_fr, l.title_en, s.level_id
     FROM   lessons l
     JOIN   subjects s ON l.subject_id = s.id
     WHERE  l.id = ? AND l.is_published = 1',
    [$lessonId]
);

if (!$lesson) {
    http_response_code(404);
    jsonOut(false, 'Lesson not found');
}

// Restrict to student's own level
if ((int) $lesson['level_id'] !== (int) $user['level_id']) {
    http_response_code(403);
    jsonOut(false, 'Lesson not in your grade level');
}

// ── Idempotency ───────────────────────────────────────────────
$existing = db_row(
    "SELECT id, status FROM lesson_progress WHERE user_id = ? AND lesson_id = ?",
    [$userId, $lessonId]
);

if ($existing && $existing['status'] === 'completed') {
    jsonOut(true, 'already_completed', [
        'xp_points'     => (int) $user['xp_points'],
        'current_level' => (int) $user['current_level'],
        'xp_earned'     => 0,
        'leveled_up'    => false,
    ]);
}

// ── Award XP ──────────────────────────────────────────────────
$xpReward  = max(1, (int) $lesson['xp_reward']);
$oldXP     = (int) $user['xp_points'];
$newXP     = $oldXP + $xpReward;
$oldLevel  = calculateLevel($oldXP);
$newLevel  = calculateLevel($newXP);
$leveledUp = $newLevel > $oldLevel;

// ── DB writes ─────────────────────────────────────────────────
try {
    db()->beginTransaction();

    if ($existing) {
        db_run(
            "UPDATE lesson_progress
             SET    status = 'completed', xp_earned = ?, completed_at = NOW()
             WHERE  user_id = ? AND lesson_id = ?",
            [$xpReward, $userId, $lessonId]
        );
    } else {
        db_run(
            "INSERT INTO lesson_progress (user_id, lesson_id, status, xp_earned, completed_at)
             VALUES (?, ?, 'completed', ?, NOW())",
            [$userId, $lessonId, $xpReward]
        );
    }

    db_run(
        'UPDATE users SET xp_points = ?, current_level = ? WHERE id = ?',
        [$newXP, $newLevel, $userId]
    );

    db()->commit();
} catch (Exception $e) {
    db()->rollBack();
    error_log('[XP] Transaction failed: ' . $e->getMessage());
    http_response_code(500);
    jsonOut(false, 'Server error — please try again');
}

// ── XP progress for next level ────────────────────────────────
$xpProgress    = getXPProgress($newXP);
$nextLevelXP   = getXPForNextLevel($newXP);
$xpToNextLevel = $nextLevelXP - $newXP;

jsonOut(true, 'success', [
    'xp_earned'      => $xpReward,
    'xp_points'      => $newXP,
    'old_xp'         => $oldXP,
    'current_level'  => $newLevel,
    'old_level'      => $oldLevel,
    'leveled_up'     => $leveledUp,
    'xp_progress'    => round($xpProgress, 2),
    'xp_to_next'     => $xpToNextLevel,
]);
