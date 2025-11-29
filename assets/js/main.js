// assets/js/main.js
// Unified front-end JS for Signup / Signin / Profile pages
(function () {
  "use strict";

  /* --------------------------
     Helpers
  ---------------------------*/
  function qs(sel) { return document.querySelector(sel); }
  function qsa(sel) { return Array.from(document.querySelectorAll(sel)); }

  function showToast(msg, timeout = 2200) {
    let wrap = document.getElementById("toastWrap");
    if (!wrap) {
      wrap = document.createElement("div");
      wrap.id = "toastWrap";
      wrap.style.position = "fixed";
      wrap.style.right = "18px";
      wrap.style.bottom = "18px";
      wrap.style.zIndex = 99999;
      document.body.appendChild(wrap);
    }
    const t = document.createElement("div");
    t.textContent = msg;
    t.style.marginTop = "8px";
    t.style.padding = "10px 14px";
    t.style.borderRadius = "8px";
    t.style.background = "rgba(0,0,0,0.75)";
    t.style.color = "#fff";
    t.style.boxShadow = "0 6px 20px rgba(0,0,0,0.45)";
    wrap.appendChild(t);
    setTimeout(() => { t.style.opacity = "0.06"; t.style.transform = "translateY(-6px)"; }, timeout - 400);
    setTimeout(() => t.remove(), timeout);
  }

 function postForm(url, obj) {
  const form = new URLSearchParams();
  for (const k in obj) form.append(k, obj[k]);

  return fetch(url, {
    method: "POST",
    body: form,
    headers: { "Accept": "application/json" }
  })
  .then(async (res) => {
    const ct = res.headers.get("content-type") || "";

    // Safe JSON parsing
    if (ct.includes("application/json")) {
      const txt = await res.text();   // read ONCE
      try {
        return JSON.parse(txt);
      } catch (e) {
        console.error("Invalid JSON:", txt);
        return { status: "error", message: "Invalid JSON", raw: txt };
      }
    }

    // fallback
    return res.text();
  })
  .catch(err => {
    console.error("Network error:", err);
    return { status: "error", message: "Network error" };
  });
}

  function getSession() {
    return localStorage.getItem("session_id") || null;
  }
  function setSession(token) {
    if (token) localStorage.setItem("session_id", token);
    else localStorage.removeItem("session_id");
  }

  function wireFloatingInputs() {
    qsa(".field .input").forEach(inp => {
      const parent = inp.closest('.field');
      function toggle() {
        if (!parent) return;
        if (inp.value && inp.value.trim() !== '') parent.classList.add('filled');
        else parent.classList.remove('filled');
      }
      inp.addEventListener('input', toggle);
      inp.addEventListener('blur', toggle);
      toggle();
    });
  }

  /* --------------------------
     SIGNUP page
     - calls backend/register.php
     expected POST params: name, email, phone, password
  ---------------------------*/
  function initSignup() {
    const form = qs('#signupForm');
    if (!form) return;
    wireFloatingInputs();

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const name = (qs('#s_name') || {}).value || '';
      const email = (qs('#s_email') || {}).value || '';
      const phone = (qs('#s_phone') || {}).value || '';
      const password = (qs('#s_password') || {}).value || '';

      if (!name || !email || !phone || !password) {
        showToast('Please fill all fields');
        return;
      }

      showToast('Registering...');
      postForm('php/register.php', { name, email, phone, password })
        .then((res) => {
          // backend may return JSON or text; handle both
          if (res && typeof res === 'object') {
            if (res.status && res.status === 'success') {
              showToast('Account created — redirecting to sign in');
              setTimeout(() => { window.location.href = 'index.html'; }, 900);
            } else {
              showToast(res.message || 'Registration failed');
            }
          } else {
            // text response
            const text = ('' + res).toLowerCase();
            if (text.includes('success')) {
              showToast('Account created — redirecting to sign in');
              setTimeout(() => { window.location.href = 'index.html'; }, 900);
            } else {
              showToast(res || 'Registration failed');
            }
          }
        })
        .catch((err) => {
          console.error(err);
          showToast('Network error during registration');
        });
    });
  }

  /* --------------------------
     SIGNIN page
     - calls backend/login.php
     expected POST params: identifier (email or phone), password
     backend returns JSON with session_id on success
  ---------------------------*/
  function initSignin() {
    const form = qs('#signinForm');
    if (!form) return;
    wireFloatingInputs();

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const email = (qs('#i_email') || {}).value || '';
      const password = (qs('#i_password') || {}).value || '';

      if (!email || !password) {
        showToast('Enter email and password');
        return;
      }

      showToast('Signing in...');
      postForm('php/login.php', { identifier: email, password: password })
        .then((res) => {
          if (res && typeof res === 'object' && res.status === 'success' && res.session_id) {
            setSession(res.session_id);
            showToast('Signed in — loading profile');
            setTimeout(() => window.location.href = 'profile.html', 700);
            return;
          }
          // fallback: string responses
          if (typeof res === 'string' && res.toLowerCase().includes('success')) {
            showToast('Signed in — loading profile');
            // might not have session; but try redirect
            setTimeout(() => window.location.href = 'profile.html', 700);
            return;
          }
          showToast((res && res.message) ? res.message : 'Invalid credentials');
        })
        .catch(err => {
          console.error(err);
          showToast('Login failed (network)');
        });
    });
  }

  /* --------------------------
     PROFILE page
     - calls backend/getprofile.php (POST: session_id)
     - update via backend/updateprofile.php (POST: session_id, email, phone, altphone, age, dob, contact)
  ---------------------------*/
  function initProfile() {
    const wrapper = qs('.profile-card');
    if (!wrapper) return;
    wireFloatingInputs();

    const p_avatar = qs('#p_avatar');
    const p_name = qs('#p_name');
    const p_email = qs('#p_email');
    const p_lastlogin = qs('#p_lastlogin');

    const input_name = qs('#input_name');
    const input_phone = qs('#input_phone');

    const editBtn = qs('#editBtn');
    const editBtnTop = qs('#editBtnTop');

    const uploadBtn = qs('#uploadBtn');
    const removeAvatarBtn = qs('#removeAvatarBtn');
    const avatarInput = qs('#avatarInput');

    const currentPw = qs('#currentPw');
    const newPw = qs('#newPw');
    const confirmPw = qs('#confirmPw');
    const changePwBtn = qs('#changePwBtn');

    const deleteBtn = qs('#deleteBtn');

    const logoutButtons = qsa('#pageLogout, button[onclick*="index.html"], button[title="Logout"]');

    // require session token
    const session = getSession();
    if (!session) {
      // not authenticated
      window.location.href = 'index.html';
      return;
    }

    function renderProfile(data) {
      // data might be a MongoDB document returned as JSON object
      // expected fields: uid, email, phone, altphone, age, dob, contact, name, avatar
      const nameVal = data.name || data.email || '';
      p_name.textContent = nameVal;
      p_email.textContent = data.email || '';
      p_lastlogin.textContent = "Last login: " + new Date().toLocaleString();

      input_name.value = data.name || '';
      input_phone.value = data.phone || (data.contact || '');
      if (p_avatar) p_avatar.src = data.avatar || data.avatar_base64 || "https://via.placeholder.com/140?text=Avatar";
      // ensure floating label classes are toggled
      qsa('.field .input').forEach(inp => {
        const parent = inp.closest('.field');
        if (parent) {
          if (inp.value && inp.value.trim() !== '') parent.classList.add('filled');
          else parent.classList.remove('filled');
        }
      });
    }

    // Fetch profile from backend
    showToast('Loading profile...');
    postForm('php/getprofile.php', { session_id: session })
      .then((res) => {
        if (!res) {
          showToast('Failed to load profile');
          return;
        }
        // res might be raw JSON document
        // if the backend returned a MongoDB BSON style object with _id, convert safe fields
        if (typeof res === 'object') {
          // normalize object to plain data
          renderProfile(res);
        } else {
          try {
            const parsed = JSON.parse(res);
            renderProfile(parsed);
          } catch (e) {
            console.error('Unexpected profile response', res);
            showToast('Invalid profile data');
          }
        }
      })
      .catch(err => {
        console.error(err);
        showToast('Unable to fetch profile');
      });

    // EDIT / SAVE profile
    let editing = false;
    function toggleEditing(on) {
      editing = !!on;
      if (editing) {
        editBtn.textContent = "Save";
        input_name.removeAttribute('readonly');
        input_phone.removeAttribute('readonly');
        if (editBtnTop) editBtnTop.classList.remove('hidden');
        wrapper.classList.add('editing');
      } else {
        editBtn.textContent = "Edit";
        input_name.setAttribute('readonly', true);
        input_phone.setAttribute('readonly', true);
        if (editBtnTop) editBtnTop.classList.add('hidden');
        wrapper.classList.remove('editing');
      }
    }

    if (editBtn) {
      editBtn.addEventListener('click', () => {
        if (!editing) toggleEditing(true);
        else saveProfile();
      });
    }
    if (editBtnTop) editBtnTop.addEventListener('click', () => editing && saveProfile());

    function saveProfile() {
      const email = (p_email && p_email.textContent) ? p_email.textContent : "";
      const emailInput = email; // email not editable in UI - we keep what server returned
      const name = (input_name && input_name.value) ? input_name.value.trim() : "";
      const phone = (input_phone && input_phone.value) ? input_phone.value.trim() : "";

      showToast('Saving profile...');
      postForm('php/updateprofile.php', {
        session_id: session,
        email: emailInput,
        phone: phone,
        name:name,
        altphone: '',
        age: '',
        dob: '',
        contact: ''
      }).then(res => {
        // response is likely a plaintext success message
        if (typeof res === 'string') {
          if (res.toLowerCase().includes('success')) {
            showToast('Profile saved');
            toggleEditing(false);
            // reload profile to show server data
            setTimeout(() => location.reload(), 800);
            return;
          }
        } else if (typeof res === 'object' && (res.status === 'success' || res.message)) {
          showToast(res.message || 'Profile saved');
          toggleEditing(false);
          setTimeout(() => location.reload(), 800);
          return;
        }
        showToast('Profile update failed');
      }).catch(err => {
        console.error(err);
        showToast('Save failed (network)');
      });
    }

    // AVATAR upload (client-side preview + local cache only)
    if (uploadBtn && avatarInput) {
      uploadBtn.addEventListener('click', () => avatarInput.click());
      avatarInput.addEventListener('change', function () {
        const f = this.files && this.files[0];
        if (!f) return;
        const reader = new FileReader();
        reader.onload = function (e) {
          if (p_avatar) p_avatar.src = e.target.result;
          // optionally persist avatar client-side so user sees it after reload:
          localStorage.setItem('client_avatar', e.target.result);
          showToast('Avatar updated (local only)');
        };
        reader.readAsDataURL(f);
      });
    }
    // restore client-side avatar if saved
    const localAv = localStorage.getItem('client_avatar');
    if (localAv && p_avatar) p_avatar.src = localAv;

    if (removeAvatarBtn) {
      removeAvatarBtn.addEventListener('click', function () {
        localStorage.removeItem('client_avatar');
        if (p_avatar) p_avatar.src = "https://via.placeholder.com/140?text=Avatar";
        showToast('Avatar removed (local)');
      });
    }

    // CHANGE PASSWORD - NOTE: This requires a backend endpoint to persist securely.
    // CHANGE PASSWORD
if (changePwBtn) {
  changePwBtn.addEventListener('click', function () {

    const cur = (currentPw && currentPw.value) ? currentPw.value.trim() : '';
    const nw = (newPw && newPw.value) ? newPw.value.trim() : '';
    const cf = (confirmPw && confirmPw.value) ? confirmPw.value.trim() : '';

    if (!cur || !nw || !cf) {
      showToast('Fill all password fields');
      return;
    }

    if (nw.length < 6) {
      showToast('New password must be at least 6 characters');
      return;
    }

    if (nw !== cf) {
      showToast('Passwords do not match');
      return;
    }

    // 🔥 CALLING BACKEND PASSWORD CHANGE API
    postForm('php/changePassword.php', {
      session_id: session,
      current_password: cur,
      new_password: nw
    })
    .then(res => {
      if (res.status === 'success') {
        showToast('Password updated successfully');
        currentPw.value = "";
        newPw.value = "";
        confirmPw.value = "";
      } else {
        showToast(res.message || 'Failed to update password');
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Network error while updating password');
    });
  });
}


    // DELETE account - requires server endpoint; we'll just show message
   deleteBtn.onclick = function(){
  if (!confirm("Delete your account permanently?")) return;

  postForm('php/deleteAccount.php', {
    session_id: session
  }).then(res => {
    if (res.status === 'success') {
      showToast("Account deleted");
      setSession(null);
      setTimeout(() => window.location.href = "index.html", 800);
    } else {
      showToast(res.message || "Failed to delete account");
    }
  });
};
  }

  /* --------------------------
     Auto init depending on page
  ---------------------------*/
  document.addEventListener('DOMContentLoaded', function () {
    initSignup();
    initSignin();
    initProfile();
  });

})();
