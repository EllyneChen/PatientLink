document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const alert = document.getElementById('loginAlert');
  const btn   = document.getElementById('loginBtn');
  alert.classList.add('d-none');
  btn.disabled = true;
  btn.textContent = 'Logging in…';

  try {
    const data = await PL.api('POST', '/api/login', {
      email:    document.getElementById('email').value.trim(),
      password: document.getElementById('password').value,
    });

    if (data.user.role !== 'doctor') {
      throw new Error('This portal is for doctors only.');
    }

    PL.saveSession(data.token, data.user);
    window.location.href = 'dashboard.html';
  } catch (err) {
    alert.textContent = err.message || 'Login failed';
    alert.classList.remove('d-none');
    btn.disabled = false;
    btn.textContent = 'Log in';
  }
});
