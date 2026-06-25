/**
 * api.js — shared helper for the PatientLink Patient Portal.
 *
 * Wraps fetch() so every page doesn't have to re-implement:
 *   - attaching the JWT as an Authorization: Bearer header
 *   - parsing JSON / surfacing Laravel validation error shapes
 *   - bouncing to login.html on a 401 (expired/invalid token)
 *
 * Paths are relative ('/api/...'), so this works unmodified whether
 * you're serving the app via `php artisan serve` (http://localhost:8000)
 * or through Apache from htdocs/patientlink/public — same origin either way.
 */

const PL = (() => {
  const TOKEN_KEY = "pl_token";
  const USER_KEY = "pl_user";

  function getToken() {
    return localStorage.getItem(TOKEN_KEY);
  }

  function getUser() {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  }

  function saveSession(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  }

  function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  }

  function requireAuth() {
    if (!getToken()) {
      window.location.href = "login.html";
    }
  }

  /**
   * api('/api/patient/profile', { method: 'PUT', body: {...} })
   * Returns the parsed JSON body. Throws an Error with a readable
   * .message on failure; validation errors land in err.fields.
   */
  async function api(path, { method = "GET", body = null } = {}) {
    const headers = { Accept: "application/json" };
    const token = getToken();
    if (token) headers["Authorization"] = `Bearer ${token}`;
    if (body) headers["Content-Type"] = "application/json";

    let res;
    try {
      res = await fetch(path, {
        method,
        headers,
        body: body ? JSON.stringify(body) : undefined,
      });
    } catch (networkErr) {
      throw new Error(
        "Couldn't reach the server. Is the Laravel app running?"
      );
    }


if (res.status === 401 && token) {
  clearSession();
  window.location.href = "login.html";
  throw new Error("Session expired. Please log in again.");
}

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      const err = new Error(
        data.error || data.message || `Request failed (${res.status})`
      );
      err.fields = data.errors || null;
      throw err;
    }

    return data;
  }

  return { getToken, getUser, saveSession, clearSession, requireAuth, api };
})();
