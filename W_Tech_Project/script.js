/**
 * Global JavaScript helper for TravelGuide (AJAX + Cookies + UI)
 */

// 1. Cookies Helper
const Cookies = {
  set(name, value, days = 7) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${encodeURIComponent(value)};expires=${d.toUTCString()};path=/`;
  },
  get(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
  },
  delete(name) {
    document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;`;
  }
};

// 2. Reusable AJAX Function
function sendAjax({ url, method = 'GET', data = null, onSuccess, onError }) {
  const xhr = new XMLHttpRequest();
  xhr.open(method, url, true);
  if (method === 'POST') {
    xhr.setRequestHeader('Content-Type', 'application/json');
  }

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const res = JSON.parse(xhr.responseText);
          if (onSuccess) onSuccess(res);
        } catch (e) {
          if (onError) onError('Invalid server response.');
        }
      } else {
        if (onError) onError(`HTTP Error: ${xhr.status}`);
      }
    }
  };

  xhr.send(data ? JSON.stringify(data) : null);
}

// 3. Toast Alert
let toastTimer;
function showToast(msg) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
}

// 4. Sync Navbar Auth State from Cookies
function syncNavbarAuth() {
  const user = Cookies.get('tg_user');
  const role = Cookies.get('tg_role');

  const guestView = document.getElementById('navGuestActions');
  const userView  = document.getElementById('navUserDisplay');

  if (user) {
    if (guestView) guestView.style.display = 'none';
    if (userView) {
      userView.style.display = 'flex';
      document.getElementById('navUserName').textContent = '👤 ' + user;
      document.getElementById('navUserRole').textContent = role || 'user';
    }
  } else {
    if (guestView) guestView.style.display = 'flex';
    if (userView) userView.style.display = 'none';
  }
}

// 5. Logout
function logoutUser() {
  sendAjax({
    url: 'api/logout.php',
    onSuccess: () => {
      Cookies.delete('tg_user');
      Cookies.delete('tg_email');
      Cookies.delete('tg_role');
      window.location.href = 'index.html';
    }
  });
}

// Initialize Navbar on page load
document.addEventListener('DOMContentLoaded', syncNavbarAuth);