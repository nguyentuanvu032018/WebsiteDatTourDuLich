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

  const countElement = document.getElementById('adminPresenceCount');
  const connectionBadge = document.getElementById('presenceConnectionBadge');
  const pathLabels = {
    '/': 'Trang chủ',
    '/admin': 'Dashboard Admin',
    '/admin/dashboard': 'Dashboard Admin',
    '/tours': 'Danh sách tour',
    '/contact': 'Trang liên hệ',
    '/bookings/history': 'Lịch sử đặt tour'
  };
  let previousTotal = null;

  const formatPath = (path) => {
    if (pathLabels[path]) return pathLabels[path];
    if (path.startsWith('/tours/detail/')) return 'Chi tiết tour';
    if (path.startsWith('/admin/tours')) return 'Quản lý tour';
    if (path.startsWith('/admin/bookings')) return 'Quản lý đơn đặt';
    if (path.startsWith('/admin/promotions')) return 'Quản lý khuyến mãi';
    if (path.startsWith('/admin/contacts')) return 'Quản lý liên hệ';
    if (path.startsWith('/contacts/my')) return 'Liên hệ của tôi';
    if (path.startsWith('/bookings/')) return 'Đặt tour / thanh toán';
    return path || 'Trang chủ';
  };

  const showPresenceToast = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    wrapper.style.zIndex = '1080';
    wrapper.innerHTML = `
      <div class="toast border-0 shadow" role="status" aria-live="polite">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
          <span class="fw-semibold">Có người dùng vừa online</span>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Đóng"></button>
        </div>
      </div>`;
    document.body.appendChild(wrapper);
    const toastElement = wrapper.querySelector('.toast');
    const toast = new bootstrap.Toast(toastElement, { delay: 3500 });
    toastElement.addEventListener('hidden.bs.toast', () => wrapper.remove());
    toast.show();
  };

  const renderPresence = (data) => {
    const total = Number(data.totalOnline || 0);
    if (countElement) countElement.textContent = String(total);
    const totalElement = document.getElementById('presenceTotal');
    const loggedInElement = document.getElementById('presenceLoggedIn');
    const guestsElement = document.getElementById('presenceGuests');
    if (totalElement) totalElement.textContent = String(total);
    if (loggedInElement) loggedInElement.textContent = String(data.loggedInOnline || 0);
    if (guestsElement) guestsElement.textContent = String(data.guestOnline || 0);

    const recent = document.getElementById('presenceRecent');
    if (recent) {
      const items = Array.isArray(data.recent) ? data.recent : [];
      recent.innerHTML = items.length
        ? items.map((item) => `
          <div class="d-flex align-items-center gap-2 small">
            <span class="presence-dot flex-shrink-0"></span>
            <span class="fw-semibold text-truncate">${escapeHtml(item.displayName || 'Khách vãng lai')}</span>
            <span class="text-secondary text-truncate">${escapeHtml(formatPath(item.currentPath || '/'))}</span>
            <span class="text-secondary ms-auto text-nowrap">${Number(item.secondsAgo || 0)} giây trước</span>
          </div>`).join('')
        : '<div class="text-secondary small">Chưa ghi nhận hoạt động trực tuyến.</div>';
    }

    if (previousTotal !== null && total > previousTotal) showPresenceToast();
    previousTotal = total;
  };

  const escapeHtml = (value) => {
    const node = document.createElement('div');
    node.textContent = String(value);
    return node.innerHTML;
  };

  if (window.EventSource && countElement) {
    const source = new EventSource('/events/presence');
    source.addEventListener('open', () => {
      if (connectionBadge) {
        connectionBadge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill';
        connectionBadge.innerHTML = '<span class="presence-dot me-1"></span>SSE đã kết nối';
      }
    });
    source.addEventListener('presence.updated', (event) => {
      try { renderPresence(JSON.parse(event.data)); } catch {}
    });
    source.addEventListener('error', () => {
      if (connectionBadge) {
        connectionBadge.className = 'badge text-bg-secondary-subtle text-secondary border rounded-pill';
        connectionBadge.textContent = 'Đang kết nối lại SSE';
      }
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'Bạn chắc chắn muốn thực hiện thao tác này?')) {
        event.preventDefault();
      }
    });
  });
});
