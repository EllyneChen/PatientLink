/**
 * login.js — handles the patient login form on login.html.
 */
document.addEventListener("DOMContentLoaded", () => {
  // Already logged in? Skip straight to the profile.
  if (PL.getToken()) {
    window.location.href = "profile.html";
    return;
  }

  const form = document.getElementById("loginForm");
  const alertBox = document.getElementById("loginAlert");
  const loginBtn = document.getElementById("loginBtn");

  function showError(message) {
    alertBox.textContent = message;
    alertBox.classList.remove("d-none");
  }

  function hideError() {
    alertBox.classList.add("d-none");
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideError();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    loginBtn.disabled = true;
    loginBtn.textContent = "Logging in…";

    try {
      const data = await PL.api("/api/login", {
        method: "POST",
        body: { email, password },
      });

      if (data.role !== "patient") {
        showError(
          `This portal is for patients. Your account role is "${data.role}".`
        );
        loginBtn.disabled = false;
        loginBtn.textContent = "Log in";
        return;
      }

      PL.saveSession(data.access_token, data.user);
      window.location.href = "profile.html";
    } catch (err) {
      showError(err.message);
      loginBtn.disabled = false;
      loginBtn.textContent = "Log in";
    }
  });
});
