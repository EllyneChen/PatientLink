const PL = (() => {
  const TOKEN_KEY = 'pl_token';
  const USER_KEY  = 'pl_user';

  function getToken() { return localStorage.getItem(TOKEN_KEY); }
  function getUser()  { return JSON.parse(localStorage.getItem(USER_KEY) || 'null'); }

  function saveSession(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  }

  function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  }

  async function api(method, path, body) {
    const token = getToken();
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch(path, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });

    if (res.status === 401 && token) {
      clearSession();
      window.location.href = 'login.html';
      throw new Error('Session expired');
    }

    const data = await res.json();
    if (!res.ok) throw Object.assign(new Error(data.error || 'Request failed'), { status: res.status, data });
    return data;
  }

  return { api, getToken, getUser, saveSession, clearSession };
})();
document.getElementById('downloadReportBtn').addEventListener('click', async () => {
  const btn = document.getElementById('downloadReportBtn');
  const textEl = document.getElementById('downloadReportText');
  btn.disabled = true;
  textEl.textContent = 'Generating…';

  try {
    const token = PL.getToken();
    const res = await fetch('/api/moh-admin/report/pdf', {
      method: 'GET',
      headers: { 'Authorization': `Bearer ${token}` },
    });

    if (!res.ok) throw new Error('Failed to generate report');

    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'patientlink-moh-report.pdf';
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert(err.message || 'Failed to generate report');
  } finally {
    btn.disabled = false;
    textEl.textContent = 'Generate Report (PDF)';
  }
});