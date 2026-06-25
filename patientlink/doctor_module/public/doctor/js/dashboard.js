// Auth guard
const user = PL.getUser();
if (!user || user.role !== 'doctor') {
  window.location.href = 'login.html';
}
document.getElementById('doctorName').textContent = user.name;

// Logout
document.getElementById('logoutBtn').addEventListener('click', async () => {
  try { await PL.api('POST', '/api/logout'); } catch (_) {}
  PL.clearSession();
  window.location.href = 'login.html';
});

let currentNupi    = null;
let currentConsent = null;

// ── Step 1: Search ────────────────────────────────────────────
document.getElementById('searchBtn').addEventListener('click', async () => {
  const nupi  = document.getElementById('nupiInput').value.trim();
  const alert = document.getElementById('searchAlert');
  if (!nupi) return;

  alert.className = 'alert d-none';

  try {
    const patient = await PL.api('GET', `/api/doctor/search?nupi=${encodeURIComponent(nupi)}`);
    currentNupi = nupi;

    document.getElementById('patientName').textContent  = patient.name;
    document.getElementById('patientNupi').textContent  = patient.nupi;
    document.getElementById('patientDob').textContent   = patient.dob;
    document.getElementById('patientPhone').textContent = patient.phone;

    document.getElementById('patientSection').classList.remove('d-none');
    document.getElementById('otpSection').classList.add('d-none');
    document.getElementById('recordsSection').classList.add('d-none');
  } catch (err) {
    alert.textContent = err.message || 'Patient not found';
    alert.className = 'alert alert-danger';
    document.getElementById('patientSection').classList.add('d-none');
  }
});

// ── Step 2: Request Consent ───────────────────────────────────
document.getElementById('requestConsentBtn').addEventListener('click', async () => {
  const btn = document.getElementById('requestConsentBtn');
  btn.disabled = true;
  btn.textContent = 'Sending OTP…';

  try {
    const res = await PL.api('POST', '/api/doctor/consent/request', { nupi: currentNupi });
    currentConsent = res.consent_id;

    // Show OTP demo box (remove in production)
    const demoBox = document.getElementById('otpDemo');
    demoBox.textContent = `🔑 Demo OTP (simulated SMS): ${res.otp_demo}`;

    document.getElementById('otpSection').classList.remove('d-none');
    document.getElementById('recordsSection').classList.add('d-none');
  } catch (err) {
    const alert = document.getElementById('searchAlert');
    alert.textContent = err.message || 'Failed to send OTP';
    alert.className = 'alert alert-danger';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Request Access (Send OTP)';
  }
});

// ── Step 3: Verify OTP ────────────────────────────────────────
document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
  const otp   = document.getElementById('otpInput').value.trim();
  const alert = document.getElementById('otpAlert');
  const btn   = document.getElementById('verifyOtpBtn');
  if (!otp) return;

  btn.disabled = true;
  btn.textContent = 'Verifying…';
  alert.className = 'alert d-none';

  try {
    await PL.api('POST', '/api/doctor/consent/verify', {
      consent_id: currentConsent,
      otp,
    });

    // Load records
    const data = await PL.api('GET', `/api/doctor/records/${encodeURIComponent(currentNupi)}`);
    document.getElementById('recordsPatientName').textContent = data.patient.name;

    const list = document.getElementById('recordsList');
    if (!data.records.length) {
      list.innerHTML = '<p class="text-muted">No health records found for this patient.</p>';
    } else {
      list.innerHTML = data.records.map(r => `
        <div class="record-card">
          <div class="d-flex justify-content-between mb-1">
            <span class="small text-muted">Facility ID: ${r.facility_id}</span>
            <span class="small text-muted">${new Date(r.created_at).toLocaleDateString()}</span>
          </div>
          <pre class="mb-0 small">${JSON.stringify(JSON.parse(r.summary || '{}'), null, 2)}</pre>
        </div>
      `).join('');
    }

    document.getElementById('recordsSection').classList.remove('d-none');
    document.getElementById('otpSection').classList.add('d-none');
  } catch (err) {
    alert.textContent = err.message || 'Invalid OTP';
    alert.className = 'alert alert-danger';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Verify OTP';
  }
});
