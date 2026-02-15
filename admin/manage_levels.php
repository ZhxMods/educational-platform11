<?php
/**
 * admin/manage_levels.php — Grade Level Management
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    adminVerifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_level') {
        $id      = (int) ($_POST['level_id'] ?? 0);
        $name_ar = trim($_POST['name_ar'] ?? '');
        $name_fr = trim($_POST['name_fr'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $slug    = trim($_POST['slug']    ?? '');
        $order   = (int) ($_POST['display_order'] ?? 0);

        if (!$name_ar || !$slug) {
            echo json_encode(['success' => false, 'message' => 'Arabic name and slug are required.']);
            exit;
        }
        if ($id > 0) {
            db_run('UPDATE levels SET name_ar=?,name_fr=?,name_en=?,slug=?,display_order=? WHERE id=?',
                [$name_ar,$name_fr,$name_en,$slug,$order,$id]);
            echo json_encode(['success' => true, 'message' => 'Level updated.']);
        } else {
            db_run('INSERT INTO levels (name_ar,name_fr,name_en,slug,display_order) VALUES (?,?,?,?,?)',
                [$name_ar,$name_fr,$name_en,$slug,$order]);
            echo json_encode(['success' => true, 'message' => 'Level created.']);
        }
        exit;
    }

    if ($action === 'delete_level') {
        $id = (int) ($_POST['level_id'] ?? 0);
        if ($id > 0) {
            db_run('DELETE FROM levels WHERE id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => 'Level deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    if ($action === 'get_level') {
        $row = db_row('SELECT * FROM levels WHERE id = ?', [(int)($_POST['level_id']??0)]);
        echo json_encode(['success' => (bool)$row, 'level' => $row]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

$levels = db_all('SELECT * FROM levels ORDER BY display_order ASC');

$pageTitle  = 'Grade Levels';
$activePage = 'levels';
require_once '_layout.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <div>
    <h1 style="font-size:1.3rem;font-weight:800;">Grade Levels</h1>
    <p style="font-size:.82rem;color:var(--text-muted);"><?php echo count($levels); ?> levels configured</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('levelModal')">
    <i data-lucide="plus" width="15" height="15"></i> Add Level
  </button>
</div>

<div class="section-card">
  <div class="section-card-body" style="padding:0;">
    <table id="levelsTable" class="table table-hover mb-0" style="width:100%;">
      <thead>
        <tr>
          <th>#</th><th>Arabic</th><th>French</th><th>English</th><th>Slug</th><th>Order</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($levels as $lv): ?>
        <tr data-id="<?php echo (int)$lv['id']; ?>">
          <td><?php echo (int)$lv['id']; ?></td>
          <td><?php echo htmlspecialchars($lv['name_ar']); ?></td>
          <td><?php echo htmlspecialchars($lv['name_fr']); ?></td>
          <td><?php echo htmlspecialchars($lv['name_en']); ?></td>
          <td><code><?php echo htmlspecialchars($lv['slug']); ?></code></td>
          <td><?php echo (int)$lv['display_order']; ?></td>
          <td>
            <div style="display:flex;gap:.35rem;">
              <button class="btn btn-ghost btn-sm btn-icon" onclick="editLevel(<?php echo (int)$lv['id']; ?>)">
                <i data-lucide="pencil" width="14" height="14"></i>
              </button>
              <button class="btn btn-danger btn-sm btn-icon" onclick="deleteLevel(<?php echo (int)$lv['id']; ?>, '<?php echo addslashes($lv['name_ar']); ?>')">
                <i data-lucide="trash-2" width="14" height="14"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="levelModal">
  <div class="modal-box" style="max-width:520px;">
    <div class="modal-head">
      <h3 id="levelModalTitle">Add Level</h3>
      <button class="modal-close" onclick="closeModal('levelModal')"><i data-lucide="x" width="18" height="18"></i></button>
    </div>
    <div class="modal-body">
      <form id="levelForm">
        <input type="hidden" name="level_id" id="field_lvl_id" value="0">
        <input type="hidden" name="action" value="save_level">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Arabic Name <span class="req">*</span></label>
            <input type="text" name="name_ar" id="field_lvl_ar" class="form-control" dir="rtl" required>
          </div>
          <div class="form-group">
            <label class="form-label">French Name</label>
            <input type="text" name="name_fr" id="field_lvl_fr" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">English Name</label>
            <input type="text" name="name_en" id="field_lvl_en" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Slug <span class="req">*</span></label>
            <input type="text" name="slug" id="field_lvl_slug" class="form-control" placeholder="e.g. 3ac" required>
          </div>
          <div class="form-group">
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="field_lvl_order" class="form-control" value="0" min="0">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('levelModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveLevel()"><i data-lucide="save" width="15" height="15"></i> Save</button>
    </div>
  </div>
</div>

<script>
$(document).ready(() => { initDataTable('#levelsTable'); lucide.createIcons(); });

async function editLevel(id) {
  const data = await adminFetch('manage_levels.php', { action: 'get_level', level_id: id });
  if (!data.success) { adminToast('error', 'Load failed.'); return; }
  const lv = data.level;
  document.getElementById('levelModalTitle').textContent = 'Edit Level';
  document.getElementById('field_lvl_id').value    = lv.id;
  document.getElementById('field_lvl_ar').value    = lv.name_ar || '';
  document.getElementById('field_lvl_fr').value    = lv.name_fr || '';
  document.getElementById('field_lvl_en').value    = lv.name_en || '';
  document.getElementById('field_lvl_slug').value  = lv.slug    || '';
  document.getElementById('field_lvl_order').value = lv.display_order || 0;
  openModal('levelModal');
}

async function saveLevel() {
  const form = document.getElementById('levelForm');
  const data = new URLSearchParams(new FormData(form));
  data.set('csrf_token', CSRF_TOKEN);
  const res = await fetch('manage_levels.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: data });
  const json = await res.json();
  if (json.success) { adminToast('success', json.message); setTimeout(() => location.reload(), 1000); }
  else adminToast('error', json.message);
}

async function deleteLevel(id, name) {
  if (!confirm(`Delete level "${name}"? This will also delete all subjects and lessons under it.`)) return;
  const res = await adminFetch('manage_levels.php', { action: 'delete_level', level_id: id });
  if (res.success) { adminToast('success', 'Deleted.'); document.querySelector(`tr[data-id="${id}"]`)?.remove(); }
  else adminToast('error', res.message);
}
</script>

<?php require_once '_layout_end.php'; ?>
