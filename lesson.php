<?php
/**
 * lesson.php — Individual Lesson Page with Anti-Cheat System
 * ──────────────────────────────────────────────────────────────
 * Anti-Cheat Features:
 *   - YouTube: Button only enabled when video ends (YT.PlayerState.ENDED)
 *   - PDF/Books: 60-second timer must complete before XP claim
 *   - Server-side: ajax/complete_lesson.php validates no double-claims
 */

declare(strict_types=1);

require_once 'includes/auth_check.php';
require_once 'includes/csrf.php';

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = $dir === 'rtl';
$user        = $currentUser;

// ── Get lesson ────────────────────────────────────────────────
$lessonId = (int) ($_GET['id'] ?? 0);
if ($lessonId <= 0) { redirect(SITE_URL . '/dashboard.php'); }

$lesson = db_row(
    "SELECT l.*,
            s.name_ar AS sub_ar, s.name_fr AS sub_fr, s.name_en AS sub_en,
            s.color   AS sub_color,
            s.level_id
     FROM   lessons  l
     JOIN   subjects s ON l.subject_id = s.id
     WHERE  l.id = ? AND l.is_published = 1",
    [$lessonId]
);

if (!$lesson) { redirect(SITE_URL . '/dashboard.php'); }

// ── Completion status ─────────────────────────────────────────
$progress = db_row(
    "SELECT status FROM lesson_progress WHERE user_id = ? AND lesson_id = ?",
    [(int) $user['id'], $lessonId]
);
$isCompleted = ($progress['status'] ?? '') === 'completed';

// ── Mark as in_progress when first viewed ────────────────────
if (!$isCompleted && ($progress['status'] ?? '') !== 'in_progress') {
    if ($progress) {
        db_run(
            "UPDATE lesson_progress SET status = 'in_progress' WHERE user_id = ? AND lesson_id = ?",
            [(int) $user['id'], $lessonId]
        );
    } else {
        db_run(
            "INSERT INTO lesson_progress (user_id, lesson_id, status) VALUES (?, ?, 'in_progress')",
            [(int) $user['id'], $lessonId]
        );
    }
}

// ── Related lessons ───────────────────────────────────────────
$relatedLessons = db_all(
    "SELECT id, title_ar, title_fr, title_en, content_type, duration_minutes
     FROM   lessons
     WHERE  subject_id = ? AND id != ? AND is_published = 1
     ORDER  BY display_order ASC
     LIMIT  5",
    [(int) $lesson['subject_id'], $lessonId]
);

// ── Build embed URL for YouTube ───────────────────────────────
$isYouTube = isYouTubeUrl($lesson['url']);
$isPdf     = $lesson['content_type'] === 'pdf';
$isBook    = $lesson['content_type'] === 'book';

// Extract YouTube video ID
$youtubeVideoId = '';
if ($isYouTube) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
    if (preg_match($pattern, $lesson['url'], $matches)) {
        $youtubeVideoId = $matches[1];
    }
}

// ── Language-specific fields ──────────────────────────────────
$titleKey  = 'title_' . $currentLang;
$descKey   = 'description_' . $currentLang;
$subKey    = 'sub_' . $currentLang;

$title    = htmlspecialchars($lesson[$titleKey]  ?? $lesson['title_ar']);
$desc     = nl2br(htmlspecialchars($lesson[$descKey] ?? ''));
$subName  = htmlspecialchars($lesson[$subKey] ?? '');
$subColor = $lesson['sub_color'] ?? '#1d4ed8';

// ── XP data ───────────────────────────────────────────────────
$xpProgress  = getXPProgress((int) $user['xp_points']);
$nextLevelXP = getXPForNextLevel((int) $user['xp_points']);
$xpToGo      = $nextLevelXP - (int) $user['xp_points'];

$csrfToken = getCsrfToken();
$pageTitle = $title . ' — ' . t('app_name');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
  <meta name="csrf-token" content="<?php echo $csrfToken; ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="css/xp-animations.css">

  <style>
    :root {
      --blue-950: #0f1f5c;
      --blue-900: #1e3a8a;
      --blue-700: #1d4ed8;
      --blue-500: #3b82f6;
      --blue-100: #dbeafe;
      --blue-50:  #eff6ff;
      --white:    #ffffff;
      --gray-50:  #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-500: #64748b;
      --gray-700: #334155;
      --gray-900: #0f172a;
      --radius: 12px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: <?php echo $isRtl ? "'Cairo'" : "'Plus Jakarta Sans'"; ?>, sans-serif;
      background: var(--gray-100);
      color: var(--gray-800, #1e293b);
      min-height: 100vh;
    }

    /* ── Top Nav ── */
    .topnav {
      background: linear-gradient(90deg, var(--blue-950), var(--blue-700));
      height: 64px;
      display: flex;
      align-items: center;
      padding: 0 1.5rem;
      gap: 1rem;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 20px rgba(0,0,0,.3);
    }
    .topnav-brand {
      display: flex; align-items: center; gap: .6rem;
      color: #fff; text-decoration: none; font-weight: 700; font-size: 1.05rem;
    }
    .topnav-spacer { flex: 1; }
    .xp-pill {
      display: flex; align-items: center; gap: .5rem;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 50px;
      padding: .35rem .85rem;
      color: #fff; font-size: .82rem; font-weight: 600;
    }
    .xp-num { color: #fde68a; font-weight: 700; }
    .level-badge {
      display: inline-flex; align-items: center; gap: .3rem;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: #fff; padding: .3rem .7rem;
      border-radius: 50px; font-size: .78rem; font-weight: 700;
    }
    .back-btn {
      display: flex; align-items: center; gap: .4rem;
      color: rgba(255,255,255,.8); text-decoration: none;
      font-size: .875rem; transition: color .2s;
    }
    .back-btn:hover { color: #fff; }

    /* ── Layout ── */
    .layout {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1.75rem 1rem;
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 1.5rem;
      align-items: start;
    }

    .breadcrumb {
      display: flex; align-items: center; gap: .5rem;
      font-size: .8rem; color: var(--gray-500);
      margin-bottom: 1rem;
    }
    .breadcrumb a { color: var(--blue-700); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }

    /* ── Media container ── */
    .media-wrap {
      background: #000;
      border-radius: 16px;
      overflow: hidden;
      position: relative;
      margin-bottom: 1.25rem;
      box-shadow: 0 8px 32px rgba(0,0,0,.25);
    }

    .video-responsive {
      position: relative;
      padding-bottom: 56.25%;
      height: 0;
      overflow: hidden;
    }
    .video-responsive iframe {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }

    /* PDF viewer with timer overlay */
    .pdf-viewer-wrap {
      position: relative;
      background: linear-gradient(135deg, var(--blue-900), var(--blue-700));
      padding: 2.5rem;
      text-align: center;
      color: #fff;
      min-height: 400px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .pdf-timer-overlay {
      background: rgba(0,0,0,.85);
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 10;
      backdrop-filter: blur(4px);
    }
    .pdf-timer-overlay.hidden { display: none; }
    .timer-circle {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: rgba(255,255,255,.1);
      border: 4px solid rgba(255,255,255,.3);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      font-weight: 900;
      color: #fde68a;
      margin-bottom: 1rem;
    }
    .timer-text {
      font-size: 1.1rem;
      color: rgba(255,255,255,.8);
    }

    .resource-card {
      background: linear-gradient(135deg, var(--blue-900), var(--blue-700));
      padding: 2.5rem;
      text-align: center;
      color: #fff;
    }
    .resource-card .rc-icon {
      font-size: 3.5rem;
      margin-bottom: 1rem;
      display: block;
    }
    .resource-card h3 { font-size: 1.3rem; font-weight: 700; margin-bottom: .5rem; }
    .resource-card p { color: rgba(255,255,255,.75); margin-bottom: 1.5rem; font-size: .9rem; }
    .resource-card .btn-open {
      display: inline-flex; align-items: center; gap: .5rem;
      background: #fff; color: var(--blue-700);
      padding: .75rem 1.75rem; border-radius: 50px;
      font-weight: 700; text-decoration: none; font-size: .95rem;
      transition: transform .15s, box-shadow .15s;
      box-shadow: 0 4px 16px rgba(0,0,0,.2);
    }
    .resource-card .btn-open:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,0,0,.25);
    }

    /* ── Lesson Info Card ── */
    .lesson-info {
      background: var(--white);
      border-radius: var(--radius);
      padding: 1.5rem;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
      margin-bottom: 1rem;
    }
    .lesson-subject-pill {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .25rem .75rem; border-radius: 50px;
      font-size: .78rem; font-weight: 700;
      margin-bottom: .75rem;
    }
    .lesson-info h1 {
      font-size: 1.4rem; font-weight: 800;
      color: var(--gray-900); margin-bottom: .75rem; line-height: 1.35;
    }
    .lesson-meta-row {
      display: flex; align-items: center; gap: 1.25rem;
      font-size: .8rem; color: var(--gray-500);
      margin-bottom: 1rem; flex-wrap: wrap;
    }
    .lesson-meta-row span {
      display: flex; align-items: center; gap: .35rem;
    }
    .lesson-description {
      font-size: .9rem; line-height: 1.7; color: var(--gray-700);
      border-top: 1px solid var(--gray-100);
      padding-top: 1rem;
    }

    /* ── XP Progress Card ── */
    .xp-progress-card {
      background: linear-gradient(135deg, var(--blue-900), var(--blue-700));
      border-radius: var(--radius);
      padding: 1.25rem;
      color: #fff;
      margin-bottom: 1rem;
    }
    .xp-progress-card .xpc-label {
      font-size: .78rem;
      color: rgba(255,255,255,.7);
      margin-bottom: .3rem;
    }
    .xp-progress-card .xpc-num {
      font-size: 1.75rem;
      font-weight: 900;
      color: #fde68a;
      margin-bottom: .6rem;
    }
    .xp-progress-card .xpc-sub {
      font-size: .75rem;
      color: rgba(255,255,255,.6);
      margin-top: .4rem;
    }

    /* ── Complete Button ── */
    .complete-btn-wrap { margin-bottom: 1rem; }

    .btn-complete-lesson {
      display: flex; align-items: center; justify-content: center; gap: .6rem;
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #16a34a, #22c55e);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 16px rgba(22,163,74,.4);
      transition: transform .15s, box-shadow .15s, opacity .15s;
    }
    .btn-complete-lesson:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(22,163,74,.45);
    }
    .btn-complete-lesson:active:not(:disabled) { transform: translateY(0); }
    .btn-complete-lesson:disabled {
      background: #94a3b8;
      color: #fff;
      box-shadow: none;
      cursor: not-allowed;
      opacity: 0.6;
    }
    .btn-complete-lesson.already-done {
      background: #f0fdf4;
      color: #15803d;
      border: 1.5px solid #86efac;
      box-shadow: none;
      cursor: default;
    }

    /* ── Sidebar ── */
    .related-card {
      background: var(--white);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .related-card .rc-head {
      padding: 1rem 1.25rem;
      border-bottom: 1px solid var(--gray-100);
      font-weight: 700;
      font-size: .9rem;
      color: var(--gray-900);
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .related-item {
      display: flex; align-items: center; gap: .75rem;
      padding: .85rem 1.25rem;
      text-decoration: none;
      color: var(--gray-700);
      border-bottom: 1px solid var(--gray-50);
      transition: background .15s;
      font-size: .875rem;
    }
    .related-item:last-child { border-bottom: none; }
    .related-item:hover { background: var(--blue-50); color: var(--blue-700); }
    .related-item-icon {
      width: 36px; height: 36px; flex-shrink: 0;
      border-radius: 8px;
      background: var(--blue-50);
      display: flex; align-items: center; justify-content: center;
    }
    .related-item-info { flex: 1; min-width: 0; }
    .related-item-info strong {
      display: block;
      font-size: .83rem;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .related-item-info span {
      font-size: .73rem;
      color: var(--gray-500);
    }

    @media (max-width: 900px) {
      .layout { grid-template-columns: 1fr; }
      .lesson-sidebar { order: -1; }
    }
  </style>
</head>
<body>

<!-- ── Top Nav ── -->
<nav class="topnav">
  <a href="dashboard.php" class="back-btn">
    <i data-lucide="arrow-<?php echo $isRtl ? 'right' : 'left'; ?>" width="16" height="16"></i>
    <?php echo t('dashboard'); ?>
  </a>
  <a href="/" class="topnav-brand">
    <i data-lucide="graduation-cap" width="20" height="20" color="white"></i>
    <?php echo t('app_name'); ?>
  </a>
  <div class="topnav-spacer"></div>
  <div class="level-badge">
    <i data-lucide="star" width="13" height="13"></i>
    <?php echo t('level'); ?> <span data-level-display><?php echo (int) $user['current_level']; ?></span>
  </div>
  <div class="xp-pill">
    <i data-lucide="zap" width="14" height="14"></i>
    <span class="xp-num" data-xp-counter><?php echo number_format((int) $user['xp_points']); ?></span>
    XP
  </div>
</nav>

<!-- ── Main Layout ── -->
<div class="layout">

  <!-- LEFT: Lesson content -->
  <div class="lesson-main">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="dashboard.php"><?php echo t('dashboard'); ?></a>
      <i data-lucide="chevron-<?php echo $isRtl ? 'left' : 'right'; ?>" width="14" height="14"></i>
      <span style="color:<?php echo $subColor; ?>;"><?php echo $subName; ?></span>
      <i data-lucide="chevron-<?php echo $isRtl ? 'left' : 'right'; ?>" width="14" height="14"></i>
      <span><?php echo $title; ?></span>
    </div>

    <!-- Media -->
    <div class="media-wrap">
      <?php if ($isYouTube && $youtubeVideoId): ?>
      <!-- YouTube Embed with Anti-Cheat -->
      <div class="video-responsive">
        <div id="player"></div>
      </div>

      <?php elseif ($isPdf || $isBook): ?>
      <!-- PDF/Book with 60-second Timer -->
      <div class="pdf-viewer-wrap" id="pdfViewer">
        <!-- Timer Overlay -->
        <div class="pdf-timer-overlay" id="pdfTimerOverlay">
          <div class="timer-circle" id="timerDisplay">60</div>
          <div class="timer-text">⏱️ Please wait 60 seconds to unlock XP claim...</div>
        </div>

        <!-- PDF Content -->
        <span class="rc-icon">
          <?php if ($isPdf): ?>📄<?php else: ?>📚<?php endif; ?>
        </span>
        <h3><?php echo $title; ?></h3>
        <p><?php echo $isPdf ? t('pdf') : t('book'); ?> — <?php echo $subName; ?></p>
        <a href="<?php echo htmlspecialchars($lesson['url']); ?>"
           target="_blank"
           rel="noopener noreferrer"
           class="btn-open"
           id="pdfOpenBtn">
          <i data-lucide="external-link" width="16" height="16"></i>
          <?php echo $isPdf ? 'Open PDF' : 'Open Book'; ?>
        </a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Lesson Info -->
    <div class="lesson-info">
      <div class="lesson-subject-pill"
           style="background:<?php echo $subColor; ?>18;color:<?php echo $subColor; ?>;">
        <i data-lucide="book-marked" width="13" height="13"></i>
        <?php echo $subName; ?>
      </div>

      <h1><?php echo $title; ?></h1>

      <div class="lesson-meta-row">
        <span>
          <i data-lucide="clock" width="14" height="14"></i>
          <?php echo (int) $lesson['duration_minutes']; ?> <?php echo t('minutes'); ?>
        </span>
        <span>
          <i data-lucide="zap" width="14" height="14"></i>
          <?php echo (int) $lesson['xp_reward']; ?> XP
        </span>
        <span>
          <?php if ($lesson['content_type'] === 'video'): ?>
          <i data-lucide="play-circle" width="14" height="14"></i> <?php echo t('video'); ?>
          <?php elseif ($lesson['content_type'] === 'pdf'): ?>
          <i data-lucide="file-text" width="14" height="14"></i> PDF
          <?php else: ?>
          <i data-lucide="book-open" width="14" height="14"></i> <?php echo t('book'); ?>
          <?php endif; ?>
        </span>
      </div>

      <?php if ($desc): ?>
      <div class="lesson-description"><?php echo $desc; ?></div>
      <?php endif; ?>
    </div>

  </div>

  <!-- RIGHT: Sidebar -->
  <aside class="lesson-sidebar">

    <!-- XP Progress -->
    <div class="xp-progress-card">
      <div class="xpc-label"><?php echo t('my_xp'); ?></div>
      <div class="xpc-num" data-xp-counter><?php echo number_format((int) $user['xp_points']); ?></div>
      <div class="xp-bar-track">
        <div class="xp-bar-fill" style="width:<?php echo $xpProgress; ?>%" data-width="<?php echo $xpProgress; ?>%"></div>
      </div>
      <div class="xpc-sub" data-xp-bar-label>
        <?php echo number_format((int) $user['xp_points']); ?> XP • <?php echo round($xpProgress); ?>%
        <?php echo t('to_next_level'); ?>
      </div>
    </div>

    <!-- Complete Lesson Button -->
    <div class="complete-btn-wrap">
      <?php if ($isCompleted): ?>
      <button class="btn-complete-lesson already-done" disabled>
        <i data-lucide="check-circle-2" width="20" height="20"></i>
        <?php echo t('lesson_completed'); ?>
      </button>

      <?php else: ?>
      <button
        class="btn-complete-lesson"
        id="completeBtn"
        data-complete-lesson
        data-lesson-id="<?php echo $lessonId; ?>"
        data-xp-reward="<?php echo (int) $lesson['xp_reward']; ?>"
        disabled>
        <i data-lucide="zap" width="20" height="20"></i>
        <span id="completeBtnText"><?php echo t('complete_lesson'); ?> (+<?php echo (int) $lesson['xp_reward']; ?> XP)</span>
      </button>
      <?php endif; ?>
    </div>

    <!-- Related Lessons -->
    <?php if (!empty($relatedLessons)): ?>
    <div class="related-card">
      <div class="rc-head">
        <i data-lucide="list" width="16" height="16" color="<?php echo $subColor; ?>"></i>
        <?php echo t('subjects'); ?> — <?php echo t('lessons'); ?>
      </div>
      <?php
      $typeIcons = ['video' => 'play-circle', 'pdf' => 'file-text', 'book' => 'book-open'];
      foreach ($relatedLessons as $rel):
        $rTitle = htmlspecialchars($rel['title_' . $currentLang] ?? $rel['title_ar']);
        $rIcon  = $typeIcons[$rel['content_type']] ?? 'file';
      ?>
      <a href="lesson.php?id=<?php echo (int) $rel['id']; ?>" class="related-item">
        <div class="related-item-icon">
          <i data-lucide="<?php echo $rIcon; ?>" width="16" height="16" color="<?php echo $subColor; ?>"></i>
        </div>
        <div class="related-item-info">
          <strong><?php echo $rTitle; ?></strong>
          <span><?php echo (int) $rel['duration_minutes']; ?> <?php echo t('minutes'); ?></span>
        </div>
        <i data-lucide="chevron-<?php echo $isRtl ? 'left' : 'right'; ?>" width="14" height="14" color="#94a3b8"></i>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </aside>
</div><!-- /layout -->

<script>lucide.createIcons();</script>

<?php if ($isYouTube && $youtubeVideoId && !$isCompleted): ?>
<!-- YouTube IFrame API Anti-Cheat -->
<script>
  // Load YouTube IFrame API
  var tag = document.createElement('script');
  tag.src = "https://www.youtube.com/iframe_api";
  var firstScriptTag = document.getElementsByTagName('script')[0];
  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

  var player;
  var videoEnded = false;
  var completeBtn = document.getElementById('completeBtn');

  function onYouTubeIframeAPIReady() {
    player = new YT.Player('player', {
      height: '100%',
      width: '100%',
      videoId: '<?php echo $youtubeVideoId; ?>',
      playerVars: {
        'rel': 0,
        'modestbranding': 1,
        'playsinline': 1
      },
      events: {
        'onStateChange': onPlayerStateChange
      }
    });
  }

  function onPlayerStateChange(event) {
    // YT.PlayerState.ENDED = 0
    if (event.data === YT.PlayerState.ENDED) {
      videoEnded = true;
      completeBtn.disabled = false;
      completeBtn.style.background = 'linear-gradient(135deg, #16a34a, #22c55e)';
      
      // Flash animation
      completeBtn.style.animation = 'pulse 1s ease-in-out 3';
      
      // Show toast
      if (typeof Toast !== 'undefined') {
        Toast.show('success', '✅ Video completed! You can now claim your XP.', 5000);
      }
    }
  }

  // Add pulse animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); box-shadow: 0 0 20px rgba(34,197,94,.6); }
    }
  `;
  document.head.appendChild(style);
</script>

<?php elseif (($isPdf || $isBook) && !$isCompleted): ?>
<!-- PDF/Book 60-Second Timer Anti-Cheat -->
<script>
  (function() {
    let timeLeft = 60;
    const timerDisplay = document.getElementById('timerDisplay');
    const timerOverlay = document.getElementById('pdfTimerOverlay');
    const completeBtn = document.getElementById('completeBtn');
    const pdfOpenBtn = document.getElementById('pdfOpenBtn');

    // Track if PDF was opened
    let pdfOpened = false;
    pdfOpenBtn.addEventListener('click', function() {
      pdfOpened = true;
    });

    const countdown = setInterval(function() {
      timeLeft--;
      timerDisplay.textContent = timeLeft;

      if (timeLeft <= 0) {
        clearInterval(countdown);
        
        // Hide overlay
        timerOverlay.classList.add('hidden');
        
        // Enable complete button
        completeBtn.disabled = false;
        completeBtn.style.background = 'linear-gradient(135deg, #16a34a, #22c55e)';
        
        // Show toast
        if (typeof Toast !== 'undefined') {
          Toast.show('success', '✅ Timer completed! You can now claim your XP.', 5000);
        }

        // Flash animation
        completeBtn.style.animation = 'pulse 1s ease-in-out 3';
      }
    }, 1000);

    // Prevent skipping by reloading
    window.addEventListener('beforeunload', function(e) {
      if (timeLeft > 0 && !completeBtn.disabled) {
        return;
      }
    });
  })();
</script>
<?php endif; ?>

<!-- XP System JS -->
<script src="js/xp-system.js"></script>

</body>
</html>
