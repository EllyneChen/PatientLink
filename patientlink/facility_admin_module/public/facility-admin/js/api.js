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

  function requireAuth() {
    if (!getToken()) window.location.href = 'login.html';
  }

  async function api(method, path, body) {
    const token = getToken();
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
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

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw Object.assign(new Error(data.error || data.message || 'Request failed'), { status: res.status, data });
    return data;
  }

  return { api, getToken, getUser, saveSession, clearSession, requireAuth };
})();
