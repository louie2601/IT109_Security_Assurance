(() => {
  'use strict';

  const form = document.getElementById('loginForm');
  if (!form) return;

  const username = form.elements['username'];
  const password = form.elements['password'];
  const togglePassword = document.querySelector('.toggle-password');

  if (togglePassword) {
    togglePassword.addEventListener('click', () => {
      if (password.type === 'password') {
        password.type = 'text';
        togglePassword.textContent = '🙈';
      } else {
        password.type = 'password';
        togglePassword.textContent = '👁️';
      }
    });
  }
  const forgotPassword = document.querySelector('.forgot-password');
  const container = document.querySelector('.login-container'); // ✅ updated selector
  const loginBtn = form.querySelector('button[type="submit"]');
  const registerLink = form.querySelector('a[href="registration.php"]');

  // Inline error below the title
  let globalError = document.querySelector('.login-error');
  if (!globalError) {
    globalError = document.createElement('div');
    globalError.className = 'login-error';
    const heading = container.querySelector('h2');
    if (heading) heading.insertAdjacentElement('afterend', globalError);
  }

  const setGlobalError = (msg = '') => (globalError.textContent = msg);
  const setFieldError = (input, message) => {
    let errorDiv;
    if (input.id === 'username') {
      errorDiv = document.getElementById('username_error');
    } else if (input.id === 'password') {
      errorDiv = document.getElementById('password_error');
    }

    if (errorDiv) {
      errorDiv.textContent = message || '';
      input.classList.toggle('invalid', !!message);
    }
  };

  // Prevent double spaces in username
  username.addEventListener('input', () => {
    username.value = username.value.replace(/\s{2,}/g, ' ');
  });

  let errorCount = parseInt(localStorage.getItem('loginErrorCount')) || 0;
  let lockUntil = parseInt(localStorage.getItem('lockUntil')) || 0;
  let isLocked = false;

  // Hide forgot password initially
  if (forgotPassword) forgotPassword.style.display = 'none';

  // Check if user is already locked
  const now = Date.now();
  if (lockUntil > now) lockUser(Math.ceil((lockUntil - now) / 1000));
  else resetLockState();

  const isValidUsername = (u) => /^[A-Za-z0-9_]{3,20}$/.test(u);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (isLocked) {
      setGlobalError('⏳ Please wait for the timer to end before trying again.');
      return;
    }

    let ok = true;
    setGlobalError('');

    // Username validation
    if (!username.value.trim()) {
      setFieldError(username, 'Username required.');
      ok = false;
    } else if (!isValidUsername(username.value.trim())) {
      setFieldError(username, 'Username must be 3–20 chars only.');
      ok = false;
    } else setFieldError(username, '');

    // Password validation
    if (!password.value.trim()) {
      setFieldError(password, 'Password required.');
      ok = false;
    } else if (password.value.length < 6) {
      setFieldError(password, 'Password must be at least 6 characters.');
      ok = false;
    } else setFieldError(password, '');

    if (!ok) {
      incrementError();
      return;
    }

    try {
      const formData = new FormData(form);
      const res = await fetch(form.action, { method: 'POST', body: formData });
      const text = await res.text();

      if (text.includes('Invalid username or password')) {
        incrementError();
        setGlobalError('Invalid username or password!');
        username.value = '';
        password.value = '';
      } else if (text.includes('dashboard.php')) {
        resetLockState();
        window.location.href = 'dashboard.php';
      } else {
        window.location.reload();
      }
    } catch (err) {
      console.error('Login error:', err);
      setGlobalError('Something went wrong. Please try again.');
    }
  });

  // --- Functions ---
  function incrementError() {
    errorCount++;
    localStorage.setItem('loginErrorCount', errorCount);

    // Show forgot password only after 2 failed attempts
    if (errorCount >= 2 && forgotPassword) forgotPassword.style.display = 'block';

    // Apply lock rules
    if (errorCount === 3) startLock(15);
    else if (errorCount === 6) startLock(30);
    else if (errorCount >= 9) startLock(60);
  }

  function startLock(seconds) {
    if (errorCount >= 9) seconds = 60; // always 60s after 9th attempt
    const until = Date.now() + seconds * 1000;
    localStorage.setItem('lockUntil', until);
    lockUser(seconds);
  }

  function lockUser(seconds) {
    isLocked = true;
    username.disabled = true;
    password.disabled = true;
    if (loginBtn) loginBtn.disabled = true;
    if (registerLink) {
      registerLink.style.pointerEvents = 'none';
      registerLink.style.opacity = '0.5';
    }
    if (forgotPassword) {
      forgotPassword.style.pointerEvents = 'none';
      forgotPassword.style.opacity = '0.5';
    }

    const endTime = Date.now() + seconds * 1000;
    const timer = setInterval(() => {
      const remaining = Math.ceil((endTime - Date.now()) / 1000);
      if (remaining <= 0) {
        clearInterval(timer);
        unlockUser();
      } else {
        setGlobalError(`⏳ Please wait ${remaining}s before trying again...`);
      }
    }, 1000);
  }

  function unlockUser() {
    username.disabled = false;
    password.disabled = false;
    if (loginBtn) loginBtn.disabled = false;
    if (registerLink) {
      registerLink.style.pointerEvents = 'auto';
      registerLink.style.opacity = '1';
    }
    if (forgotPassword) {
      forgotPassword.style.pointerEvents = 'auto';
      forgotPassword.style.opacity = '1';
    }
    isLocked = false;
    setGlobalError('');
  }

  function resetLockState() {
    localStorage.removeItem('lockUntil');
    localStorage.removeItem('loginErrorCount');
    errorCount = 0;
    setGlobalError('');
    if (forgotPassword) forgotPassword.style.display = 'none';
  }

  // Disable Browser Back Button
  window.history.pushState(null, '', window.location.href);
  window.addEventListener('popstate', function () {
    window.history.pushState(null, '', window.location.href);
  });
})();


