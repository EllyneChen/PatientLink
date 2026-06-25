// Auth guard
const user = PL.getUser();
if (!user || user.role !== 'moh_admin') {
  window.location.href = 'login.html';
}
document.getElementById('adminName').textContent = user.name;

// Logout
document.getElementById('logoutBtn').addEventListener('click', async () => {
  try { await PL.api('POST', '/api/logout'); } catch (_) {}
  PL.clearSession();
  window.location.href = 'login.html';
});

let currentPage = 1;

// ── Load Analytics ────────────────────────────────────────────
async function loadAnalytics() {
  try {
    const data = await PL.api('GET', '/api/moh-admin/analytics');

    document.getElementById('statPatients').textContent   = data.totals.patients;
    document.getElementById('statDoctors').textContent    = data.totals.doctors;
    document.getElementById('statFacilities').textContent = data.totals.facilities;
    document.getElementById('statUsers').textContent      = data.totals.users;

    document.getElementById('consentApproved').textContent = data.consents.approved;
    document.getElementById('consentPending').textContent  = data.consents.pending;
    document.getElementById('consentExpired').textContent  = data.consents.expired;
    document.getElementById('activeUsers').textContent     = data.active_users;
  } catch (err) {
    console.error('Analytics error:', err);
  }
}

// ── Load Audit Logs ───────────────────────────────────────────
async function loadAuditLogs(page = 1) {
  const action  = document.getElementById('filterAction').value.trim();
  const outcome = document.getElementById('filterOutcome').value;
  const role    = document.getElementById('filterRole').value;

  let url = `/api/moh-admin/audit-logs?page=${page}&per_page=15`;
  if (action)  url += `&action=${encodeURIComponent(action)}`;
  if (outcome) url += `&outcome=${encodeURIComponent(outcome)}`;
  if (role)    url += `&role=${encodeURIComponent(role)}`;

  try {
    const data = await PL.api('GET', url);
    renderLogs(data.data);
    renderPagination(data);
    currentPage = page;
  } catch (err) {
    document.getElementById('logsBody').innerHTML =
      `<tr><td colspan="5" class="text-center text-danger">${err.message}</td></tr>`;
  }
}

function renderLogs(logs) {
  const tbody = document.getElementById('logsBody');
  if (!logs.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No logs found</td></tr>';
    return;
  }

  tbody.innerHTML = logs.map(log => {
    const badgeClass = log.outcome === 'success' ? 'badge-success'
                     : log.outcome === 'denied'  ? 'badge-denied'
                     : 'badge-failure';
    const actor = log.actor ? `${log.actor.name} (${log.actor.role})` : 'System';
    const ts    = new Date(log.timestamp).toLocaleString();

    return `<tr>
      <td class="small text-muted">${ts}</td>
      <td>${actor}</td>
      <td><code>${log.action}</code></td>
      <td><span class="badge ${badgeClass}">${log.outcome}</span></td>
      <td class="small text-muted">${log.entity_type || '—'}</td>
    </tr>`;
  }).join('');
}

function renderPagination(data) {
  const el = document.getElementById('pagination');
  if (data.last_page <= 1) { el.innerHTML = ''; return; }

  let html = '';
  if (data.current_page > 1) {
    html += `<button class="btn btn-sm btn-outline-secondary me-1" onclick="loadAuditLogs(${data.current_page - 1})">← Prev</button>`;
  }
  html += `<span class="small text-muted me-1">Page ${data.current_page} of ${data.last_page}</span>`;
  if (data.current_page < data.last_page) {
    html += `<button class="btn btn-sm btn-outline-secondary" onclick="loadAuditLogs(${data.current_page + 1})">Next →</button>`;
  }
  el.innerHTML = html;
}

// Filter button
document.getElementById('applyFilters').addEventListener('click', () => loadAuditLogs(1));

// Init
loadAnalytics();
loadAuditLogs(1);
