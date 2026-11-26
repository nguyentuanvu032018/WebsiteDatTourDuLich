'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const sendPresenceHeartbeat = () => {
    const body = new URLSearchParams({
      csrf_token: window.VIVUTOUR_CSRF || '',
      path: window.location.pathname
    });
    fetch('/presence/heartbeat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body,
      credentials: 'same-origin',
      keepalive: true
    }).catch(() => {});
  };
  sendPresenceHeartbeat();
  window.setInterval(sendPresenceHeartbeat, 25000);

  const path = window.location.pathname.replace(/\/$/, '') || '/';
  document.querySelectorAll('header .nav-link[href]').forEach((link) => {
    const href = (link.getAttribute('href') || '').replace(/\/$/, '') || '/';
    const active = href === '/' ? path === '/' : path === href || path.startsWith(`${href}/`);
    link.classList.toggle('active', active);
    if (active) link.setAttribute('aria-current', 'page');
  });

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle || '');
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      const icon = button.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye', input.type === 'password');
        icon.classList.toggle('bi-eye-slash', input.type !== 'password');
      }
    });
  });

  document.querySelectorAll('[data-copy-coupon]').forEach((button) => {
    button.addEventListener('click', async () => {
      const coupon = button.dataset.copyCoupon || '';
      const label = button.querySelector('span');
      if (!coupon || !label) return;

      try {
        await navigator.clipboard.writeText(coupon);
      } catch {
        const input = document.createElement('input');
        input.value = coupon;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
      }

      const original = button.dataset.copyLabel || 'Sao chép mã';
      label.textContent = 'Đã sao chép';
      button.classList.add('btn-success');
      button.classList.remove('btn-warning');
      window.setTimeout(() => {
        label.textContent = original;
        button.classList.add('btn-warning');
        button.classList.remove('btn-success');
      }, 1800);
    });
  });
});
