<?php
/**
 * admin/manage_subjects.php — Subject Management
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    adminVerifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_subject') {
        $id       = (int)($_POST['subject_id'] ?? 0);
        $level_id = (int)($_POST['level_id']   ?? 0);
        $name_ar  = trim($_POST['name_ar'] ?? '');
        $name_fr  = trim($_POST['name_fr'] ?? '');
        $name_en  = trim($_POST['name_en'] ?? '');
        $color    = trim($_POST['color']   ?? '#1D4ED8');
        $order    = (int)($_POST['display_order'] ?? 0);

        if (!$level_id || !$name_ar) {
            echo json_encode(['success' => false, 'message' => 'Level and Arabic name are required.']);
            exit;
        }
        if ($id > 0) {
            db_run('UPDATE subjects SET level_id=?,name_ar=?,name_fr=?,name_en=?,color=?,display_order=? WHERE id=?',
                [$level_id,$name_ar,$name_fr,$name_en,$color,$order,$id]);
            echo json_encode(['success' => true, 'message' => 'Subject updated.']);
        } else {
            db_run('INSERT INTO subjects (level_id,name_ar,name_fr,name_en,color,display_order) VALUES (?,?,?,?,?,?)',
                [$level_id,$name_ar,$name_fr,$name_en,$color,$order]);
            echo json_encode(['success' => true, 'message' => 'Subject created.']);
        }
        exit;
    }

    if ($action === 'delete_subject') {
        $id = (int)($_POST['subject_id'] ?? 0);
        if ($id > 0) { db_run('DELETE FROM subjects WHERE id = ?', [$id]); echo json_encode(['success'=>true,'message'=>'Deleted.']); }
        else echo json_encode(['success'=>false,'message'=>'Invalid ID.']);
        exit;
    }

    if ($action === 'get_subject') {
        $row = db_row('SELECT * FROM subjects WHERE id = ?', [(int)($_POST['subject_id']??0)]);
        echo json_encode(['success' => (bool)$row, 'subject' => $row]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

$subjects = db_all(
    "SELECT s.*, lv.name_{$currentLang} AS level_name
     FROM   subjects s
     JOIN   levels   lv ON s.level_id = lv.id
     ORDER  BY lv.display_order, s.display_order"
);
$levels = db_all('SELECT id, name_ar, name_fr, name_en FROM levels ORDER BY display_order');

$pageTitle  = 'Subjects';
$activePage = 'subjects';
require_once '_layout.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <div>
    <h1 style="font-size:1.3rem;font-weight:800;">Subjects</h1>
    <p style="font-size:.82rem;color:var(--text-muted);"><?php echo count($subjects); ?> subjects across all levels</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('subjectModal')">
    <i data-lucide="plus" width="15" height="15"></i> Add Subject
  </button>
</div>

<div class="section-card">
  <div class="section-card-body" style="padding:0;">
    <table id="subjectsTable" class="table table-hover mb-0" style="width:100%;">
      <thead>
        <tr><th>#</th><th>Name</th><th>Level</th><th>Color</th><th>Order</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($subjects as $sub): ?>
        <tr data-id="<?php echo (int)$sub['id']; ?>">
          <td><?php echo (int)$sub['id']; ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.6rem;">
              <span style="width:14px;height:14px;border-radius:50%;background:<?php echo htmlspecialchars($sub['color']); ?>;display:inline-block;flex-shrink:0;"></span>
              <strong><?php echo htmlspecialchars($sub['name_' . $currentLang] ?: $sub['name_ar']); ?></strong>
            </div>
          </td>
          <td><?php echo htmlspecialchars($sub['level_name']); ?></td>
          <td><code><?php echo htmlspecialchars($sub['color']); ?></code></td>
          <td><?php echo (int)$sub['display_order']; ?></td>
          <td>
            <div style="display:flex;gap:.35rem;">
              <button class="btn btn-ghost btn-sm btn-icon" onclick="editSubject(<?php echo (int)$sub['id']; ?>)">
                <i data-lucide="pencil" width="14" height="14"></i>
              </button>
              <button class="btn btn-danger btn-sm btn-icon" onclick="deleteSubject(<?php echo (int)$sub['id']; ?>, '<?php echo addslashes($sub['name_ar']); ?>')">
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
<div class="modal-overlay" id="subjectModal">
  <div class="modal-box" style="max-width:540px;">
    <div class="modal-head">
      <h3 id="subjectModalTitle">Add Subject</h3>
      <button class="modal-close" onclick="closeModal('subjectModal')"><i data-lucide="x" width="18" height="18"></i></button>
    </div>
    <div class="modal-body">
      <form id="subjectForm">
        <input type="hidden" name="subject_id" id="field_sub_id" value="0">
        <input type="hidden" name="action" value="save_subject">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Level <span class="req">*</span></label>
            <select name="level_id" id="field_sub_level" class="form-control" required>
              <option value="">— Select Level —</option>
              <?php foreach ($levels as $lv): ?>
              <option value="<?php echo (int)$lv['id']; ?>"><?php echo htmlspecialchars($lv['name_' . $currentLang]); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group full">
            <label class="form-label">Arabic Name <span class="req">*</span></label>
            <input type="text" name="name_ar" id="field_sub_ar" class="form-control" dir="rtl" required>
          </div>
          <div class="form-group">
            <label class="form-label">French Name</label>
            <input type="text" name="name_fr" id="field_sub_fr" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">English Name</label>
            <input type="text" name="name_en" id="field_sub_en" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Color (hex)</label>
            <input type="color" name="color" id="field_sub_color" class="form-control" value="#1D4ED8" style="height:42px;padding:.3rem;">
          </div>
          <div class="form-group">
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="field_sub_order" class="form-control" value="0" min="0">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('subjectModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveSubject()"><i data-lucide="save" width="15" height="15"></i> Save</button>
    </div>
  </div>
</div>

<script>
$(document).ready(() => { initDataTable('#subjectsTable'); lucide.createIcons(); });

async function editSubject(id) {
  const data = await adminFetch('manage_subjects.php', { action: 'get_subject', subject_id: id });
  if (!data.success) { adminToast('error', 'Load failed.'); return; }
  const s = data.subject;
  document.getElementById('subjectModalTitle').textContent = 'Edit Subject';
  document.getElementById('field_sub_id').value    = s.id;
  document.getElementById('field_sub_level').value = s.level_id;
  document.getElementById('field_sub_ar').value    = s.name_ar  || '';
  document.getElementById('field_sub_fr').value    = s.name_fr  || '';
  document.getElementById('field_sub_en').value    = s.name_en  || '';
  document.getElementById('field_sub_color').value = s.color    || '#1D4ED8';
  document.getElementById('field_sub_order').value = s.display_order || 0;
  openModal('subjectModal');
}

async function saveSubject() {
  const form = document.getElementById('subjectForm');
  const data = new URLSearchParams(new FormData(form));
  data.set('csrf_token', CSRF_TOKEN);
  const res  = await fetch('manage_subjects.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: data });
  const json = await res.json();
  if (json.success) { adminToast('success', json.message); setTimeout(() => location.reload(), 1000); }
  else adminToast('error', json.message);
}

async function deleteSubject(id, name) {
  if (!confirm(`Delete subject "${name}"? All lessons under it will also be deleted.`)) return;
  const res = await adminFetch('manage_subjects.php', { action: 'delete_subject', subject_id: id });
  if (res.success) { adminToast('success', 'Deleted.'); document.querySelector(`tr[data-id="${id}"]`)?.remove(); }
  else adminToast('error', res.message);
}
</script>

<?php require_once '_layout_end.php'; ?>
