<?php
/**
 * admin/_layout.php — Shared admin layout: dark sidebar + top bar.
 * Include AFTER admin_auth.php.
 */

$pageTitle  = $pageTitle  ?? 'Admin';
$activePage = $activePage ?? '';

$navItems = [
    ['id' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard',       'href' => 'dashboard.php'],
    ['id' => 'lessons',   'icon' => 'book-open',         'label' => 'Manage Lessons',  'href' => 'manage_lessons.php'],
    ['id' => 'users',     'icon' => 'users',             'label' => 'Manage Users',    'href' => 'manage_users.php'],
    ['id' => 'levels',    'icon' => 'layers',            'label' => 'Grade Levels',    'href' => 'manage_levels.php'],
    ['id' => 'subjects',  'icon' => 'grid-2x2',          'label' => 'Subjects',        'href' => 'manage_subjects.php'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> — Admin</title>
  <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
  <!-- FIXED: site-url meta for xp-system.js absolute endpoint resolution -->
  <meta name="site-url" content="<?php echo SITE_URL; ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

  <style>
    :root {
      --sidebar-w    : 256px;
      --sidebar-bg   : #0d1117;
      --sidebar-border: #21262d;
      --sidebar-text : #8b949e;
      --sidebar-hover: #161b22;
      --sidebar-active-bg: #1f6feb22;
      --sidebar-active-text: #58a6ff;
      --sidebar-active-border: #1f6feb;
      --topbar-h     : 60px;
      --topbar-bg    : #ffffff;
      --topbar-border: #e2e8f0;
      --main-bg      : #f8fafc;
      --card-bg      : #ffffff;
      --card-border  : #e2e8f0;
      --card-shadow  : 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
      --card-radius  : 12px;
      --blue-700     : #1d4ed8;
      --blue-500     : #3b82f6;
      --blue-50      : #eff6ff;
      --blue-100     : #dbeafe;
      --green-500    : #22c55e;
      --red-500      : #ef4444;
      --amber-500    : #f59e0b;
      --purple-500   : #8b5cf6;
      --text-primary : #0f172a;
      --text-secondary: #475569;
      --text-muted   : #94a3b8;
      --font-body    : <?php echo $isRtl ? "'Cairo'" : "'DM Sans'"; ?>, sans-serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-body); background: var(--main-bg); color: var(--text-primary); min-height: 100vh; display: flex; }

    .admin-sidebar {
      width: var(--sidebar-w); background: var(--sidebar-bg);
      border-<?php echo $isRtl ? 'left' : 'right'; ?>: 1px solid var(--sidebar-border);
      display: flex; flex-direction: column;
      position: fixed; top: 0; <?php echo $isRtl ? 'right' : 'left'; ?>: 0;
      height: 100vh; z-index: 200;
      transition: transform .3s cubic-bezier(.4,0,.2,1);
      overflow-y: auto; overflow-x: hidden;
    }
    .sidebar-logo { display: flex; align-items: center; gap: .75rem; padding: 1.25rem 1.25rem 1rem; border-bottom: 1px solid var(--sidebar-border); text-decoration: none; flex-shrink: 0; }
    .logo-icon { width: 36px; height: 36px; background: var(--blue-700); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .logo-text { color: #e6edf3; font-weight: 700; font-size: .95rem; line-height: 1.2; }
    .logo-sub  { color: var(--sidebar-text); font-size: .72rem; font-weight: 400; display: block; }
    .sidebar-section { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #484f58; padding: 1.25rem 1.25rem .4rem; }
    .sidebar-nav { flex: 1; padding: .5rem 0; }
    .nav-item {
      display: flex; align-items: center; gap: .7rem; padding: .55rem 1.25rem;
      color: var(--sidebar-text); text-decoration: none; font-size: .85rem; font-weight: 500;
      border-<?php echo $isRtl ? 'right' : 'left'; ?>: 3px solid transparent;
      transition: all .15s ease; cursor: pointer;
      border-top: none; border-bottom: none; border-<?php echo $isRtl ? 'left' : 'right'; ?>: none;
      background: none; width: 100%; text-align: <?php echo $isRtl ? 'right' : 'left'; ?>; font-family: var(--font-body);
    }
    .nav-item:hover { background: var(--sidebar-hover); color: #c9d1d9; }
    .nav-item.active { background: var(--sidebar-active-bg); color: var(--sidebar-active-text); border-<?php echo $isRtl ? 'right' : 'left'; ?>-color: var(--sidebar-active-border); font-weight: 600; }
    .nav-item .nav-icon { flex-shrink: 0; opacity: .7; }
    .nav-item.active .nav-icon { opacity: 1; }
    .sidebar-footer { border-top: 1px solid var(--sidebar-border); padding: 1rem 1.25rem; flex-shrink: 0; }
    .admin-avatar-row { display: flex; align-items: center; gap: .75rem; }
    .admin-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--blue-700), #7c3aed); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: .85rem; flex-shrink: 0; }
    .admin-name  { color: #c9d1d9; font-size: .82rem; font-weight: 600; }
    .admin-role  { color: var(--sidebar-text); font-size: .7rem; display: block; }
    .logout-btn  { margin-<?php echo $isRtl ? 'right' : 'left'; ?>: auto; background: none; border: none; color: var(--sidebar-text); cursor: pointer; padding: .25rem; transition: color .15s; }
    .logout-btn:hover { color: var(--red-500); }
    .admin-main { <?php echo $isRtl ? 'margin-right' : 'margin-left'; ?>: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .admin-topbar { height: var(--topbar-h); background: var(--topbar-bg); border-bottom: 1px solid var(--topbar-border); display: flex; align-items: center; padding: 0 1.5rem; gap: 1rem; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
    .hamburger-admin { display: none; background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: .3rem; }
    .topbar-title { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
    .topbar-spacer { flex: 1; }
    .topbar-badge { display: inline-flex; align-items: center; gap: .4rem; background: var(--blue-50); color: var(--blue-700); padding: .3rem .75rem; border-radius: 50px; font-size: .78rem; font-weight: 600; border: 1px solid var(--blue-100); }
    .admin-content { padding: 1.75rem; flex: 1; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--card-radius); padding: 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--card-shadow); transition: box-shadow .2s; }
    .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
    .stat-icon-wrap { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-num   { font-size: 1.75rem; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .stat-label { font-size: .78rem; color: var(--text-muted); font-weight: 500; margin-top: .2rem; }
    .section-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--card-radius); box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 1.5rem; }
    .section-card-head { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--card-border); flex-wrap: wrap; gap: .75rem; }
    .section-card-head h2 { font-size: .95rem; font-weight: 700; color: var(--text-primary); }
    .section-card-body { padding: 1.25rem; }
    .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; border-radius: 8px; font-size: .83rem; font-weight: 600; font-family: var(--font-body); cursor: pointer; border: none; transition: all .15s; text-decoration: none; }
    .btn-primary { background: var(--blue-700); color: #fff; box-shadow: 0 2px 8px rgba(29,78,216,.3); }
    .btn-primary:hover { background: #1e40af; }
    .btn-success { background: #16a34a; color: #fff; }
    .btn-success:hover { background: #15803d; }
    .btn-danger  { background: var(--red-500); color: #fff; }
    .btn-danger:hover { background: #dc2626; }
    .btn-warning { background: var(--amber-500); color: #fff; }
    .btn-warning:hover { background: #d97706; }
    .btn-ghost   { background: transparent; color: var(--text-secondary); border: 1px solid var(--card-border); }
    .btn-ghost:hover { background: var(--main-bg); color: var(--text-primary); }
    .btn-sm { padding: .35rem .7rem; font-size: .78rem; border-radius: 6px; }
    .btn-icon { padding: .4rem; border-radius: 6px; }
    .badge-status { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .6rem; border-radius: 50px; font-size: .72rem; font-weight: 600; }
    .badge-active   { background: #dcfce7; color: #15803d; }
    .badge-inactive { background: #fee2e2; color: #b91c1c; }
    .badge-video    { background: var(--blue-100); color: var(--blue-700); }
    .badge-pdf      { background: #fee2e2; color: #b91c1c; }
    .badge-book     { background: #dcfce7; color: #15803d; }
    .badge-admin    { background: #f3e8ff; color: #7c3aed; }
    .badge-student  { background: var(--blue-50); color: var(--blue-700); }
    .modal-overlay { position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,.55); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1rem; opacity: 0; pointer-events: none; transition: opacity .25s ease; }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal-box { background: var(--card-bg); border-radius: 16px; width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 48px rgba(0,0,0,.2); transform: scale(.95) translateY(10px); transition: transform .25s cubic-bezier(.34,1.3,.64,1); }
    .modal-overlay.open .modal-box { transform: scale(1) translateY(0); }
    .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--card-border); position: sticky; top: 0; background: var(--card-bg); z-index: 1; }
    .modal-head h3 { font-size: 1rem; font-weight: 700; }
    .modal-close { background: none; border: none; cursor: pointer; color: var(--text-muted); padding: .25rem; border-radius: 6px; transition: background .15s, color .15s; }
    .modal-close:hover { background: var(--main-bg); color: var(--text-primary); }
    .modal-body { padding: 1.5rem; }
    .modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: .75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--card-border); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-grid .full { grid-column: 1 / -1; }
    .form-group { display: flex; flex-direction: column; gap: .4rem; }
    .form-label { font-size: .82rem; font-weight: 600; color: var(--text-secondary); }
    .form-label span.req { color: var(--red-500); margin-<?php echo $isRtl ? 'right' : 'left'; ?>: .2rem; }
    .form-control { width: 100%; padding: .65rem .9rem; border: 1.5px solid var(--card-border); border-radius: 8px; font-family: var(--font-body); font-size: .875rem; color: var(--text-primary); background: var(--main-bg); transition: border-color .2s, box-shadow .2s; outline: none; }
    .form-control:focus { border-color: var(--blue-500); box-shadow: 0 0 0 3px rgba(59,130,246,.12); background: #fff; }
    .form-control.is-error { border-color: var(--red-500); }
    .form-hint  { font-size: .75rem; color: var(--text-muted); }
    .form-error { font-size: .75rem; color: var(--red-500); }
    textarea.form-control { resize: vertical; min-height: 80px; }
    .lang-tabs { display: flex; gap: 0; margin-bottom: .75rem; }
    .lang-tab { padding: .4rem .85rem; font-size: .78rem; font-weight: 600; border: 1.5px solid var(--card-border); background: var(--main-bg); color: var(--text-muted); cursor: pointer; transition: all .15s; font-family: var(--font-body); }
    .lang-tab:first-child { border-radius: 8px 0 0 8px; }
    .lang-tab:last-child  { border-radius: 0 8px 8px 0; border-left: none; }
    .lang-tab.active { background: var(--blue-700); color: #fff; border-color: var(--blue-700); }
    .lang-panel { display: none; }
    .lang-panel.active { display: grid; gap: .75rem; }
    .activity-list { list-style: none; display: flex; flex-direction: column; gap: 0; }
    .activity-item { display: flex; align-items: flex-start; gap: .85rem; padding: .85rem 0; border-bottom: 1px solid var(--card-border); }
    .activity-item:last-child { border-bottom: none; }
    .activity-dot { width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; margin-top: .1rem; }
    .activity-text { flex: 1; min-width: 0; }
    .activity-text strong { font-size: .875rem; color: var(--text-primary); font-weight: 600; }
    .activity-text p { font-size: .8rem; color: var(--text-muted); margin-top: .1rem; }
    .activity-time { font-size: .72rem; color: var(--text-muted); white-space: nowrap; }
    #admin-toast { position: fixed; bottom: 1.5rem; <?php echo $isRtl ? 'left' : 'right'; ?>: 1.5rem; z-index: 9999; display: flex; flex-direction: column-reverse; gap: .65rem; pointer-events: none; }
    .a-toast { pointer-events: all; display: flex; align-items: center; gap: .75rem; min-width: 260px; max-width: 380px; padding: .875rem 1rem; border-radius: 12px; font-size: .875rem; font-weight: 600; box-shadow: 0 4px 20px rgba(0,0,0,.15); opacity: 0; transform: translateX(120%); transition: opacity .3s ease, transform .35s cubic-bezier(.34,1.4,.64,1); }
    .a-toast.in  { opacity: 1; transform: translateX(0); }
    .a-toast.out { animation: atoast-out .3s ease forwards; }
    @keyframes atoast-out { to { opacity:0; transform:translateX(130%); } }
    .a-toast.success { background: #f0fdf4; color: #15803d; border-left: 4px solid #22c55e; }
    .a-toast.error   { background: #fef2f2; color: #b91c1c; border-left: 4px solid #ef4444; }
    .a-toast.info    { background: var(--blue-50); color: var(--blue-700); border-left: 4px solid var(--blue-500); }
    .dataTables_wrapper .dataTables_filter input { border: 1.5px solid var(--card-border) !important; border-radius: 8px !important; padding: .4rem .75rem !important; font-family: var(--font-body) !important; font-size: .83rem !important; outline: none !important; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--blue-500) !important; box-shadow: 0 0 0 3px rgba(59,130,246,.12) !important; }
    table.dataTable thead th { font-size: .78rem !important; font-weight: 700 !important; text-transform: uppercase !important; letter-spacing: .05em !important; color: var(--text-muted) !important; background: var(--main-bg) !important; border-bottom: 2px solid var(--card-border) !important; white-space: nowrap; }
    table.dataTable tbody td { font-size: .85rem !important; vertical-align: middle !important; }
    table.dataTable tbody tr:hover td { background: var(--blue-50) !important; }
    .page-link { font-size: .8rem !important; }
    @media (max-width: 768px) {
      .admin-sidebar { transform: translateX(<?php echo $isRtl ? '100%' : '-100%'; ?>); }
      .admin-sidebar.open { transform: translateX(0); }
      .admin-main { <?php echo $isRtl ? 'margin-right' : 'margin-left'; ?>: 0; }
      .hamburger-admin { display: flex; }
      .admin-content { padding: 1rem; }
      .form-grid { grid-template-columns: 1fr; }
      .form-grid .full { grid-column: 1; }
      .stat-grid { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>

<aside class="admin-sidebar" id="adminSidebar">
  <a href="dashboard.php" class="sidebar-logo">
    <span class="logo-icon"><i data-lucide="graduation-cap" width="18" height="18" color="white"></i></span>
    <span class="logo-text">EduAdmin <span class="logo-sub">Control Panel</span></span>
  </a>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <?php foreach ($navItems as $item): ?>
    <a href="<?php echo $item['href']; ?>" class="nav-item <?php echo $activePage === $item['id'] ? 'active' : ''; ?>">
      <i data-lucide="<?php echo $item['icon']; ?>" width="16" height="16" class="nav-icon"></i>
      <?php echo $item['label']; ?>
    </a>
    <?php endforeach; ?>
    <div class="sidebar-section" style="margin-top:.5rem;">System</div>
    <a href="../index.php" class="nav-item">
      <i data-lucide="home" width="16" height="16" class="nav-icon"></i> View Site
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-avatar-row">
      <div class="admin-avatar"><?php echo mb_strtoupper(mb_substr($adminUser['full_name'], 0, 1, 'UTF-8'), 'UTF-8'); ?></div>
      <div>
        <div class="admin-name"><?php echo htmlspecialchars($adminUser['full_name']); ?></div>
        <span class="admin-role"><?php echo ucfirst($adminUser['role']); ?></span>
      </div>
      <a href="../logout.php" class="logout-btn" title="Logout">
        <i data-lucide="log-out" width="16" height="16"></i>
      </a>
    </div>
  </div>
</aside>

<div class="admin-main">
  <header class="admin-topbar">
    <button class="hamburger-admin" id="hamburgerAdmin">
      <i data-lucide="menu" width="20" height="20"></i>
    </button>
    <span class="topbar-title"><?php echo htmlspecialchars($pageTitle); ?></span>
    <div class="topbar-spacer"></div>
    <span class="topbar-badge">
      <i data-lucide="shield-check" width="13" height="13"></i>
      <?php echo ucfirst($adminUser['role']); ?>
    </span>
  </header>
  <div class="admin-content">
