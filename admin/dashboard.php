<?php
/**
 * admin/dashboard.php — Admin Overview Dashboard
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';

// ── Stats ─────────────────────────────────────────────────────
$totalStudents = (int) db_row("SELECT COUNT(*) AS n FROM users WHERE role = 'student'")['n'];
$totalLessons  = (int) db_row("SELECT COUNT(*) AS n FROM lessons WHERE is_published = 1")['n'];
$totalXP       = (int) db_row("SELECT COALESCE(SUM(xp_points),0) AS n FROM users WHERE role = 'student'")['n'];
$totalSubjects = (int) db_row("SELECT COUNT(*) AS n FROM subjects")['n'];

// ── Recent lesson completions ─────────────────────────────────
$recentActivity = db_all(
    "SELECT u.full_name, u.username,
            l.title_ar, l.title_fr, l.title_en,
            lp.completed_at, lp.xp_earned
     FROM   lesson_progress lp
     JOIN   users   u ON lp.user_id   = u.id
     JOIN   lessons l ON lp.lesson_id = l.id
     WHERE  lp.status = 'completed' AND lp.completed_at IS NOT NULL
     ORDER  BY lp.completed_at DESC
     LIMIT  12"
);

// ── Top students ──────────────────────────────────────────────
$topStudents = db_all(
    "SELECT u.full_name, u.username, u.xp_points, u.current_level,
            lv.name_{$currentLang} AS level_name
     FROM   users u
     LEFT   JOIN levels lv ON u.level_id = lv.id
     WHERE  u.role = 'student' AND u.is_active = 1
     ORDER  BY u.xp_points DESC
     LIMIT  5"
);

// ── New students this week ─────────────────────────────────────
$newThisWeek = (int) db_row(
    "SELECT COUNT(*) AS n FROM users WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)['n'];

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once '_layout.php';
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#eff6ff;">
      <i data-lucide="users" width="22" height="22" color="#1d4ed8"></i>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?php echo number_format($totalStudents); ?></div>
      <div class="stat-label">Total Students</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#f0fdf4;">
      <i data-lucide="book-open" width="22" height="22" color="#16a34a"></i>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?php echo number_format($totalLessons); ?></div>
      <div class="stat-label">Published Lessons</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#fef9c3;">
      <i data-lucide="zap" width="22" height="22" color="#ca8a04"></i>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?php echo number_format($totalXP); ?></div>
      <div class="stat-label">Total XP Awarded</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#fdf4ff;">
      <i data-lucide="grid-2x2" width="22" height="22" color="#9333ea"></i>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?php echo number_format($totalSubjects); ?></div>
      <div class="stat-label">Subjects</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap" style="background:#fff7ed;">
      <i data-lucide="user-plus" width="22" height="22" color="#ea580c"></i>
    </div>
    <div class="stat-info">
      <div class="stat-num"><?php echo number_format($newThisWeek); ?></div>
      <div class="stat-label">New This Week</div>
    </div>
  </div>
</div>

<!-- Two column: Activity + Top Students -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:1rem;align-items:start;">

  <!-- Recent Activity -->
  <div class="section-card">
    <div class="section-card-head">
      <h2><i data-lucide="activity" width="16" height="16" style="display:inline;vertical-align:middle;margin-right:.4rem;"></i> Recent Activity</h2>
      <span style="font-size:.75rem;color:var(--text-muted);">Last 12 completions</span>
    </div>
    <div style="padding:0 1.25rem;">
      <?php if (empty($recentActivity)): ?>
      <p style="padding:2rem 0;text-align:center;color:var(--text-muted);">No activity yet.</p>
      <?php else: ?>
      <ul class="activity-list">
        <?php foreach ($recentActivity as $act):
          $titleKey = 'title_' . $currentLang;
          $lesson   = htmlspecialchars($act[$titleKey] ?? $act['title_ar']);
          $student  = htmlspecialchars($act['full_name']);
          $time     = $act['completed_at']
              ? date('M j, g:i a', strtotime($act['completed_at']))
              : '—';
        ?>
        <li class="activity-item">
          <div class="activity-dot" style="background:#eff6ff;">
            <i data-lucide="check-circle-2" width="16" height="16" color="#1d4ed8"></i>
          </div>
          <div class="activity-text">
            <strong><?php echo $student; ?></strong>
            <p>Completed <em><?php echo $lesson; ?></em>
               &nbsp;·&nbsp; +<?php echo (int) $act['xp_earned']; ?> XP
            </p>
          </div>
          <span class="activity-time"><?php echo $time; ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top Students -->
  <div class="section-card">
    <div class="section-card-head">
      <h2><i data-lucide="trophy" width="16" height="16" style="display:inline;vertical-align:middle;margin-right:.4rem;"></i> Top Students</h2>
    </div>
    <div style="padding:0 1.25rem;">
      <?php if (empty($topStudents)): ?>
      <p style="padding:2rem 0;text-align:center;color:var(--text-muted);">No students yet.</p>
      <?php else: ?>
      <?php
      $medals = ['🥇','🥈','🥉'];
      foreach ($topStudents as $i => $stu): ?>
      <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 0;border-bottom:1px solid var(--card-border);">
        <span style="font-size:1.2rem;width:28px;text-align:center;"><?php echo $medals[$i] ?? '#'.($i+1); ?></span>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.875rem;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?php echo htmlspecialchars($stu['full_name']); ?>
          </div>
          <div style="font-size:.75rem;color:var(--text-muted);">
            <?php echo htmlspecialchars($stu['level_name'] ?? '—'); ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          <div style="font-weight:700;color:var(--blue-700);font-size:.875rem;"><?php echo number_format((int)$stu['xp_points']); ?> XP</div>
          <div style="font-size:.72rem;color:var(--text-muted);">Level <?php echo (int)$stu['current_level']; ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Quick links -->
<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem;">
  <a href="manage_lessons.php" class="btn btn-primary">
    <i data-lucide="plus-circle" width="15" height="15"></i> Add Lesson
  </a>
  <a href="manage_users.php" class="btn btn-ghost">
    <i data-lucide="users" width="15" height="15"></i> Manage Users
  </a>
</div>

<?php require_once '_layout_end.php'; ?>
