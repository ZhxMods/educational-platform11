<?php
/**
 * register.php — Student Registration
 * Theme  : Professional Blue & White
 * RTL    : Full Arabic support
 */

declare(strict_types=1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle language switch
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    redirect('register.php');
}

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = $dir === 'rtl';

// Already logged in → dashboard
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
}

// ── Fetch grade levels ────────────────────────────────────────
$levels = db_all('SELECT id, name_ar, name_fr, name_en FROM levels ORDER BY display_order ASC');

// ── Handle POST ───────────────────────────────────────────────
$errors   = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email'     => sanitize($_POST['email']     ?? ''),
        'username'  => sanitize($_POST['username']  ?? ''),
        'level_id'  => (int) ($_POST['level_id']   ?? 0),
    ];
    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // ── Validation ────────────────────────────────────────────
    if (empty($formData['full_name'])) {
        $errors['full_name'] = t('fill_all_fields');
    }
    if (empty($formData['username']) || strlen($formData['username']) < 3) {
        $errors['username'] = t('fill_all_fields');
    }
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = t('invalid_email');
    }
    if ($formData['level_id'] <= 0) {
        $errors['level_id'] = t('select_level');
    }
    if (strlen($password) < 6) {
        $errors['password'] = t('password_too_short');
    }
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = t('passwords_not_match');
    }

    if (empty($errors)) {
        // ── Uniqueness check ──────────────────────────────────
        $existing = db_row(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [$formData['username'], $formData['email']]
        );

        if ($existing) {
            $errors['general'] = t('username_or_email_exists');
        } else {
            // ── Insert ─────────────────────────────────────────
            db_run(
                'INSERT INTO users
                     (username, email, password, full_name, role, level_id,
                      xp_points, current_level, preferred_language, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?, 1)',
                [
                    $formData['username'],
                    $formData['email'],
                    hashPassword($password),
                    $formData['full_name'],
                    'student',
                    $formData['level_id'],
                    $currentLang,
                ]
            );

            $userId = (int) db_last_id();

            // ── Start session ──────────────────────────────────
            $_SESSION['user_id']   = $userId;
            $_SESSION['username']  = $formData['username'];
            $_SESSION['user_role'] = 'student';
            $_SESSION['lang']      = $currentLang;

            setFlashMessage('success', t('registration_success'));
            redirect(SITE_URL . '/dashboard.php');
        }
    }
}

$pageTitle = t('register') . ' — ' . t('app_name');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

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
      --gray-300: #cbd5e1;
      --gray-500: #64748b;
      --gray-700: #334155;
      --gray-900: #0f172a;
      --red-500:  #ef4444;
      --red-50:   #fef2f2;
      --green-500:#22c55e;
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
      justify-content: flex-start;
      padding: 2rem 1rem;
    }

    /* ── Language bar ── */
    .lang-bar {
      width: 100%;
      max-width: 520px;
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
    .lang-btn:hover, .lang-btn.active {
      background: rgba(255,255,255,.15);
      color: #fff;
      border-color: rgba(255,255,255,.5);
    }
    .lang-btn.active { background: var(--blue-500); border-color: var(--blue-500); color: #fff; }

    /* ── Card ── */
    .card {
      width: 100%;
      max-width: 520px;
      background: var(--white);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
    }

    .card-header {
      background: linear-gradient(135deg, var(--blue-900) 0%, var(--blue-700) 100%);
      padding: 2rem 2.5rem;
      text-align: center;
      position: relative;
    }
    .card-header::after {
      content: '';
      position: absolute;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      pointer-events: none;
    }

    .logo-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 64px; height: 64px;
      background: rgba(255,255,255,.15);
      border-radius: 50%;
      margin-bottom: 1rem;
      position: relative; z-index: 1;
    }
    .logo-wrap i { color: #fff; }

    .card-header h1 {
      font-size: 1.6rem;
      font-weight: 700;
      color: #fff;
      position: relative; z-index: 1;
    }
    .card-header p {
      color: rgba(255,255,255,.7);
      font-size: .9rem;
      margin-top: .35rem;
      position: relative; z-index: 1;
    }

    .card-body { padding: 2rem 2.5rem 2.5rem; }

    /* ── Alerts ── */
    .alert {
      padding: .875rem 1rem;
      border-radius: var(--radius);
      margin-bottom: 1.25rem;
      font-size: .9rem;
      display: flex;
      align-items: center;
      gap: .6rem;
    }
    .alert-error { background: var(--red-50); color: #b91c1c; border-<?php echo $isRtl ? 'right' : 'left'; ?>: 4px solid var(--red-500); }

    /* ── Form ── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-grid .full { grid-column: 1 / -1; }

    .field { display: flex; flex-direction: column; gap: .4rem; }
    .field label {
      font-size: .85rem;
      font-weight: 600;
      color: var(--gray-700);
    }

    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-icon {
      position: absolute;
      <?php echo $isRtl ? 'right' : 'left'; ?>: .9rem;
      color: var(--gray-500);
      pointer-events: none;
      display: flex;
    }
    .input-wrap input,
    .input-wrap select {
      width: 100%;
      padding: .72rem 1rem;
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
    .input-wrap input:focus,
    .input-wrap select:focus {
      border-color: var(--blue-500);
      box-shadow: 0 0 0 3px rgba(59,130,246,.15);
      background: #fff;
    }
    .input-wrap input.is-error,
    .input-wrap select.is-error {
      border-color: var(--red-500);
    }
    .field-error {
      font-size: .78rem;
      color: var(--red-500);
    }

    /* ── Submit btn ── */
    .btn-primary {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      width: 100%;
      padding: .875rem;
      background: linear-gradient(135deg, var(--blue-700) 0%, var(--blue-500) 100%);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: transform .15s, box-shadow .15s;
      margin-top: .5rem;
      box-shadow: 0 4px 15px rgba(29,78,216,.35);
    }
    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(29,78,216,.45);
    }
    .btn-primary:active { transform: translateY(0); }

    /* ── Footer link ── */
    .card-footer {
      text-align: center;
      padding: 1.25rem 2.5rem;
      border-top: 1px solid var(--gray-100);
      color: var(--gray-500);
      font-size: .875rem;
    }
    .card-footer a { color: var(--blue-700); font-weight: 600; text-decoration: none; }
    .card-footer a:hover { text-decoration: underline; }

    /* ── Step indicator ── */
    .steps {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      padding: 1rem 0 0;
    }
    .step-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--gray-300);
    }
    .step-dot.active { background: var(--blue-500); width: 24px; border-radius: 4px; }

    @media (max-width: 560px) {
      .form-grid { grid-template-columns: 1fr; }
      .form-grid .full { grid-column: 1; }
      .card-body { padding: 1.5rem; }
      .card-header { padding: 1.5rem; }
    }
  </style>
</head>
<body>

  <!-- Language switcher -->
  <div class="lang-bar">
    <?php foreach (['ar' => 'AR', 'fr' => 'FR', 'en' => 'EN'] as $code => $label): ?>
    <a href="?lang=<?php echo $code; ?>"
       class="lang-btn <?php echo $currentLang === $code ? 'active' : ''; ?>">
      <?php echo $label; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card">

    <!-- Header -->
    <div class="card-header">
      <div class="logo-wrap">
        <i data-lucide="graduation-cap" width="32" height="32"></i>
      </div>
      <h1><?php echo t('app_name'); ?></h1>
      <p><?php echo t('register'); ?></p>
      <div class="steps">
        <div class="step-dot active"></div>
        <div class="step-dot"></div>
        <div class="step-dot"></div>
      </div>
    </div>

    <!-- Body -->
    <div class="card-body">

      <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error">
        <i data-lucide="alert-circle" width="18" height="18"></i>
        <?php echo htmlspecialchars($errors['general']); ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="register.php" novalidate>
        <div class="form-grid">

          <!-- Full name -->
          <div class="field full">
            <label for="full_name"><?php echo t('full_name'); ?></label>
            <div class="input-wrap">
              <span class="input-icon"><i data-lucide="user" width="16" height="16"></i></span>
              <input
                type="text"
                id="full_name"
                name="full_name"
                autocomplete="name"
                value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>"
                class="<?php echo isset($errors['full_name']) ? 'is-error' : ''; ?>"
                required>
            </div>
            <?php if (!empty($errors['full_name'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($errors['full_name']); ?></span>
            <?php endif; ?>
          </div>

          <!-- Username -->
          <div class="field">
            <label for="username"><?php echo t('username'); ?></label>
            <div class="input-wrap">
              <span class="input-icon"><i data-lucide="at-sign" width="16" height="16"></i></span>
              <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>"
                class="<?php echo isset($errors['username']) ? 'is-error' : ''; ?>"
                required>
            </div>
            <?php if (!empty($errors['username'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($errors['username']); ?></span>
            <?php endif; ?>
          </div>

          <!-- Email -->
          <div class="field">
            <label for="email"><?php echo t('email'); ?></label>
            <div class="input-wrap">
              <span class="input-icon"><i data-lucide="mail" width="16" height="16"></i></span>
              <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                class="<?php echo isset($errors['email']) ? 'is-error' : ''; ?>"
                required>
            </div>
            <?php if (!empty($errors['email'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($errors['email']); ?></span>
            <?php endif; ?>
          </div>

          <!-- Grade level -->
          <div class="field full">
            <label for="level_id"><?php echo t('grade_level'); ?></label>
            <div class="input-wrap">
              <span class="input-icon"><i data-lucide="layers" width="16" height="16"></i></span>
              <select
                id="level_id"
                name="level_id"
                class="<?php echo isset($errors['level_id']) ? 'is-error' : ''; ?>"
                required>
                <option value=""><?php echo t('select_grade'); ?></option>
                <?php foreach ($levels as $level): ?>
                <option
                  value="<?php echo (int) $level['id']; ?>"
                  <?php echo (isset($formData['level_id']) && $formData['level_id'] == $level['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($level['name_' . $currentLang]); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if (!empty($errors['level_id'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($errors['level_id']); ?></span>
            <?php endif; ?>
          </div>

          <!-- Password -->
          <div class="field">
            <label for="password"><?php echo t('password'); ?></label>
            <div class="input-wrap">
              <span class="input-icon"><i data-lucide="lock" width="16" height="16"></i></span>
              <input
                type="password"
                id="password"
                name="password"
                autocomplete="new-password"
                minlength="6"
                class="<?php echo isset($errors['password']) ? 'is-error' : ''; ?>"
                required>
            </div>
            <?php if (!empty($errors['password'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($errors['password']); ?></span>
            <?php endif; ?>
          </div>

          <!-- Confirm password -->
          <div class="field">
            <label for="confirm_password"><?php echo t('confirm_password'); ?></label>
            <div class="input-wrap">
              <span class="input-icon"><i data-lucide="shield-check" width="16" height="16"></i></span>
              <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                autocomplete="new-password"
                minlength="6"
                class="<?php echo isset($errors['confirm_password']) ? 'is-error' : ''; ?>"
                required>
            </div>
            <?php if (!empty($errors['confirm_password'])): ?>
            <span class="field-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
            <?php endif; ?>
          </div>

        </div><!-- /form-grid -->

        <button type="submit" class="btn-primary" style="margin-top:1.5rem;">
          <i data-lucide="user-plus" width="18" height="18"></i>
          <?php echo t('register'); ?>
        </button>

      </form>
    </div>

    <!-- Footer -->
    <div class="card-footer">
      <?php echo t('have_account'); ?>
      <a href="login.php"><?php echo t('login'); ?></a>
    </div>

  </div>

<script>lucide.createIcons();</script>
</body>
</html>
