document.addEventListener('DOMContentLoaded', () => {
  PL.requireAuth();

  const user = PL.getUser();
  if (user) document.getElementById('navUserName').textContent = user.name;

  async function loadStaff() {
    const tbody = document.getElementById('staffTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Loading…</td></tr>';
    try {
      const data = await PL.api('GET', '/api/facility-admin/staff');
      if (!data.staff.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No staff found.</td></tr>';
        return;
      }
      tbody.innerHTML = data.staff.map(s => `
        <tr>
          <td>${s.name}</td>
          <td>${s.email}</td>
          <td>${s.licence_no}</td>
          <td>${s.specialisation || '—'}</td>
          <td>
            <span class="badge ${s.is_active ? 'bg-success' : 'bg-secondary'}">
              ${s.is_active ? 'Active' : 'Inactive'}
            </span>
            <button class="btn btn-sm btn-outline-${s.is_active ? 'danger' : 'success'} ms-2"
              onclick="toggleStaff('${s.doctor_id}', ${s.is_active ? 'true' : 'false'})">
              ${s.is_active ? 'Deactivate' : 'Activate'}
            </button>
            <button class="btn btn-sm btn-danger ms-2"
              onclick="deleteStaff('${s.doctor_id}', '${s.name}')">
              Delete
            </button>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${err.message}</td></tr>`;
    }
  }

  window.toggleStaff = async (doctorId, isCurrentlyActive) => {
    const action = isCurrentlyActive ? 'deactivate' : 'activate';
    const confirmed = await showConfirm(
      isCurrentlyActive ? 'Deactivate Staff Account' : 'Activate Staff Account',
      `Are you sure you want to ${action} this account?`,
      isCurrentlyActive ? '#dc3545' : '#198754'
    );
    if (!confirmed) return;
    try {
      await PL.api('PUT', `/api/facility-admin/staff/${doctorId}/${action}`);
      loadStaff();
    } catch (err) {
      showToast(err.message, '#dc3545');
    }
  };

  window.deleteStaff = async (doctorId, name) => {
    const confirmed = await showConfirm(
      'Delete Staff Account',
      `Permanently delete ${name}? This cannot be undone.`,
      '#dc3545'
    );
    if (!confirmed) return;
    try {
      await PL.api('DELETE', `/api/facility-admin/staff/${doctorId}`);
      showToast('Staff account deleted successfully');
      loadStaff();
    } catch (err) {
      showToast(err.message, '#dc3545');
    }
  };

  function showConfirm(title, message, btnColor) {
    return new Promise(resolve => {
      const overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;';
      overlay.innerHTML = `
        <div style="background:#fff;border-radius:12px;padding:1.75rem;max-width:360px;width:90%;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
          <h6 style="margin:0 0 0.5rem;font-weight:700;font-size:1rem;">${title}</h6>
          <p style="color:#6c757d;margin:0 0 1.5rem;font-size:0.9rem;">${message}</p>
          <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
            <button id="pl-cancel" style="padding:0.45rem 1rem;border:1px solid #dee2e6;border-radius:6px;background:#fff;cursor:pointer;font-size:0.875rem;">Cancel</button>
            <button id="pl-confirm" style="padding:0.45rem 1rem;border:none;border-radius:6px;background:${btnColor};color:#fff;cursor:pointer;font-size:0.875rem;font-weight:500;">Confirm</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      document.getElementById('pl-cancel').onclick = () => { overlay.remove(); resolve(false); };
      document.getElementById('pl-confirm').onclick = () => { overlay.remove(); resolve(true); };
    });
  }

  function showToast(message, color = '#198754') {
    const toast = document.createElement('div');
    toast.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;background:${color};color:#fff;padding:0.75rem 1.25rem;border-radius:8px;z-index:9999;font-size:0.875rem;box-shadow:0 4px 12px rgba(0,0,0,0.2);`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  document.getElementById('createStaffForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertBox = document.getElementById('createAlert');
    const btn      = document.getElementById('createBtn');
    alertBox.classList.add('d-none');
    btn.disabled = true;
    btn.textContent = 'Creating…';
    try {
      await PL.api('POST', '/api/facility-admin/staff', {
        name:           document.getElementById('staffName').value.trim(),
        email:          document.getElementById('staffEmail').value.trim(),
        password:       document.getElementById('staffPassword').value,
        licence_no:     document.getElementById('staffLicence').value.trim(),
        specialisation: document.getElementById('staffSpecialisation').value.trim(),
        phone:          document.getElementById('staffPhone').value.trim(),
      });
      bootstrap.Modal.getInstance(document.getElementById('createStaffModal')).hide();
      document.getElementById('createStaffForm').reset();
      loadStaff();
    } catch (err) {
      if (err.data && err.data.errors) {
        const first = Object.values(err.data.errors)[0][0];
        alertBox.textContent = first;
      } else {
        alertBox.textContent = err.message;
      }
      alertBox.classList.remove('d-none');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Create Account';
    }
  });

  document.getElementById('logoutBtn').addEventListener('click', async () => {
    try { await PL.api('POST', '/api/logout'); } catch (e) {}
    PL.clearSession();
    window.location.href = 'login.html';
  });

  loadStaff();
});