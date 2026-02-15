<?php
/**
 * admin/manage_lessons.php — Lesson CMS with Live URL Preview
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    adminVerifyCsrf();

    $action = $_POST['action'] ?? '';

    // ── ADD or EDIT lesson ───────────────────────────────────
    if ($action === 'save_lesson') {
        $id          = (int) ($_POST['lesson_id'] ?? 0);
        $subject_id  = (int) ($_POST['subject_id'] ?? 0);
        $type        = in_array($_POST['content_type'] ?? '', ['video','pdf','book']) ? $_POST['content_type'] : 'video';
        $url         = trim($_POST['url'] ?? '');
        $order       = (int) ($_POST['display_order'] ?? 0);
        $xp          = max(1, (int) ($_POST['xp_reward'] ?? 10));
        $duration    = (int) ($_POST['duration_minutes'] ?? 0);
        $published   = isset($_POST['is_published']) ? 1 : 0;

        $title_ar = trim($_POST['title_ar'] ?? '');
        $title_fr = trim($_POST['title_fr'] ?? '');
        $title_en = trim($_POST['title_en'] ?? '');
        $desc_ar  = trim($_POST['desc_ar']  ?? '');
        $desc_fr  = trim($_POST['desc_fr']  ?? '');
        $desc_en  = trim($_POST['desc_en']  ?? '');

        if (!$subject_id || !$url || !$title_ar) {
            echo json_encode(['success' => false, 'message' => 'Subject, URL and Arabic title are required.']);
            exit;
        }

        if ($id > 0) {
            // Update
            db_run(
                "UPDATE lessons SET
                    subject_id=?, content_type=?, url=?, display_order=?,
                    xp_reward=?, duration_minutes=?, is_published=?,
                    title_ar=?, title_fr=?, title_en=?,
                    description_ar=?, description_fr=?, description_en=?
                 WHERE id=?",
                [$subject_id,$type,$url,$order,$xp,$duration,$published,
                 $title_ar,$title_fr,$title_en,$desc_ar,$desc_fr,$desc_en,$id]
            );
            echo json_encode(['success' => true, 'message' => 'Lesson updated successfully.']);
        } else {
            // Insert
            db_run(
                "INSERT INTO lessons
                    (subject_id,content_type,url,display_order,xp_reward,duration_minutes,
                     is_published,title_ar,title_fr,title_en,
                     description_ar,description_fr,description_en,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$subject_id,$type,$url,$order,$xp,$duration,$published,
                 $title_ar,$title_fr,$title_en,$desc_ar,$desc_fr,$desc_en,
                 (int)$adminUser['id']]
            );
            echo json_encode(['success' => true, 'message' => 'Lesson created successfully.']);
        }
        exit;
    }

    // ── DELETE lesson ────────────────────────────────────────
    if ($action === 'delete_lesson') {
        $id = (int) ($_POST['lesson_id'] ?? 0);
        if ($id > 0) {
            db_run('DELETE FROM lessons WHERE id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => 'Lesson deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        }
        exit;
    }

    // ── GET lesson for edit ──────────────────────────────────
    if ($action === 'get_lesson') {
        $id = (int) ($_POST['lesson_id'] ?? 0);
        $lesson = db_row('SELECT * FROM lessons WHERE id = ?', [$id]);
        echo json_encode(['success' => (bool)$lesson, 'lesson' => $lesson]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ── Fetch data for display ────────────────────────────────────
$lessons = db_all(
    "SELECT l.*, s.name_{$currentLang} AS subject_name, s.level_id,
            lv.name_{$currentLang} AS level_name
     FROM   lessons  l
     JOIN   subjects s  ON l.subject_id  = s.id
     JOIN   levels   lv ON s.level_id    = lv.id
     ORDER  BY lv.display_order, s.display_order, l.display_order"
);

$subjects = db_all(
    "SELECT s.id, s.name_ar, s.name_fr, s.name_en,
            lv.name_{$currentLang} AS level_name
     FROM   subjects s
     JOIN   levels   lv ON s.level_id = lv.id
     ORDER  BY lv.display_order, s.display_order"
);

$levels = db_all('SELECT * FROM levels ORDER BY display_order');

$pageTitle  = 'Manage Lessons';
$activePage = 'lessons';
require_once '_layout.php';
?>

<!-- Page Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <div>
    <h1 style="font-size:1.3rem;font-weight:800;color:var(--text-primary);">Lessons</h1>
    <p style="font-size:.82rem;color:var(--text-muted);margin-top:.2rem;"><?php echo count($lessons); ?> total lessons</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('lessonModal')">
    <i data-lucide="plus" width="15" height="15"></i> Add Lesson
  </button>
</div>

<!-- Lessons Table -->
<div class="section-card">
  <div class="section-card-body" style="padding:0;">
    <div style="overflow-x:auto;">
      <table id="lessonsTable" class="table table-hover mb-0" style="width:100%;">
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Subject</th>
            <th>Level</th>
            <th>Type</th>
            <th>XP</th>
            <th>Order</th>
            <th>Status</th>
            <th style="width:100px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lessons as $row): ?>
          <tr data-id="<?php echo (int)$row['id']; ?>">
            <td><?php echo (int)$row['id']; ?></td>
            <td>
              <div style="font-weight:600;font-size:.85rem;"><?php echo htmlspecialchars($row['title_' . $currentLang] ?: $row['title_ar']); ?></div>
              <div style="font-size:.72rem;color:var(--text-muted);max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($row['url']); ?></div>
            </td>
            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
            <td><?php echo htmlspecialchars($row['level_name']); ?></td>
            <td>
              <span class="badge-status badge-<?php echo $row['content_type']; ?>">
                <?php echo ucfirst($row['content_type']); ?>
              </span>
            </td>
            <td><strong><?php echo (int)$row['xp_reward']; ?></strong></td>
            <td><?php echo (int)$row['display_order']; ?></td>
            <td>
              <span class="badge-status <?php echo $row['is_published'] ? 'badge-active' : 'badge-inactive'; ?>">
                <?php echo $row['is_published'] ? 'Published' : 'Draft'; ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.35rem;">
                <button class="btn btn-ghost btn-sm btn-icon" onclick="editLesson(<?php echo (int)$row['id']; ?>)" title="Edit">
                  <i data-lucide="pencil" width="14" height="14"></i>
                </button>
                <button class="btn btn-danger btn-sm btn-icon" onclick="deleteLesson(<?php echo (int)$row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['title_ar'])); ?>')" title="Delete">
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
</div>

<!-- ═══ ADD / EDIT LESSON MODAL ════════════════════════════════ -->
<div class="modal-overlay" id="lessonModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modalTitle">Add New Lesson</h3>
      <button class="modal-close" onclick="closeModal('lessonModal')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <form id="lessonForm">
        <input type="hidden" name="lesson_id" id="field_lesson_id" value="0">
        <input type="hidden" name="action"    value="save_lesson">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <!-- Subject & Level -->
        <div class="form-grid" style="margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">Subject <span class="req">*</span></label>
            <select name="subject_id" id="field_subject_id" class="form-control" required>
              <option value="">— Select Subject —</option>
              <?php foreach ($subjects as $sub): ?>
              <option value="<?php echo (int)$sub['id']; ?>">
                <?php echo htmlspecialchars($sub['name_' . $currentLang]); ?>
                (<?php echo htmlspecialchars($sub['level_name']); ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Content Type <span class="req">*</span></label>
            <select name="content_type" id="field_content_type" class="form-control">
              <option value="video">🎬 YouTube Video</option>
              <option value="pdf">📄 MediaFire PDF</option>
              <option value="book">📚 MediaFire Book</option>
            </select>
          </div>
        </div>

        <!-- URL with Live Preview -->
        <div class="form-group full" style="margin-bottom:1rem;">
          <label class="form-label">Resource URL <span class="req">*</span></label>
          <input type="url" name="url" id="field_url" class="form-control"
                 placeholder="https://youtube.com/watch?v=... or https://mediafire.com/..."
                 required>
          <span class="form-hint" id="urlHint">YouTube video link or MediaFire download link</span>
          
          <!-- Live Preview Container -->
          <div id="urlPreview" style="display:none;margin-top:1rem;padding:1rem;background:var(--blue-50);border-radius:8px;border:1px solid var(--blue-100);">
            <!-- YouTube Preview -->
            <div id="youtubePreview" style="display:none;">
              <div style="font-size:.85rem;font-weight:600;color:var(--blue-700);margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem;">
                <i data-lucide="play-circle" width="16" height="16"></i>
                YouTube Video Preview
              </div>
              <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:6px;background:#000;">
                <iframe id="youtubeEmbed" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                        allowfullscreen></iframe>
              </div>
            </div>
            
            <!-- MediaFire/PDF Preview -->
            <div id="mediaPreview" style="display:none;">
              <div style="display:flex;align-items:center;gap:1rem;">
                <div id="fileIcon" style="width:48px;height:48px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;">
                  📄
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:.85rem;font-weight:600;color:var(--text-primary);margin-bottom:.25rem;" id="fileTypeLabel">
                    MediaFire Document
                  </div>
                  <div style="font-size:.75rem;color:var(--text-muted);" id="fileUrlPreview"></div>
                </div>
                <a href="#" target="_blank" rel="noopener" id="testLinkBtn"
                   style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;background:var(--blue-700);color:#fff;border-radius:6px;text-decoration:none;font-size:.8rem;font-weight:600;white-space:nowrap;">
                  <i data-lucide="external-link" width="14" height="14"></i>
                  Test Link
                </a>
              </div>
            </div>
            
            <!-- Invalid URL Warning -->
            <div id="invalidPreview" style="display:none;">
              <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem;background:#fee2e2;border-radius:6px;border:1px solid #fecaca;">
                <i data-lucide="alert-circle" width="18" height="18" color="#dc2626"></i>
                <span style="font-size:.85rem;color:#b91c1c;font-weight:500;">
                  Invalid URL format. Please use a valid YouTube or MediaFire link.
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Titles (tabbed AR/FR/EN) -->
        <div class="lang-tab-group" style="margin-bottom:1rem;">
          <label class="form-label" style="margin-bottom:.5rem;">Titles & Descriptions</label>
          <div class="lang-tabs">
            <button type="button" class="lang-tab active" data-target="lang_ar">AR</button>
            <button type="button" class="lang-tab"        data-target="lang_fr">FR</button>
            <button type="button" class="lang-tab"        data-target="lang_en">EN</button>
          </div>
          <div id="lang_ar" class="lang-panel active">
            <input  type="text"  name="title_ar" id="field_title_ar" class="form-control" placeholder="العنوان بالعربية *" dir="rtl" required>
            <textarea name="desc_ar"  id="field_desc_ar"  class="form-control" placeholder="الوصف بالعربية" dir="rtl"></textarea>
          </div>
          <div id="lang_fr" class="lang-panel">
            <input  type="text"  name="title_fr" id="field_title_fr" class="form-control" placeholder="Titre en français">
            <textarea name="desc_fr"  id="field_desc_fr"  class="form-control" placeholder="Description en français"></textarea>
          </div>
          <div id="lang_en" class="lang-panel">
            <input  type="text"  name="title_en" id="field_title_en" class="form-control" placeholder="Title in English">
            <textarea name="desc_en"  id="field_desc_en"  class="form-control" placeholder="Description in English"></textarea>
          </div>
        </div>

        <!-- XP / Duration / Order -->
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">XP Reward</label>
            <input type="number" name="xp_reward" id="field_xp_reward" class="form-control" value="10" min="1" max="500">
          </div>
          <div class="form-group">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration_minutes" id="field_duration" class="form-control" value="0" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="field_order" class="form-control" value="0" min="0">
          </div>
          <div class="form-group" style="align-self:end;padding-bottom:.65rem;">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.85rem;font-weight:600;color:var(--text-secondary);">
              <input type="checkbox" name="is_published" id="field_published" value="1" checked style="width:16px;height:16px;cursor:pointer;">
              Publish immediately
            </label>
          </div>
        </div>

      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('lessonModal')">Cancel</button>
      <button class="btn btn-primary" id="saveLessonBtn" onclick="saveLesson()" disabled>
        <i data-lucide="save" width="15" height="15"></i>
        <span id="saveBtnLabel">Add Lesson</span>
      </button>
    </div>
  </div>
</div>

<script>
// ── DataTable init ─────────────────────────────────────────
$(document).ready(() => {
  initDataTable('#lessonsTable');
  lucide.createIcons();
});

// ── URL Preview Logic ─────────────────────────────────────
const urlField = document.getElementById('field_url');
const urlPreview = document.getElementById('urlPreview');
const youtubePreview = document.getElementById('youtubePreview');
const mediaPreview = document.getElementById('mediaPreview');
const invalidPreview = document.getElementById('invalidPreview');
const saveBtn = document.getElementById('saveLessonBtn');

let previewTimeout;

urlField.addEventListener('input', () => {
  clearTimeout(previewTimeout);
  previewTimeout = setTimeout(() => updateUrlPreview(), 500);
});

function updateUrlPreview() {
  const url = urlField.value.trim();
  
  // Hide all previews first
  urlPreview.style.display = 'none';
  youtubePreview.style.display = 'none';
  mediaPreview.style.display = 'none';
  invalidPreview.style.display = 'none';
  
  // Empty URL
  if (!url) {
    saveBtn.disabled = true;
    return;
  }
  
  // Basic URL validation
  if (!url.startsWith('https://')) {
    urlPreview.style.display = 'block';
    invalidPreview.style.display = 'block';
    saveBtn.disabled = true;
    lucide.createIcons();
    return;
  }
  
  // Check for YouTube
  const ytRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i;
  const ytMatch = url.match(ytRegex);
  
  if (ytMatch && ytMatch[1]) {
    // YouTube video
    const videoId = ytMatch[1];
    urlPreview.style.display = 'block';
    youtubePreview.style.display = 'block';
    document.getElementById('youtubeEmbed').src = `https://www.youtube.com/embed/${videoId}?rel=0`;
    saveBtn.disabled = false;
    lucide.createIcons();
    return;
  }
  
  // Check for MediaFire or other document links
  if (url.includes('mediafire.com') || url.includes('drive.google.com') || url.match(/\.(pdf|docx?|pptx?)$/i)) {
    urlPreview.style.display = 'block';
    mediaPreview.style.display = 'block';
    
    // Determine file type
    let fileType = 'Document';
    let icon = '📄';
    
    if (url.toLowerCase().includes('.pdf') || url.includes('/file/')) {
      fileType = 'PDF Document';
      icon = '📄';
    } else if (url.toLowerCase().includes('.doc')) {
      fileType = 'Word Document';
      icon = '📝';
    } else if (url.toLowerCase().includes('.ppt')) {
      fileType = 'PowerPoint';
      icon = '📊';
    } else if (url.includes('mediafire')) {
      fileType = 'MediaFire File';
      icon = '📦';
    }
    
    document.getElementById('fileIcon').textContent = icon;
    document.getElementById('fileTypeLabel').textContent = fileType;
    document.getElementById('fileUrlPreview').textContent = url.length > 60 ? url.substring(0, 60) + '...' : url;
    document.getElementById('testLinkBtn').href = url;
    
    saveBtn.disabled = false;
    lucide.createIcons();
    return;
  }
  
  // Unknown URL type
  urlPreview.style.display = 'block';
  invalidPreview.style.display = 'block';
  saveBtn.disabled = true;
  lucide.createIcons();
}

// ── Open modal for new lesson ──────────────────────────────
document.getElementById('lessonModal').addEventListener('click', e => {
  if (e.target === document.getElementById('lessonModal')) closeModal('lessonModal');
});

// Override openModal to reset form
const _origOpen = window.openModal;
window.openModal = function(id) {
  if (id === 'lessonModal') {
    document.getElementById('modalTitle').textContent = 'Add New Lesson';
    document.getElementById('saveBtnLabel').textContent = 'Add Lesson';
    document.getElementById('lessonForm').reset();
    document.getElementById('field_lesson_id').value = '0';
    document.getElementById('field_published').checked = true;
    
    // Reset preview
    urlPreview.style.display = 'none';
    saveBtn.disabled = true;
  }
  _origOpen(id);
};

// ── Edit lesson ────────────────────────────────────────────
async function editLesson(id) {
  try {
    const data = await adminFetch('manage_lessons.php', { action: 'get_lesson', lesson_id: id });
    if (!data.success || !data.lesson) { adminToast('error', 'Could not load lesson.'); return; }
    const l = data.lesson;

    document.getElementById('modalTitle').textContent  = 'Edit Lesson';
    document.getElementById('saveBtnLabel').textContent = 'Save Changes';
    document.getElementById('field_lesson_id').value   = l.id;
    document.getElementById('field_subject_id').value  = l.subject_id;
    document.getElementById('field_content_type').value = l.content_type;
    document.getElementById('field_url').value          = l.url;
    document.getElementById('field_title_ar').value     = l.title_ar || '';
    document.getElementById('field_title_fr').value     = l.title_fr || '';
    document.getElementById('field_title_en').value     = l.title_en || '';
    document.getElementById('field_desc_ar').value      = l.description_ar || '';
    document.getElementById('field_desc_fr').value      = l.description_fr || '';
    document.getElementById('field_desc_en').value      = l.description_en || '';
    document.getElementById('field_xp_reward').value   = l.xp_reward;
    document.getElementById('field_duration').value     = l.duration_minutes;
    document.getElementById('field_order').value        = l.display_order;
    document.getElementById('field_published').checked  = l.is_published == 1;

    // Trigger URL preview
    updateUrlPreview();

    document.getElementById('lessonModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
  } catch(e) {
    adminToast('error', 'Network error.');
  }
}

// ── Save lesson ────────────────────────────────────────────
async function saveLesson() {
  const btn  = document.getElementById('saveLessonBtn');
  const form = document.getElementById('lessonForm');
  btn.disabled = true;
  btn.innerHTML = '<span style="border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;width:14px;height:14px;animation:spin .7s linear infinite;display:inline-block;"></span> Saving...';

  try {
    const data = new URLSearchParams(new FormData(form));
    data.set('csrf_token', CSRF_TOKEN);
    const res = await fetch('manage_lessons.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: data,
    });
    const json = await res.json();

    if (json.success) {
      adminToast('success', json.message);
      setTimeout(() => location.reload(), 1200);
    } else {
      adminToast('error', json.message || 'Error saving lesson.');
    }
  } catch(e) {
    adminToast('error', 'Network error. Please try again.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="save" width="15" height="15"></i> <span id="saveBtnLabel">Save</span>';
    lucide.createIcons();
  }
}

// ── Delete lesson ──────────────────────────────────────────
async function deleteLesson(id, title) {
  if (!confirm(`Delete lesson "${title}"?\n\nThis cannot be undone.`)) return;
  try {
    const res = await adminFetch('manage_lessons.php', { action: 'delete_lesson', lesson_id: id });
    if (res.success) {
      adminToast('success', 'Lesson deleted.');
      document.querySelector(`tr[data-id="${id}"]`)?.remove();
    } else {
      adminToast('error', res.message || 'Delete failed.');
    }
  } catch(e) {
    adminToast('error', 'Network error.');
  }
}

// Spinner keyframes
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>

<?php require_once '_layout_end.php'; ?>