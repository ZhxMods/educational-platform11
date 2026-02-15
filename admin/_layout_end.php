  </div><!-- /admin-content -->
</div><!-- /admin-main -->

<!-- Toast container -->
<div id="admin-toast"></div>

<!-- jQuery + DataTables + Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
'use strict';

// ── Lucide icons ────────────────────────────────────────────
lucide.createIcons();

// ── Sidebar hamburger ───────────────────────────────────────
const sidebar  = document.getElementById('adminSidebar');
const hamburger = document.getElementById('hamburgerAdmin');
if (hamburger) {
  hamburger.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
}

// ── Toast helper ────────────────────────────────────────────
function adminToast(type, message, duration = 3500) {
  const container = document.getElementById('admin-toast');
  const toast = document.createElement('div');
  toast.className = `a-toast ${type}`;
  toast.innerHTML = `<span>${message}</span>`;
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add('in'));
  const close = () => {
    toast.classList.remove('in');
    toast.classList.add('out');
    toast.addEventListener('animationend', () => toast.remove(), { once: true });
  };
  if (duration > 0) setTimeout(close, duration);
}

// ── Modal helpers ───────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

// ── CSRF token ──────────────────────────────────────────────
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Shared fetch helper ─────────────────────────────────────
async function adminFetch(url, data = {}) {
  data.csrf_token = CSRF_TOKEN;
  const body = new URLSearchParams(data);
  const res  = await fetch(url, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body,
  });
  return res.json();
}

// ── Lang tabs (AR / FR / EN) ────────────────────────────────
document.querySelectorAll('.lang-tabs').forEach(tabGroup => {
  tabGroup.querySelectorAll('.lang-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.target;
      const parent = tab.closest('.lang-tab-group');
      parent.querySelectorAll('.lang-tab').forEach(t => t.classList.remove('active'));
      parent.querySelectorAll('.lang-panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      parent.querySelector('#' + target)?.classList.add('active');
    });
  });
});

// ── DataTables default init (call per-page if needed) ───────
window.initDataTable = function(selector, opts = {}) {
  const defaults = {
    responsive  : true,
    pageLength  : 15,
    language    : { search: '', searchPlaceholder: '🔍  Search...' },
    dom         : "<'d-flex align-items-center justify-content-between mb-3'lf>rtip",
  };
  return $(selector).DataTable(Object.assign(defaults, opts));
};
</script>
</body>
</html>
