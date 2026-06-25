document.addEventListener('DOMContentLoaded', () => {
  PL.requireAuth();

  const user = PL.getUser();
  if (user) document.getElementById('navUserName').textContent = user.name;

  // ── Staff list ──────────────────────────────────────────────
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
              onclick="toggleStaff('${s.doctor_id}', ${s.is_active})">
              ${s.is_active ? 'Deactivate' : 'Activate'}
            </button>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${err.message}</td></tr>`;
    }
  }

  // ── Toggle staff active/inactive ────────────────────────────
  window.toggleStaff = async (doctorId, isCurrentlyActive) => {
    const action = isCurrentlyActive ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} this account?`)) return;

    try {
      await PL.api('PUT', `/api/facility-admin/staff/${doctorId}/${action}`);
      loadStaff();
    } catch (err) {
      alert(err.message);
    }
  };

  // ── Create staff form ───────────────────────────────────────
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

      // Close modal, reset form, reload staff list
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

  // ── Logout ──────────────────────────────────────────────────
  document.getElementById('logoutBtn').addEventListener('click', async () => {
    try { await PL.api('POST', '/api/logout'); } catch (e) {}
    PL.clearSession();
    window.location.href = 'login.html';
  });

  loadStaff();
});
