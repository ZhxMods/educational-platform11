<?php
/**
 * dashboard.php — Student Dashboard
 * Theme : Professional Blue & White
 * RTL   : Full Arabic support
 * Tabs  : My Lessons | Leaderboard | Achievements
 */

declare(strict_types=1);

require_once 'includes/auth_check.php';   // also loads config, db, functions + csrf
// EDIT 2: csrf.php is now loaded inside auth_check.php automatically

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = $dir === 'rtl';

$user    = $currentUser;
$levelId = (int) ($user['level_id'] ?? 0);

// ── XP calculations ──────────────────────────────────────────
$xpProgress   = getXPProgress((int) $user['xp_points']);
$nextLevelXP  = getXPForNextLevel((int) $user['xp_points']);
$xpToGo       = $nextLevelXP - (int) $user['xp_points'];

// ── Current grade level name ──────────────────────────────────
$levelRow  = $levelId ? db_row('SELECT name_ar, name_fr, name_en FROM levels WHERE id = ?', [$levelId]) : null;
$levelName = $levelRow ? htmlspecialchars($levelRow['name_' . $currentLang]) : '—';

// ── Subjects for this level ───────────────────────────────────
$subjects = $levelId
    ? db_all(
        "SELECT s.*,
                (SELECT COUNT(*) FROM lessons l WHERE l.subject_id = s.id AND l.is_published = 1) AS lesson_count,
                (SELECT COUNT(*) FROM lessons l
                    JOIN lesson_progress lp ON l.id = lp.lesson_id
                 WHERE l.subject_id = s.id
                   AND lp.user_id = ?
                   AND lp.status  = 'completed') AS completed_count
         FROM   subjects s
         WHERE  s.level_id = ?
         ORDER  BY s.display_order ASC",
        [(int) $user['id'], $levelId]
    )
    : [];

// ── Recent lessons ────────────────────────────────────────────
$recentLessons = $levelId
    ? db_all(
        "SELECT l.id, l.title_ar, l.title_fr, l.title_en,
                l.content_type, l.duration_minutes, l.xp_reward,
                s.name_ar AS sub_ar, s.name_fr AS sub_fr, s.name_en AS sub_en, s.color,
                COALESCE(lp.status,'not_started') AS status
         FROM   lessons l
         JOIN   subjects s ON l.subject_id = s.id
         LEFT   JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
         WHERE  s.level_id = ? AND l.is_published = 1
         ORDER  BY l.created_at DESC
         LIMIT  6",
        [(int) $user['id'], $levelId]
    )
    : [];

// ── Stats ─────────────────────────────────────────────────────
$completedCount = (int) db_row(
    "SELECT COUNT(*) AS n FROM lesson_progress WHERE user_id = ? AND status = 'completed'",
    [(int) $user['id']]
)['n'];

$quizCount = (int) db_row(
    'SELECT COUNT(*) AS n FROM quiz_attempts WHERE user_id = ?',
    [(int) $user['id']]
)['n'];

$avgScore = (float) (db_row(
    'SELECT AVG(score) AS avg FROM quiz_attempts WHERE user_id = ?',
    [(int) $user['id']]
)['avg'] ?? 0);

// ── Leaderboard — top 5 in same level ────────────────────────
$leaderboard = $levelId
    ? db_all(
        'SELECT id, username, full_name, xp_points, current_level
         FROM   users
         WHERE  role = ? AND level_id = ? AND is_active = 1
         ORDER  BY xp_points DESC
         LIMIT  5',
        ['student', $levelId]
    )
    : [];

// ── Achievements ──────────────────────────────────────────────
$achievements = [];
if ($completedCount >= 1)  $achievements[] = ['icon' => 'star',   'label' => t('achievement_first_lesson'), 'color' => '#f59e0b'];
if ($completedCount >= 5)  $achievements[] = ['icon' => 'flame',  'label' => t('achievement_5_lessons'),    'color' => '#ef4444'];
if ($completedCount >= 10) $achievements[] = ['icon' => 'award',  'label' => t('achievement_10_lessons'),   'color' => '#8b5cf6'];
if ($quizCount >= 1)       $achievements[] = ['icon' => 'brain',  'label' => t('achievement_first_quiz'),   'color' => '#06b6d4'];
if ($avgScore >= 80)       $achievements[] = ['icon' => 'trophy', 'label' => t('achievement_high_scorer'),  'color' => '#10b981'];
if ((int)$user['xp_points'] >= 100) $achievements[] = ['icon' => 'zap', 'label' => t('achievement_100_xp'), 'color' => '#3b82f6'];

$pageTitle = t('dashboard') . ' — ' . t('app_name');

$typeIcons  = ['video' => 'play-circle', 'pdf' => 'file-text', 'book' => 'book-open'];
$typeColors = ['video' => '#3b82f6', 'pdf' => '#ef4444', 'book' => '#10b981'];

// EDIT 1: Generate CSRF token for AJAX calls
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <!-- EDIT 1: CSRF meta tag for xp-system.js -->
  <meta name="csrf-token" content="<?php echo $csrfToken; ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <!-- EDIT 1: XP Animation CSS -->
  <link rel="stylesheet" href="css/xp-animations.css">

  <style>
    :root {
      --blue-950: #0f1f5c;
      --blue-900: #1e3a8a;
      --blue-700: #1d4ed8;
      --blue-600: #2563eb;
      --blue-500: #3b82f6;
      --blue-100: #dbeafe;
      --blue-50:  #eff6ff;
      --white:    #ffffff;
      --gray-50:  #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-400: #94a3b8;
      --gray-500: #64748b;
      --gray-600: #475569;
      --gray-700: #334155;
      --gray-800: #1e293b;
      --gray-900: #0f172a;
      --radius:   12px;
      --sidebar-w: 240px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: <?php echo $isRtl ? "'Cairo'" : "'Plus Jakarta Sans'"; ?>, sans-serif;
      background: var(--gray-100);
      color: var(--gray-800);
      min-height: 100vh;
    }

    /* ── Top Nav ── */
    .topnav {
      position: sticky; top: 0; z-index: 100;
      background: var(--blue-900);
      background-image: linear-gradient(90deg, var(--blue-950) 0%, var(--blue-700) 100%);
      height: 64px;
      display: flex; align-items: center;
      padding: 0 1.5rem; gap: 1rem;
      box-shadow: 0 2px 20px rgba(0,0,0,.3);
    }
    .topnav-brand {
      display: flex; align-items: center; gap: .6rem;
      color: #fff; text-decoration: none; font-weight: 700; font-size: 1.1rem; flex-shrink: 0;
    }
    .brand-icon {
      width: 36px; height: 36px; background: rgba(255,255,255,.15);
      border-radius: 8px; display: flex; align-items: center; justify-content: center;
    }
    .topnav-spacer { flex: 1; }

    .xp-pill {
      display: flex; align-items: center; gap: .5rem;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 50px; padding: .35rem .85rem;
      color: #fff; font-size: .82rem; font-weight: 600;
    }
    .xp-pill .xp-num { color: #fde68a; font-weight: 700; }

    .level-badge {
      display: inline-flex; align-items: center; gap: .3rem;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: #fff; padding: .3rem .7rem;
      border-radius: 50px; font-size: .78rem; font-weight: 700;
    }

    .nav-avatar { position: relative; }
    .avatar-btn {
      width: 38px; height: 38px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-500), #818cf8);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 700; font-size: .9rem;
      cursor: pointer; border: 2px solid rgba(255,255,255,.3); user-select: none;
    }
    .avatar-dropdown {
      display: none; position: absolute;
      top: calc(100% + .5rem);
      <?php echo $isRtl ? 'left' : 'right'; ?>: 0;
      background: #fff; border-radius: var(--radius);
      box-shadow: 0 8px 30px rgba(0,0,0,.15);
      min-width: 180px; overflow: hidden; z-index: 200;
    }
    .avatar-dropdown.open { display: block; }
    .dropdown-user { padding: .9rem 1rem; border-bottom: 1px solid var(--gray-100); }
    .dropdown-user strong { display: block; font-size: .9rem; color: var(--gray-900); }
    .dropdown-user span   { font-size: .78rem; color: var(--gray-500); }
    .dropdown-link {
      display: flex; align-items: center; gap: .6rem;
      padding: .7rem 1rem; color: var(--gray-700);
      text-decoration: none; font-size: .875rem; transition: background .15s;
    }
    .dropdown-link:hover { background: var(--gray-50); color: var(--blue-700); }
    .dropdown-link.danger { color: #ef4444; }

    .hamburger {
      display: none; background: none; border: none;
      color: #fff; cursor: pointer; padding: .3rem;
    }

    /* ── Layout ── */
    .layout { display: flex; min-height: calc(100vh - 64px); }

    /* ── Sidebar ── */
    .sidebar {
      width: var(--sidebar-w); background: var(--white);
      border-<?php echo $isRtl ? 'left' : 'right'; ?>: 1px solid var(--gray-200);
      flex-shrink: 0; display: flex; flex-direction: column;
      padding: 1.5rem 0; position: sticky; top: 64px;
      height: calc(100vh - 64px); overflow-y: auto; transition: transform .3s;
    }
    .sidebar-section-label {
      font-size: .7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--gray-400);
      padding: 0 1.25rem .5rem; margin-top: 1rem;
    }
    .sidebar-item {
      display: flex; align-items: center; gap: .75rem;
      padding: .7rem 1.25rem; color: var(--gray-600);
      text-decoration: none; font-size: .875rem; font-weight: 500;
      border-<?php echo $isRtl ? 'right' : 'left'; ?>: 3px solid transparent;
      cursor: pointer; background: none;
      border-top: none; border-bottom: none;
      border-<?php echo $isRtl ? 'left' : 'right'; ?>: none;
      width: 100%; text-align: <?php echo $isRtl ? 'right' : 'left'; ?>;
      transition: all .2s; font-family: inherit;
    }
    .sidebar-item:hover { background: var(--blue-50); color: var(--blue-700); }
    .sidebar-item.active {
      background: var(--blue-50); color: var(--blue-700); font-weight: 600;
      border-<?php echo $isRtl ? 'right' : 'left'; ?>-color: var(--blue-600);
    }
    .sidebar-item .badge {
      margin-<?php echo $isRtl ? 'right' : 'left'; ?>: auto;
      background: var(--blue-100); color: var(--blue-700);
      border-radius: 50px; padding: .1rem .5rem;
      font-size: .72rem; font-weight: 700;
    }

    /* ── Main ── */
    .main { flex: 1; padding: 1.75rem; min-width: 0; }

    /* ── Welcome banner ── */
    .welcome-banner {
      background: linear-gradient(135deg, var(--blue-900) 0%, var(--blue-600) 100%);
      border-radius: 16px; padding: 1.75rem 2rem; color: #fff;
      display: flex; align-items: center; justify-content: space-between;
      gap: 1rem; margin-bottom: 1.5rem;
      position: relative; overflow: hidden;
    }
    .welcome-banner::before {
      content: ''; position: absolute;
      top: -30px; <?php echo $isRtl ? 'left' : 'right'; ?>: -30px;
      width: 140px; height: 140px; border-radius: 50%;
      background: rgba(255,255,255,.07);
    }
    .welcome-banner h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: .25rem; }
    .welcome-banner p  { color: rgba(255,255,255,.75); font-size: .9rem; }

    .xp-bar-wrap { flex-shrink: 0; width: 200px; }
    .xp-bar-wrap label { font-size: .8rem; color: rgba(255,255,255,.75); display: block; margin-bottom: .4rem; }
    .xp-bar-track { background: rgba(255,255,255,.2); border-radius: 50px; height: 10px; overflow: hidden; }
    .xp-bar-fill {
      height: 100%; border-radius: 50px;
      background: linear-gradient(90deg, #fde68a, #f59e0b);
      transition: width 1s ease;
    }
    .xp-bar-text { font-size: .75rem; color: rgba(255,255,255,.65); margin-top: .35rem; text-align: <?php echo $isRtl ? 'right' : 'left'; ?>; }

    /* ── Stats grid ── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem; margin-bottom: 1.5rem;
    }
    .stat-card {
      background: var(--white); border-radius: var(--radius);
      padding: 1.25rem; display: flex; align-items: center; gap: 1rem;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .stat-icon {
      width: 44px; height: 44px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .stat-label { font-size: .78rem; color: var(--gray-500); font-weight: 500; }
    .stat-value { font-size: 1.6rem; font-weight: 700; color: var(--gray-900); line-height: 1; margin-top: .2rem; }

    /* ── Section heading ── */
    .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
    .section-head h3 { font-size: 1.1rem; font-weight: 700; color: var(--gray-900); }

    /* ── Tab panel ── */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ── Subject grid ── */
    .subject-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
    .subject-card {
      background: var(--white); border-radius: var(--radius);
      padding: 1.25rem; box-shadow: 0 1px 4px rgba(0,0,0,.06);
      border-top: 4px solid var(--c, var(--blue-500));
      transition: box-shadow .2s, transform .2s;
    }
    .subject-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); transform: translateY(-2px); }
    .subject-card-head { display: flex; align-items: center; gap: .75rem; margin-bottom: .9rem; }
    .subject-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .subject-card h4 { font-size: .95rem; font-weight: 700; color: var(--gray-900); }
    .subject-card .sub-meta { font-size: .78rem; color: var(--gray-500); margin-top: .1rem; }
    .progress-bar { background: var(--gray-100); border-radius: 50px; height: 6px; overflow: hidden; margin: .75rem 0 .5rem; }
    .progress-fill { height: 100%; border-radius: 50px; background: var(--c, var(--blue-500)); transition: width .8s ease; }
    .progress-label { font-size: .75rem; color: var(--gray-500); }

    /* ── Lesson grid ── */
    .lesson-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
    .lesson-card {
      background: var(--white); border-radius: var(--radius);
      overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06);
      transition: box-shadow .2s, transform .2s;
    }
    .lesson-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); transform: translateY(-2px); }
    .lesson-thumb {
      height: 110px; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, var(--blue-900), var(--blue-600));
      position: relative;
    }
    .content-type-badge {
      position: absolute; top: .6rem;
      <?php echo $isRtl ? 'right' : 'left'; ?>: .6rem;
      background: rgba(255,255,255,.15); backdrop-filter: blur(4px);
      color: #fff; border-radius: 6px; padding: .2rem .5rem;
      font-size: .72rem; font-weight: 600;
      display: flex; align-items: center; gap: .3rem;
    }
    .completed-tick {
      position: absolute; top: .6rem;
      <?php echo $isRtl ? 'left' : 'right'; ?>: .6rem;
      background: #22c55e; color: #fff;
      width: 24px; height: 24px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }
    .lesson-body { padding: 1rem; }
    .lesson-subject {
      display: inline-block; padding: .2rem .6rem;
      border-radius: 50px; font-size: .72rem; font-weight: 600; margin-bottom: .5rem;
    }
    .lesson-body h4 {
      font-size: .9rem; font-weight: 700; color: var(--gray-900);
      margin-bottom: .5rem; line-height: 1.35;
      display: -webkit-box; -webkit-line-clamp: 2;
      -webkit-box-orient: vertical; overflow: hidden;
    }
    .lesson-meta { display: flex; align-items: center; gap: 1rem; font-size: .75rem; color: var(--gray-500); }
    .lesson-meta span { display: flex; align-items: center; gap: .3rem; }

    /* ── EDIT 3: Lesson action buttons ── */
    .btn-lesson {
      display: flex; align-items: center; justify-content: center; gap: .4rem;
      width: 100%; padding: .6rem; border-radius: 8px;
      font-size: .83rem; font-weight: 600; font-family: inherit;
      cursor: pointer; border: none; margin-top: .75rem; transition: all .15s;
    }
    .btn-lesson.start { background: var(--blue-700); color: #fff; }
    .btn-lesson.start:hover { background: var(--blue-900); }
    .btn-lesson.continue { background: var(--blue-50); color: var(--blue-700); border: 1px solid var(--blue-200); }
    .btn-lesson.done { background: #f0fdf4; color: #15803d; pointer-events: none; border: 1.5px solid #86efac; }

    /* ── Leaderboard ── */
    .leaderboard-list { display: flex; flex-direction: column; gap: .75rem; }
    .lb-item {
      display: flex; align-items: center; gap: 1rem;
      background: var(--white); border-radius: var(--radius);
      padding: 1rem 1.25rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); transition: box-shadow .2s;
    }
    .lb-item:hover { box-shadow: 0 4px 15px rgba(0,0,0,.1); }
    .lb-item.is-me { border: 2px solid var(--blue-500); background: var(--blue-50); }
    .lb-rank { font-size: 1.2rem; font-weight: 900; color: var(--gray-300); width: 32px; text-align: center; flex-shrink: 0; }
    .lb-rank.gold   { color: #f59e0b; }
    .lb-rank.silver { color: #94a3b8; }
    .lb-rank.bronze { color: #b45309; }
    .lb-avatar {
      width: 42px; height: 42px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-500), #818cf8);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 700; font-size: .95rem; flex-shrink: 0;
    }
    .lb-info { flex: 1; min-width: 0; }
    .lb-name { font-weight: 600; color: var(--gray-900); font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lb-lvl  { font-size: .75rem; color: var(--gray-500); }
    .lb-xp   { display: flex; align-items: center; gap: .3rem; font-weight: 700; font-size: .9rem; color: var(--blue-700); flex-shrink: 0; }

    /* ── Achievements ── */
    .achievement-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
    .achievement-card {
      background: var(--white); border-radius: var(--radius);
      padding: 1.25rem; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,.06); transition: box-shadow .2s;
    }
    .achievement-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); }
    .achievement-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto .75rem; }
    .achievement-card h4 { font-size: .85rem; font-weight: 600; color: var(--gray-800); }

    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--gray-400); }
    .empty-state i { display: block; margin-bottom: 1rem; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sidebar {
        position: fixed; top: 64px;
        <?php echo $isRtl ? 'right' : 'left'; ?>: 0;
        height: calc(100vh - 64px); z-index: 90;
        transform: translateX(<?php echo $isRtl ? '100%' : '-100%'; ?>);
        box-shadow: 4px 0 20px rgba(0,0,0,.15);
      }
      .sidebar.open { transform: translateX(0); }
      .hamburger { display: flex; }
      .main { padding: 1rem; }
      .xp-bar-wrap { display: none; }
      .welcome-banner { flex-direction: column; align-items: flex-start; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>

<!-- ── Top Nav ── -->
<nav class="topnav">
  <button class="hamburger" id="hamburger" aria-label="Menu">
    <i data-lucide="menu" width="22" height="22"></i>
  </button>

  <a href="index.php" class="topnav-brand">
    <span class="brand-icon"><i data-lucide="graduation-cap" width="20" height="20" color="white"></i></span>
    <?php echo t('app_name'); ?>
  </a>

  <div class="topnav-spacer"></div>

  <div class="level-badge">
    <i data-lucide="star" width="13" height="13"></i>
    <?php echo t('level'); ?>
    <!-- EDIT 4B: data-level-display for JS count-up -->
    <span data-level-display><?php echo (int) $user['current_level']; ?></span>
  </div>

  <div class="xp-pill">
    <i data-lucide="zap" width="14" height="14"></i>
    <!-- EDIT 4A: data-xp-counter for JS count-up -->
    <span class="xp-num" data-xp-counter><?php echo number_format((int) $user['xp_points']); ?></span>
    XP
  </div>

  <div class="nav-avatar">
    <div class="avatar-btn" id="avatarBtn">
      <?php echo mb_strtoupper(mb_substr($user['full_name'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
    </div>
    <div class="avatar-dropdown" id="avatarDropdown">
      <div class="dropdown-user">
        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
        <span><?php echo htmlspecialchars($levelName); ?></span>
      </div>
      <a href="dashboard.php" class="dropdown-link">
        <i data-lucide="layout-dashboard" width="15" height="15"></i> <?php echo t('dashboard'); ?>
      </a>
      <a href="logout.php" class="dropdown-link danger">
        <i data-lucide="log-out" width="15" height="15"></i> <?php echo t('logout'); ?>
      </a>
    </div>
  </div>
</nav>

<!-- ── Layout ── -->
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <span class="sidebar-section-label"><?php echo t('navigation') ?: 'Navigation'; ?></span>

    <button class="sidebar-item active" data-tab="lessons" onclick="switchTab('lessons', this)">
      <i data-lucide="book-open" width="18" height="18"></i>
      <?php echo t('lessons'); ?>
      <span class="badge"><?php echo count($recentLessons); ?></span>
    </button>

    <button class="sidebar-item" data-tab="subjects" onclick="switchTab('subjects', this)">
      <i data-lucide="grid-2x2" width="18" height="18"></i>
      <?php echo t('subjects'); ?>
      <span class="badge"><?php echo count($subjects); ?></span>
    </button>

    <button class="sidebar-item" data-tab="leaderboard" onclick="switchTab('leaderboard', this)">
      <i data-lucide="trophy" width="18" height="18"></i>
      <?php echo t('leaderboard'); ?>
    </button>

    <button class="sidebar-item" data-tab="achievements" onclick="switchTab('achievements', this)">
      <i data-lucide="award" width="18" height="18"></i>
      <?php echo t('achievements') ?: 'Achievements'; ?>
      <?php if (count($achievements) > 0): ?>
      <span class="badge"><?php echo count($achievements); ?></span>
      <?php endif; ?>
    </button>

    <span class="sidebar-section-label" style="margin-top:auto;"><?php echo t('account') ?: 'Account'; ?></span>
    <a href="logout.php" class="sidebar-item" style="color:#ef4444;">
      <i data-lucide="log-out" width="18" height="18"></i>
      <?php echo t('logout'); ?>
    </a>
  </aside>

  <!-- Main Content -->
  <main class="main">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div>
        <h2><?php echo t('welcome'); ?>, <?php echo htmlspecialchars($user['full_name']); ?> 👋</h2>
        <p>
          <?php echo $levelName; ?> &bull;
          <?php echo t('level'); ?>
          <!-- EDIT 4B: data-level-display -->
          <span data-level-display><?php echo (int) $user['current_level']; ?></span>
        </p>
      </div>
      <div class="xp-bar-wrap">
        <!-- EDIT 4A: data-xp-bar-label -->
        <label data-xp-bar-label><?php echo number_format((int) $user['xp_points']); ?> XP &bull; <?php echo round($xpProgress); ?>%</label>
        <div class="xp-bar-track">
          <div class="xp-bar-fill" style="width:<?php echo $xpProgress; ?>%" data-width="<?php echo $xpProgress; ?>%"></div>
        </div>
        <div class="xp-bar-text" data-xp-to-next>
          <?php echo number_format($xpToGo); ?> XP <?php echo t('to_next_level'); ?>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff;">
          <i data-lucide="zap" width="22" height="22" color="#1d4ed8"></i>
        </div>
        <div>
          <div class="stat-label"><?php echo t('my_xp'); ?></div>
          <!-- EDIT 4A: data-xp-counter -->
          <div class="stat-value" data-xp-counter><?php echo number_format((int) $user['xp_points']); ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;">
          <i data-lucide="check-circle-2" width="22" height="22" color="#16a34a"></i>
        </div>
        <div>
          <div class="stat-label"><?php echo t('completed_lessons'); ?></div>
          <div class="stat-value"><?php echo $completedCount; ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3;">
          <i data-lucide="clipboard-list" width="22" height="22" color="#ca8a04"></i>
        </div>
        <div>
          <div class="stat-label"><?php echo t('quizzes'); ?></div>
          <div class="stat-value"><?php echo $quizCount; ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fdf4ff;">
          <i data-lucide="percent" width="22" height="22" color="#9333ea"></i>
        </div>
        <div>
          <div class="stat-label"><?php echo t('average_score'); ?></div>
          <div class="stat-value"><?php echo round($avgScore); ?>%</div>
        </div>
      </div>
    </div>

    <!-- TAB: LESSONS -->
    <div id="tab-lessons" class="tab-panel active">
      <div class="section-head">
        <h3><?php echo t('recent_lessons'); ?></h3>
      </div>

      <?php if (empty($recentLessons)): ?>
      <div class="empty-state">
        <i data-lucide="book-dashed" width="48" height="48"></i>
        <p><?php echo t('no_lessons_yet'); ?></p>
      </div>

      <?php else: ?>
      <div class="lesson-grid">
        <?php foreach ($recentLessons as $lesson):
          $titleKey = 'title_' . $currentLang;
          $subKey   = 'sub_'   . $currentLang;
          $lColor   = $lesson['color'] ?? '#1d4ed8';
          $cType    = $lesson['content_type'];
          $lIcon    = $typeIcons[$cType] ?? 'file';
          $status   = $lesson['status'];
        ?>
        <div class="lesson-card">
          <div class="lesson-thumb" style="background:linear-gradient(135deg,<?php echo $lColor; ?>cc,<?php echo $lColor; ?>);">
            <i data-lucide="<?php echo $lIcon; ?>" width="40" height="40" color="rgba(255,255,255,.6)"></i>
            <span class="content-type-badge">
              <i data-lucide="<?php echo $lIcon; ?>" width="11" height="11"></i>
              <?php echo t($cType); ?>
            </span>
            <?php if ($status === 'completed'): ?>
            <span class="completed-tick"><i data-lucide="check" width="14" height="14"></i></span>
            <?php endif; ?>
          </div>
          <div class="lesson-body">
            <span class="lesson-subject" style="background:<?php echo $lColor; ?>20;color:<?php echo $lColor; ?>;">
              <?php echo htmlspecialchars($lesson[$subKey] ?? ''); ?>
            </span>
            <h4><?php echo htmlspecialchars($lesson[$titleKey] ?? ''); ?></h4>
            <div class="lesson-meta">
              <span><i data-lucide="clock" width="12" height="12"></i> <?php echo (int)$lesson['duration_minutes']; ?> <?php echo t('minutes'); ?></span>
              <span><i data-lucide="zap" width="12" height="12"></i> <?php echo (int)$lesson['xp_reward']; ?> XP</span>
            </div>

            <?php if ($status === 'completed'): ?>
            <!-- Completed state — no action needed -->
            <button class="btn-lesson done" disabled>
              <i data-lucide="check-circle-2" width="15" height="15"></i>
              <?php echo t('lesson_completed'); ?>
            </button>

            <?php elseif ($status === 'in_progress'): ?>
            <!-- EDIT 3: Continue button with data-complete-lesson -->
            <button
              class="btn-lesson continue"
              data-complete-lesson
              data-lesson-id="<?php echo (int)$lesson['id']; ?>"
              data-xp-reward="<?php echo (int)$lesson['xp_reward']; ?>">
              <i data-lucide="play" width="15" height="15"></i>
              <?php echo t('continue_learning'); ?>
            </button>

            <?php else: ?>
            <!-- EDIT 3: Start button with data-complete-lesson -->
            <button
              class="btn-lesson start"
              data-complete-lesson
              data-lesson-id="<?php echo (int)$lesson['id']; ?>"
              data-xp-reward="<?php echo (int)$lesson['xp_reward']; ?>">
              <i data-lucide="play-circle" width="15" height="15"></i>
              <?php echo t('start_lesson'); ?>
            </button>

            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- TAB: SUBJECTS -->
    <div id="tab-subjects" class="tab-panel">
      <div class="section-head">
        <h3><?php echo t('subjects'); ?> — <?php echo $levelName; ?></h3>
      </div>

      <?php if (empty($subjects)): ?>
      <div class="empty-state">
        <i data-lucide="folder-open" width="48" height="48"></i>
        <p><?php echo t('no_lessons_yet'); ?></p>
      </div>
      <?php else: ?>
      <div class="subject-grid">
        <?php foreach ($subjects as $sub):
          $sColor   = $sub['color'] ?? '#1d4ed8';
          $sTotal   = (int) $sub['lesson_count'];
          $sDone    = (int) $sub['completed_count'];
          $sPct     = $sTotal > 0 ? round(($sDone / $sTotal) * 100) : 0;
          $sNameKey = 'name_' . $currentLang;
        ?>
        <div class="subject-card" style="--c:<?php echo $sColor; ?>;">
          <div class="subject-card-head">
            <div class="subject-icon" style="background:<?php echo $sColor; ?>20;">
              <i data-lucide="book-marked" width="20" height="20" color="<?php echo $sColor; ?>"></i>
            </div>
            <div>
              <h4><?php echo htmlspecialchars($sub[$sNameKey]); ?></h4>
              <div class="sub-meta"><?php echo $sTotal; ?> <?php echo t('lessons'); ?></div>
            </div>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?php echo $sPct; ?>%;--c:<?php echo $sColor; ?>;"></div>
          </div>
          <div class="progress-label">
            <?php echo $sDone; ?> / <?php echo $sTotal; ?> <?php echo t('completed_lessons'); ?> &bull; <?php echo $sPct; ?>%
          </div>
          <a href="subject.php?id=<?php echo (int)$sub['id']; ?>"
             style="display:flex;align-items:center;gap:.4rem;margin-top:.85rem;font-size:.83rem;font-weight:600;color:<?php echo $sColor; ?>;text-decoration:none;">
            <i data-lucide="arrow-right" width="14" height="14"></i>
            <?php echo t('start_learning'); ?>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- TAB: LEADERBOARD -->
    <div id="tab-leaderboard" class="tab-panel">
      <div class="section-head">
        <h3><?php echo t('leaderboard'); ?> — <?php echo $levelName; ?></h3>
      </div>

      <?php if (empty($leaderboard)): ?>
      <div class="empty-state">
        <i data-lucide="users" width="48" height="48"></i>
        <p><?php echo t('no_lessons_yet'); ?></p>
      </div>
      <?php else: ?>
      <div class="leaderboard-list">
        <?php
        $rankColors = ['gold', 'silver', 'bronze'];
        $rankEmojis = ['🥇', '🥈', '🥉'];
        foreach ($leaderboard as $idx => $student):
          $rank     = $idx + 1;
          $isMe     = ((int) $student['id'] === (int) $user['id']);
          $rClass   = $rankColors[$idx] ?? '';
          $rLabel   = $rankEmojis[$idx] ?? '#' . $rank;
          $initials = mb_strtoupper(mb_substr($student['full_name'], 0, 1, 'UTF-8'), 'UTF-8');
        ?>
        <div class="lb-item <?php echo $isMe ? 'is-me' : ''; ?>">
          <div class="lb-rank <?php echo $rClass; ?>"><?php echo $rLabel; ?></div>
          <div class="lb-avatar"><?php echo $initials; ?></div>
          <div class="lb-info">
            <div class="lb-name">
              <?php echo htmlspecialchars($student['full_name']); ?>
              <?php if ($isMe): ?>
              <span style="font-size:.72rem;color:var(--blue-600);font-weight:600;">(<?php echo t('you') ?: 'You'; ?>)</span>
              <?php endif; ?>
            </div>
            <div class="lb-lvl"><?php echo t('level'); ?> <?php echo (int) $student['current_level']; ?></div>
          </div>
          <div class="lb-xp">
            <i data-lucide="star" width="14" height="14" color="#f59e0b"></i>
            <?php echo number_format((int) $student['xp_points']); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- TAB: ACHIEVEMENTS -->
    <div id="tab-achievements" class="tab-panel">
      <div class="section-head">
        <h3><?php echo t('achievements') ?: 'My Achievements'; ?></h3>
      </div>

      <?php if (empty($achievements)): ?>
      <div class="empty-state">
        <i data-lucide="lock" width="48" height="48"></i>
        <p><?php echo t('no_achievements_yet') ?: 'Complete lessons to unlock achievements!'; ?></p>
      </div>
      <?php else: ?>
      <div class="achievement-grid">
        <?php foreach ($achievements as $ach): ?>
        <div class="achievement-card">
          <div class="achievement-icon" style="background:<?php echo $ach['color']; ?>20;">
            <i data-lucide="<?php echo $ach['icon']; ?>" width="28" height="28" color="<?php echo $ach['color']; ?>"></i>
          </div>
          <h4><?php echo htmlspecialchars($ach['label']); ?></h4>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<script>
  lucide.createIcons();

  // Sidebar hamburger
  const sidebar   = document.getElementById('sidebar');
  const hamburger = document.getElementById('hamburger');
  hamburger.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });

  // Avatar dropdown
  const avatarBtn      = document.getElementById('avatarBtn');
  const avatarDropdown = document.getElementById('avatarDropdown');
  avatarBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    avatarDropdown.classList.toggle('open');
  });
  document.addEventListener('click', () => avatarDropdown.classList.remove('open'));

  // Tab switching
  function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
    btn.classList.add('active');
    if (window.innerWidth <= 768) sidebar.classList.remove('open');
  }

  // Animate XP bar on load
  document.addEventListener('DOMContentLoaded', () => {
    const fill = document.querySelector('.xp-bar-fill');
    if (fill) {
      const target = fill.dataset.width || fill.style.width;
      fill.style.width = '0%';
      setTimeout(() => { fill.style.width = target; }, 300);
    }
  });
</script>

<!-- EDIT 1: XP System JS — must come after lucide.createIcons() -->
<script src="js/xp-system.js"></script>

</body>
</html>
