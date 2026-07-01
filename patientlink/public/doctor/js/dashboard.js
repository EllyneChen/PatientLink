const user = PL.getUser();
if (!user || user.role !== 'doctor') {
  window.location.href = 'login.html';
}
document.getElementById('doctorName').textContent = user.name;

document.getElementById('logoutBtn').addEventListener('click', async () => {
  try { await PL.api('POST', '/api/logout'); } catch (_) {}
  PL.clearSession();
  window.location.href = 'login.html';
});

let currentNupi = null;
let currentConsent = null;

document.getElementById('searchBtn').addEventListener('click', async () => {
  const nupi = document.getElementById('nupiInput').value.trim();
  const alertEl = document.getElementById('searchAlert');
  if (!nupi) return;
  alertEl.className = 'alert d-none';
  try {
    const patient = await PL.api('GET', '/api/doctor/search?nupi=' + encodeURIComponent(nupi));
    currentNupi = nupi;
    document.getElementById('patientName').textContent = patient.name;
    document.getElementById('patientNupi').textContent = patient.nupi;
    document.getElementById('patientDob').textContent = patient.dob ? patient.dob.split('T')[0] : '-';
    document.getElementById('patientPhone').textContent = patient.phone;
    document.getElementById('patientSection').classList.remove('d-none');
    document.getElementById('otpSection').classList.add('d-none');
    document.getElementById('recordsSection').classList.add('d-none');
    document.getElementById('addRecordSection').classList.add('d-none');
  } catch (err) {
    alertEl.textContent = err.message || 'Patient not found';
    alertEl.className = 'alert alert-danger';
    document.getElementById('patientSection').classList.add('d-none');
  }
});

document.getElementById('requestConsentBtn').addEventListener('click', async () => {
  const btn = document.getElementById('requestConsentBtn');
  btn.disabled = true;
  btn.textContent = 'Sending OTP...';
  try {
    const res = await PL.api('POST', '/api/doctor/consent/request', { nupi: currentNupi });
    currentConsent = res.consent_id;
    document.getElementById('otpDemo').textContent = 'Demo OTP (simulated SMS): ' + res.otp_demo;
    document.getElementById('otpSection').classList.remove('d-none');
    document.getElementById('recordsSection').classList.add('d-none');
    document.getElementById('addRecordSection').classList.add('d-none');
  } catch (err) {
    const alertEl = document.getElementById('searchAlert');
    alertEl.textContent = err.message || 'Failed to send OTP';
    alertEl.className = 'alert alert-danger';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Request Access (Send OTP)';
  }
});

document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
  const otp = document.getElementById('otpInput').value.trim();
  const alertEl = document.getElementById('otpAlert');
  const btn = document.getElementById('verifyOtpBtn');
  if (!otp) return;
  btn.disabled = true;
  btn.textContent = 'Verifying...';
  alertEl.className = 'alert d-none';
  try {
    await PL.api('POST', '/api/doctor/consent/verify', { consent_id: currentConsent, otp: otp });
    await loadRecords();
    document.getElementById('recordsSection').classList.remove('d-none');
    document.getElementById('otpSection').classList.add('d-none');
  } catch (err) {
    alertEl.textContent = err.message || 'Invalid OTP';
    alertEl.className = 'alert alert-danger';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Verify OTP';
  }
});

async function loadRecords() {
  const data = await PL.api('GET', '/api/doctor/records/' + encodeURIComponent(currentNupi));
  document.getElementById('recordsPatientName').textContent = data.patient.name;
  const list = document.getElementById('recordsList');
  if (!data.records.length) {
    list.innerHTML = '<p class="text-muted">No health records found for this patient.</p>';
  } else {
    list.innerHTML = data.records.map(function(r) {
      var summary = {};
      try { summary = typeof r.summary === 'string' ? JSON.parse(r.summary) : r.summary; } catch(e) {}
      return '<div class="card mb-3 border-0 shadow-sm"><div class="card-body">' +
        '<div class="d-flex justify-content-between mb-2"><span class="fw-semibold text-muted small">DATE</span>' +
        '<span class="small text-muted">' + (r.created_at ? r.created_at.split('T')[0] : '-') + '</span></div>' +
        '<div class="row g-2">' +
        '<div class="col-6"><div class="small text-muted">DIAGNOSIS</div><div>' + (summary.diagnosis || '-') + '</div></div>' +
        '<div class="col-6"><div class="small text-muted">ALLERGIES</div><div>' + (summary.allergies || '-') + '</div></div>' +
        '<div class="col-6"><div class="small text-muted">DOCTOR</div><div>' + (summary.previous_doctor || '-') + '</div></div>' +
        '<div class="col-6"><div class="small text-muted">FACILITY</div><div>' + (summary.facility || '-') + '</div></div>' +
        '</div>' +
        '<div class="mt-2"><div class="small text-muted">CLINICAL NOTES</div><div>' + (summary.clinical_notes ? summary.clinical_notes.replace(/\n/g, '<br>') : '-') + '</div></div>' +
        '<div class="mt-3 text-end"><button class="btn btn-sm btn-pl-primary" onclick="exportSingleRecord(\'' + r.id + '\', \'' + currentNupi + '\')">Download Record (PDF)</button></div>' +
        '</div></div>';
    }).join('');
  }
  document.getElementById('recPrevDoctor').value = user.name;
  document.getElementById('addRecordSection').classList.remove('d-none');
}

document.getElementById('addRecordForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('addRecordBtn');
  const alertBox = document.getElementById('addRecordAlert');
  alertBox.className = 'alert d-none';
  btn.disabled = true;
  btn.textContent = 'Saving...';
  try {
    await PL.api('POST', '/api/doctor/records/' + encodeURIComponent(currentNupi), {
      diagnosis: document.getElementById('recDiagnosis').value.trim(),
      allergies: document.getElementById('recAllergies').value.trim(),
      previous_doctor: document.getElementById('recPrevDoctor').value.trim(),
      facility: document.getElementById('recFacility').value.trim(),
      date: document.getElementById('recDate').value,
      clinical_notes: document.getElementById('recClinicalNotes').value.trim()
    });
    document.getElementById('addRecordForm').reset();
    document.getElementById('recPrevDoctor').value = user.name;
    alertBox.textContent = 'Record saved successfully.';
    alertBox.className = 'alert alert-success';
    await loadRecords();
  } catch (err) {
    alertBox.textContent = err.message || 'Failed to save record.';
    alertBox.className = 'alert alert-danger';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save Record';
  }
});

document.getElementById('exportPdfBtn').addEventListener('click', async () => {
  const btn = document.getElementById('exportPdfBtn');
  const textEl = document.getElementById('exportPdfText');
  btn.disabled = true;
  textEl.textContent = 'Generating...';
  try {
    const token = PL.getToken();
    const res = await fetch('/api/doctor/records/' + encodeURIComponent(currentNupi) + '/export-pdf', {
      method: 'GET',
      headers: { 'Authorization': 'Bearer ' + token },
    });
    if (!res.ok) throw new Error('Failed to generate PDF');
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'patient-records-' + currentNupi + '.pdf';
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert(err.message || 'Failed to generate PDF');
  } finally {
    btn.disabled = false;
    textEl.textContent = 'Export My Records (PDF)';
  }
});

window.exportSingleRecord = async function(recordId, nupi) {
  try {
    const token = PL.getToken();
    const res = await fetch('/api/doctor/records/' + encodeURIComponent(nupi) + '/export-pdf/' + recordId, {
      method: 'GET',
      headers: { 'Authorization': 'Bearer ' + token },
    });
    if (!res.ok) throw new Error('Failed to generate PDF');
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'record-' + recordId + '.pdf';
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert(err.message || 'Failed to generate PDF');
  }
};