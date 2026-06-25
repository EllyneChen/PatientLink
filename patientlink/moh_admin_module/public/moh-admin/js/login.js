document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const alertEl = document.getElementById('loginAlert');
  const btn     = document.getElementById('loginBtn');
  alertEl.classList.add('d-none');
  btn.disabled = true;
  btn.textContent = 'Logging in…';

  try {
    const data = await PL.api('POST', '/api/login', {
      email:    document.getElementById('email').value.trim(),
      password: document.getElementById('password').value,
    });

    if (data.user.role !== 'moh_admin') {
      throw new Error('This portal is for MOH Administrators only.');
    }

    PL.saveSession(data.access_token, data.user);
    window.location.href = 'dashboard.html';
  } catch (err) {
    alertEl.textContent = err.message || 'Login failed';
    alertEl.classList.remove('d-none');
    btn.disabled = false;
    btn.textContent = 'Log in';
  }
});
