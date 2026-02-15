<?php
/**
 * login.php — Student / Admin Login
 * Theme : Professional Blue & White  |  RTL support
 */

declare(strict_types=1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle language switch
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    redirect('login.php');
}

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = $dir === 'rtl';

// Already logged in
if (isLoggedIn()) {
    // Route based on role
    if (in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin', 'staff'], true)) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/dashboard.php');
    }
}

// ── Flash from registration ───────────────────────────────────
$flash = getFlashMessage();

// ── Handle POST ───────────────────────────────────────────────
$error    = '';
$formData = ['identifier' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');  // username OR email
    $password   = $_POST['password'] ?? '';
    $formData['identifier'] = $identifier;

    if (empty($identifier) || empty($password)) {
        $error = t('fill_all_fields');
    } else {
        $user = db_row(
            'SELECT id, username, email, password, full_name, role,
                    level_id, xp_points, current_level, preferred_language, is_active
             FROM   users
             WHERE  username = ? OR email = ?
             LIMIT  1',
            [$identifier, $identifier]
        );

        if ($user && verifyPassword($password, $user['password'])) {
            if (!(bool) $user['is_active']) {
                $error = t('account_disabled');
            } else {
                // ── Successful login ───────────────────────────
                session_regenerate_id(true);      // prevent session fixation

                $_SESSION['user_id']   = (int) $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['lang']      = $user['preferred_language'];

                // Stamp last_login
                db_run('UPDATE users SET last_login = NOW() WHERE id = ?', [(int) $user['id']]);

                // Route based on role
                if (in_array($user['role'], ['admin', 'super_admin', 'staff'], true)) {
                    $redirect = SITE_URL . '/admin/dashboard.php';
                } else {
                    $redirect = SITE_URL . '/dashboard.php';
                }

                // Respect ?redirect= hint from auth_check
                if (!empty($_GET['redirect'])) {
                    $safe = filter_var(urldecode($_GET['redirect']), FILTER_SANITIZE_URL);
                    // Only allow relative paths on the same site
                    if (str_starts_with($safe, '/') && !str_starts_with($safe, '//')) {
                        $redirect = SITE_URL . $safe;
                    }
                }

                redirect($redirect);
            }
        } else {
            $error = t('invalid_credentials');
        }
    }
}

$pageTitle = t('login') . ' — ' . t('app_name');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <style>
    :root {
      --blue-950: #0f1f5c;
      --blue-900: #1e3a8a;
      --blue-700: #1d4ed8;
      --blue-500: #3b82f6;
      --blue-100: #dbeafe;
      --white:    #ffffff;
      --gray-50:  #f8fafc;
      --gray-100: #f1f5f9;
      --gray-300: #cbd5e1;
      --gray-500: #64748b;
      --gray-700: #334155;
      --gray-900: #0f172a;
      --red-500:  #ef4444;
      --red-50:   #fef2f2;
      --green-500:#22c55e;
      --green-50: #f0fdf4;
      --radius:   12px;
      --shadow-lg: 0 20px 60px rgba(30,58,138,.15);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: <?php echo $isRtl ? "'Cairo'" : "'Plus Jakarta Sans'"; ?>, sans-serif;
      background: var(--blue-950);
      background-image:
        radial-gradient(ellipse 80% 50% at 50% -20%, rgba(59,130,246,.35) 0%, transparent 60%),
        radial-gradient(ellipse 60% 40% at 80% 110%, rgba(29,78,216,.25) 0%, transparent 50%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    .lang-bar {
      width: 100%;
      max-width: 440px;
      display: flex;
      justify-content: <?php echo $isRtl ? 'flex-start' : 'flex-end'; ?>;
      gap: .5rem;
      margin-bottom: 1.25rem;
    }
    .lang-btn {
      padding: .3rem .75rem;
      border-radius: 6px;
      font-size: .8rem;
      font-weight: 600;
      text-decoration: none;
      border: 1.5px solid rgba(255,255,255,.25);
      color: rgba(255,255,255,.7);
      transition: all .2s;
    }
    .lang-btn:hover { background: rgba(255,255,255,.15); color: #fff; }
    .lang-btn.active { background: var(--blue-500); border-color: var(--blue-500); color: #fff; }

    .card {
      width: 100%;
      max-width: 440px;
      background: var(--white);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
    }

    .card-header {
      background: linear-gradient(135deg, var(--blue-900) 0%, var(--blue-700) 100%);
      padding: 2.25rem 2.5rem 2rem;
      text-align: center;
      position: relative;
    }
    .card-header::before {
      content: '';
      position: absolute;
      top: -30px; right: -30px;
      width: 120px; height: 120px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .card-header::after {
      content: '';
      position: absolute;
      bottom: -20px; left: -20px;
      width: 80px; height: 80px;
      border-radius: 50%;
      background: rgba(255,255,255,.04);
    }

    .logo-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 68px; height: 68px;
      background: rgba(255,255,255,.15);
      border-radius: 50%;
      margin-bottom: .9rem;
      position: relative; z-index: 1;
      border: 2px solid rgba(255,255,255,.2);
    }
    .card-header h1 { font-size: 1.6rem; font-weight: 700; color: #fff; position: relative; z-index: 1; }
    .card-header p  { color: rgba(255,255,255,.7); font-size: .9rem; margin-top: .35rem; position: relative; z-index: 1; }

    .card-body { padding: 2rem 2.5rem 2.5rem; }

    .alert {
      padding: .875rem 1rem;
      border-radius: var(--radius);
      margin-bottom: 1.25rem;
      font-size: .875rem;
      display: flex;
      align-items: center;
      gap: .6rem;
    }
    .alert-error   { background: var(--red-50);   color: #b91c1c; border-<?php echo $isRtl ? 'right' : 'left'; ?>: 4px solid var(--red-500); }
    .alert-success { background: var(--green-50); color: #15803d; border-<?php echo $isRtl ? 'right' : 'left'; ?>: 4px solid var(--green-500); }

    .field { display: flex; flex-direction: column; gap: .4rem; margin-bottom: 1.1rem; }
    .field label { font-size: .85rem; font-weight: 600; color: var(--gray-700); }

    .input-wrap { position: relative; display: flex; align-items: center; }
    .input-icon {
      position: absolute;
      <?php echo $isRtl ? 'right' : 'left'; ?>: .9rem;
      color: var(--gray-500);
      pointer-events: none;
      display: flex;
    }
    .input-wrap input {
      width: 100%;
      padding: .8rem 1rem;
      padding-<?php echo $isRtl ? 'right' : 'left'; ?>: 2.7rem;
      border: 1.5px solid var(--gray-300);
      border-radius: var(--radius);
      font-family: inherit;
      font-size: .9rem;
      color: var(--gray-900);
      background: var(--gray-50);
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }
    .input-wrap input:focus {
      border-color: var(--blue-500);
      box-shadow: 0 0 0 3px rgba(59,130,246,.15);
      background: #fff;
    }

    /* Password show/hide toggle */
    .pwd-toggle {
      position: absolute;
      <?php echo $isRtl ? 'left' : 'right'; ?>: .9rem;
      color: var(--gray-500);
      cursor: pointer;
      display: flex;
      background: none;
      border: none;
      padding: 0;
    }
    .pwd-toggle:hover { color: var(--blue-700); }
    #password { padding-<?php echo $isRtl ? 'left' : 'right'; ?>: 2.7rem; }

    .btn-primary {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      width: 100%;
      padding: .9rem;
      background: linear-gradient(135deg, var(--blue-700) 0%, var(--blue-500) 100%);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: transform .15s, box-shadow .15s;
      box-shadow: 0 4px 15px rgba(29,78,216,.35);
      margin-top: .5rem;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(29,78,216,.45); }
    .btn-primary:active { transform: translateY(0); }

    .card-footer {
      text-align: center;
      padding: 1.25rem 2.5rem;
      border-top: 1px solid var(--gray-100);
      color: var(--gray-500);
      font-size: .875rem;
    }
    .card-footer a { color: var(--blue-700); font-weight: 600; text-decoration: none; }
    .card-footer a:hover { text-decoration: underline; }

    .back-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .4rem;
      color: rgba(255,255,255,.6);
      text-decoration: none;
      font-size: .85rem;
      margin-top: 1.25rem;
      transition: color .2s;
    }
    .back-link:hover { color: #fff; }
  </style>
</head>
<body>

  <div class="lang-bar">
    <?php foreach (['ar' => 'AR', 'fr' => 'FR', 'en' => 'EN'] as $code => $label): ?>
    <a href="?lang=<?php echo $code; ?>"
       class="lang-btn <?php echo $currentLang === $code ? 'active' : ''; ?>">
      <?php echo $label; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card">

    <div class="card-header">
      <div class="logo-wrap">
        <i data-lucide="book-open-check" width="32" height="32" color="white"></i>
      </div>
      <h1><?php echo t('app_name'); ?></h1>
      <p><?php echo t('login'); ?></p>
    </div>

    <div class="card-body">

      <?php if ($flash && $flash['type'] === 'success'): ?>
      <div class="alert alert-success">
        <i data-lucide="check-circle-2" width="18" height="18"></i>
        <?php echo htmlspecialchars($flash['text']); ?>
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <i data-lucide="alert-circle" width="18" height="18"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>

        <div class="field">
          <label for="identifier"><?php echo t('username'); ?> / <?php echo t('email'); ?></label>
          <div class="input-wrap">
            <span class="input-icon"><i data-lucide="user" width="16" height="16"></i></span>
            <input
              type="text"
              id="identifier"
              name="identifier"
              autocomplete="username"
              value="<?php echo htmlspecialchars($formData['identifier']); ?>"
              placeholder="<?php echo t('username'); ?>"
              required>
          </div>
        </div>

        <div class="field">
          <label for="password"><?php echo t('password'); ?></label>
          <div class="input-wrap">
            <span class="input-icon"><i data-lucide="lock" width="16" height="16"></i></span>
            <input
              type="password"
              id="password"
              name="password"
              autocomplete="current-password"
              placeholder="••••••"
              required>
            <button type="button" class="pwd-toggle" id="togglePwd" aria-label="Toggle password">
              <i data-lucide="eye" width="16" height="16" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary">
          <i data-lucide="log-in" width="18" height="18"></i>
          <?php echo t('login'); ?>
        </button>

      </form>
    </div>

    <div class="card-footer">
      <?php echo t('no_account'); ?>
      <a href="register.php"><?php echo t('register'); ?></a>
    </div>

  </div>

  <a href="index.php" class="back-link">
    <i data-lucide="arrow-<?php echo $isRtl ? 'right' : 'left'; ?>" width="14" height="14"></i>
    <?php echo t('back_to_home'); ?>
  </a>

<script>
  lucide.createIcons();

  // Password toggle
  const pwd    = document.getElementById('password');
  const toggle = document.getElementById('togglePwd');
  const icon   = document.getElementById('eyeIcon');

  toggle.addEventListener('click', () => {
    const hidden = pwd.type === 'password';
    pwd.type = hidden ? 'text' : 'password';
    icon.setAttribute('data-lucide', hidden ? 'eye-off' : 'eye');
    lucide.createIcons();
  });
</script>
</body>
</html>