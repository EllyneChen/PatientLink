/**
 * profile.js — view + edit logic for profile.html (FR-P06).
 */
document.addEventListener("DOMContentLoaded", () => {
  PL.requireAuth();

  const user = PL.getUser();
  if (user) document.getElementById("navUserName").textContent = user.name;

  const loadingState = document.getElementById("loadingState");
  const profileFields = document.getElementById("profileFields");
  const pageAlert = document.getElementById("pageAlert");

  let currentProfile = null;

  function showPageAlert(message, type = "danger") {
    pageAlert.className = `alert alert-${type}`;
    pageAlert.textContent = message;
    pageAlert.classList.remove("d-none");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function renderProfile(p) {
    currentProfile = p;

    document.getElementById("nupiPill").textContent = `NUPI: ${p.nupi}`;
    document.getElementById("fieldName").textContent = p.name;
    document.getElementById("fieldEmail").textContent = p.email;
    document.getElementById("fieldDob").textContent = p.dob || "—";
    document.getElementById("fieldPhone").textContent = p.phone || "—";
    document.getElementById("fieldNokName").textContent = p.next_of_kin_name || "—";
    document.getElementById("fieldNokPhone").textContent = p.next_of_kin_phone || "—";

    const consentBadge = document.getElementById("fieldConsent");
    if (p.data_sharing_consent) {
      consentBadge.textContent = "Sharing enabled";
      consentBadge.className = "badge bg-success";
    } else {
      consentBadge.textContent = "Sharing disabled";
      consentBadge.className = "badge bg-secondary";
    }

    loadingState.classList.add("d-none");
    profileFields.classList.remove("d-none");
  }

  function populateEditForm(p) {
    document.getElementById("editName").value = p.name || "";
    document.getElementById("editDob").value = p.dob || "";
    document.getElementById("editPhone").value = p.phone || "";
    document.getElementById("editNokName").value = p.next_of_kin_name || "";
    document.getElementById("editNokPhone").value = p.next_of_kin_phone || "";
    document.getElementById("editConsent").checked = !!p.data_sharing_consent;
  }

  async function loadProfile() {
    try {
      const data = await PL.api("/api/patient/profile");
      renderProfile(data);
    } catch (err) {
      showPageAlert(err.message);
    }
  }

  document.getElementById("editBtn").addEventListener("click", () => {
    if (currentProfile) populateEditForm(currentProfile);
  });

  document.getElementById("editForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const editAlert = document.getElementById("editAlert");
    editAlert.classList.add("d-none");

    const saveBtn = document.getElementById("saveBtn");
    saveBtn.disabled = true;
    saveBtn.textContent = "Saving…";

    const payload = {
      name: document.getElementById("editName").value.trim(),
      dob: document.getElementById("editDob").value,
      phone: document.getElementById("editPhone").value.trim(),
      next_of_kin_name: document.getElementById("editNokName").value.trim(),
      next_of_kin_phone: document.getElementById("editNokPhone").value.trim(),
      data_sharing_consent: document.getElementById("editConsent").checked,
    };

    try {
      const data = await PL.api("/api/patient/profile", {
        method: "PUT",
        body: payload,
      });

      renderProfile(data.patient);

      const modalEl = document.getElementById("editModal");
      bootstrap.Modal.getInstance(modalEl).hide();

      showPageAlert("Profile updated successfully.", "success");
    } catch (err) {
      if (err.fields) {
        const firstMessage = Object.values(err.fields)[0][0];
        editAlert.textContent = firstMessage;
      } else {
        editAlert.textContent = err.message;
      }
      editAlert.classList.remove("d-none");
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = "Save changes";
    }
  });

  document.getElementById("logoutBtn").addEventListener("click", async () => {
    try {
      await PL.api("/api/logout", { method: "POST" });
    } catch (e) {
      // even if the API call fails (e.g. token already expired),
      // still clear the local session and send the user back to login.
    }
    PL.clearSession();
    window.location.href = "login.html";
  });

  loadProfile();
});
