<?php
/**
 * admin/manage_users.php — User Management
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';

// ── Handle POST AJAX ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    adminVerifyCsrf();

    $action = $_POST['action'] ?? '';
    $uid    = (int) ($_POST['user_id'] ?? 0);

    if (!$uid) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }

    // ── Toggle ban/activate ───────────────────────────────────
    if ($action === 'toggle_active') {
        $user = db_row('SELECT is_active FROM users WHERE id = ? AND role = ?', [$uid, 'student']);
        if (!$user) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }

        $newState = $user['is_active'] ? 0 : 1;
        db_run('UPDATE users SET is_active = ? WHERE id = ?', [$newState, $uid]);
        $label = $newState ? 'Account activated.' : 'Account banned.';
        echo json_encode(['success' => true, 'message' => $label, 'is_active' => $newState]);
        exit;
    }

    // ── Add XP ───────────────────────────────────────────────
    if ($action === 'add_xp') {
        $amount = (int) ($_POST['xp_amount'] ?? 0);
        if ($amount === 0) { echo json_encode(['success' => false, 'message' => 'XP amount cannot be zero.']); exit; }

        $user = db_row('SELECT xp_points FROM users WHERE id = ? AND role = ?', [$uid, 'student']);
        if (!$user) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }

        $newXP    = max(0, (int) $user['xp_points'] + $amount);
        $newLevel = calculateLevel($newXP);
        db_run('UPDATE users SET xp_points = ?, current_level = ? WHERE id = ?', [$newXP, $newLevel, $uid]);

        echo json_encode([
            'success'  => true,
            'message'  => ($amount > 0 ? "+$amount" : "$amount") . " XP applied. New total: $newXP XP.",
            'xp_points'=> $newXP,
            'level'    => $newLevel,
        ]);
        exit;
    }

    // ── Reset XP ─────────────────────────────────────────────
    if ($action === 'reset_xp') {
        db_run('UPDATE users SET xp_points = 0, current_level = 1 WHERE id = ?', [$uid]);
        echo json_encode(['success' => true, 'message' => 'XP reset to 0.', 'xp_points' => 0, 'level' => 1]);
        exit;
    }

    // ── Get user for XP modal ─────────────────────────────────
    if ($action === 'get_user') {
        $user = db_row(
            'SELECT id, full_name, username, xp_points, current_level FROM users WHERE id = ? AND role = ?',
            [$uid, 'student']
        );
        echo json_encode(['success' => (bool)$user, 'user' => $user]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ── Fetch all students ────────────────────────────────────────
$users = db_all(
    "SELECT u.id, u.username, u.full_name, u.email,
            u.xp_points, u.current_level, u.is_active,
            u.preferred_language, u.last_login, u.created_at,
            lv.name_{$currentLang} AS level_name,
            (SELECT COUNT(*) FROM lesson_progress lp WHERE lp.user_id = u.id AND lp.status = 'completed') AS lessons_done
     FROM   users u
     LEFT   JOIN levels lv ON u.level_id = lv.id
     WHERE  u.role = 'student'
     ORDER  BY u.created_at DESC"
);

$pageTitle  = 'Manage Users';
$activePage = 'users';
require_once '_layout.php';
?>

<!-- Page Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <div>
    <h1 style="font-size:1.3rem;font-weight:800;color:var(--text-primary);">Students</h1>
    <p style="font-size:.82rem;color:var(--text-muted);margin-top:.2rem;">
      <?php echo count($users); ?> registered students
    </p>
  </div>
</div>

<!-- Users Table -->
<div class="section-card">
  <div class="section-card-body" style="padding:0;">
    <div style="overflow-x:auto;">
      <table id="usersTable" class="table table-hover mb-0" style="width:100%;">
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Grade Level</th>
            <th>XP</th>
            <th>Level</th>
            <th>Lessons Done</th>
            <th>Language</th>
            <th>Joined</th>
            <th>Status</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr data-uid="<?php echo (int)$u['id']; ?>">
            <td><?php echo (int)$u['id']; ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.65rem;">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                  <?php echo mb_strtoupper(mb_substr($u['full_name'],0,1,'UTF-8'),'UTF-8'); ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:.85rem;"><?php echo htmlspecialchars($u['full_name']); ?></div>
                  <div style="font-size:.72rem;color:var(--text-muted);">@<?php echo htmlspecialchars($u['username']); ?></div>
                </div>
              </div>
            </td>
            <td><?php echo htmlspecialchars($u['level_name'] ?? '—'); ?></td>
            <td>
              <strong class="xp-display" data-uid="<?php echo (int)$u['id']; ?>">
                <?php echo number_format((int)$u['xp_points']); ?>
              </strong>
            </td>
            <td>
              <span class="level-display" data-uid="<?php echo (int)$u['id']; ?>">
                <?php echo (int)$u['current_level']; ?>
              </span>
            </td>
            <td><?php echo (int)$u['lessons_done']; ?></td>
            <td><span class="badge-status badge-student"><?php echo strtoupper($u['preferred_language']); ?></span></td>
            <td style="font-size:.78rem;color:var(--text-muted);">
              <?php echo $u['created_at'] ? date('M j, Y', strtotime($u['created_at'])) : '—'; ?>
            </td>
            <td>
              <span class="status-badge badge-status <?php echo $u['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"
                    data-uid="<?php echo (int)$u['id']; ?>">
                <?php echo $u['is_active'] ? 'Active' : 'Banned'; ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.3rem;flex-wrap:nowrap;">
                <!-- XP -->
                <button class="btn btn-warning btn-sm btn-icon"
                        onclick="openXpModal(<?php echo (int)$u['id']; ?>, '<?php echo addslashes(htmlspecialchars($u['full_name'])); ?>', <?php echo (int)$u['xp_points']; ?>)"
                        title="Manage XP">
                  <i data-lucide="zap" width="13" height="13"></i>
                </button>
                <!-- Ban / Activate -->
                <button class="btn btn-sm btn-icon <?php echo $u['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                        onclick="toggleActive(<?php echo (int)$u['id']; ?>, <?php echo $u['is_active'] ? 1 : 0; ?>)"
                        title="<?php echo $u['is_active'] ? 'Ban user' : 'Activate user'; ?>">
                  <i data-lucide="<?php echo $u['is_active'] ? 'ban' : 'check-circle'; ?>" width="13" height="13"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ═══ XP MODAL ════════════════════════════════════════════════ -->
<div class="modal-overlay" id="xpModal">
  <div class="modal-box" style="max-width:400px;">
    <div class="modal-head">
      <h3>Manage XP — <span id="xpModalName"></span></h3>
      <button class="modal-close" onclick="closeModal('xpModal')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="xpUserId" value="">

      <!-- Current XP display -->
      <div style="background:var(--blue-50);border-radius:10px;padding:1rem;text-align:center;margin-bottom:1.25rem;">
        <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.3rem;">Current XP</div>
        <div id="xpCurrentDisplay" style="font-size:2rem;font-weight:900;color:var(--blue-700);">0</div>
      </div>

      <div class="form-group" style="margin-bottom:1rem;">
        <label class="form-label">XP Amount (use negative to deduct)</label>
        <input type="number" id="xpAmount" class="form-control"
               placeholder="e.g. 50 or -20" value="50">
        <span class="form-hint">Positive = add, Negative = deduct</span>
      </div>

      <!-- Quick presets -->
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
        <?php foreach ([10,25,50,100,-10,-50] as $v): ?>
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('xpAmount').value=<?php echo $v; ?>">
          <?php echo $v > 0 ? "+$v" : $v; ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal-footer" style="justify-content:space-between;">
      <button class="btn btn-danger btn-sm" onclick="resetXP()">
        <i data-lucide="rotate-ccw" width="13" height="13"></i> Reset to 0
      </button>
      <div style="display:flex;gap:.75rem;">
        <button class="btn btn-ghost" onclick="closeModal('xpModal')">Cancel</button>
        <button class="btn btn-primary" onclick="applyXP()">
          <i data-lucide="zap" width="14" height="14"></i> Apply XP
        </button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(() => {
  initDataTable('#usersTable');
  lucide.createIcons();
});

// ── Toggle active/ban ───────────────────────────────────────
async function toggleActive(uid, currentState) {
  const action = currentState ? 'Ban' : 'Activate';
  if (!confirm(`${action} this user?`)) return;

  try {
    const res = await adminFetch('manage_users.php', { action: 'toggle_active', user_id: uid });
    if (res.success) {
      const badge = document.querySelector(`.status-badge[data-uid="${uid}"]`);
      const btn   = badge?.closest('tr')?.querySelector('.btn-danger, .btn-success');

      if (badge) {
        badge.className = `status-badge badge-status ${res.is_active ? 'badge-active' : 'badge-inactive'}`;
        badge.textContent = res.is_active ? 'Active' : 'Banned';
      }
      if (btn) {
        btn.className = `btn btn-sm btn-icon ${res.is_active ? 'btn-danger' : 'btn-success'}`;
        btn.title = res.is_active ? 'Ban user' : 'Activate user';
        const icon = btn.querySelector('i');
        if (icon) { icon.setAttribute('data-lucide', res.is_active ? 'ban' : 'check-circle'); lucide.createIcons(); }
        btn.setAttribute('onclick', `toggleActive(${uid}, ${res.is_active ? 1 : 0})`);
      }
      adminToast('success', res.message);
    } else {
      adminToast('error', res.message);
    }
  } catch(e) {
    adminToast('error', 'Network error.');
  }
}

// ── XP Modal ────────────────────────────────────────────────
function openXpModal(uid, name, currentXP) {
  document.getElementById('xpUserId').value        = uid;
  document.getElementById('xpModalName').textContent = name;
  document.getElementById('xpCurrentDisplay').textContent = currentXP.toLocaleString();
  document.getElementById('xpAmount').value        = 50;
  openModal('xpModal');
}

async function applyXP() {
  const uid    = document.getElementById('xpUserId').value;
  const amount = parseInt(document.getElementById('xpAmount').value, 10);
  if (isNaN(amount) || amount === 0) { adminToast('error', 'Enter a non-zero amount.'); return; }

  try {
    const res = await adminFetch('manage_users.php', { action: 'add_xp', user_id: uid, xp_amount: amount });
    if (res.success) {
      document.getElementById('xpCurrentDisplay').textContent = res.xp_points.toLocaleString();
      // Update table
      const xpCell   = document.querySelector(`.xp-display[data-uid="${uid}"]`);
      const lvlCell  = document.querySelector(`.level-display[data-uid="${uid}"]`);
      if (xpCell) xpCell.textContent = res.xp_points.toLocaleString();
      if (lvlCell) lvlCell.textContent = res.level;
      adminToast('success', res.message);
    } else {
      adminToast('error', res.message);
    }
  } catch(e) {
    adminToast('error', 'Network error.');
  }
}

async function resetXP() {
  const uid = document.getElementById('xpUserId').value;
  if (!confirm('Reset this student\'s XP to 0? This cannot be undone.')) return;
  try {
    const res = await adminFetch('manage_users.php', { action: 'reset_xp', user_id: uid });
    if (res.success) {
      document.getElementById('xpCurrentDisplay').textContent = '0';
      const xpCell  = document.querySelector(`.xp-display[data-uid="${uid}"]`);
      const lvlCell = document.querySelector(`.level-display[data-uid="${uid}"]`);
      if (xpCell)  xpCell.textContent  = '0';
      if (lvlCell) lvlCell.textContent = '1';
      adminToast('info', res.message);
    } else {
      adminToast('error', res.message);
    }
  } catch(e) {
    adminToast('error', 'Network error.');
  }
}
</script>

<?php require_once '_layout_end.php'; ?>
