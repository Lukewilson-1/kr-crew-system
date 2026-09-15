/* ════════════════════════════════════════════════════════════════════════
 * NOTIFICATION BELL — shared by the Running Room Register and the Crew app.
 * Polls /running-rooms/api/notifications and renders a dropdown of recent
 * notifications. Clicking the bell marks everything as read.
 * ════════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const bell = document.getElementById('krBell');
  if (!bell) return;

  const btn = document.getElementById('krBellBtn');
  const count = document.getElementById('krBellCount');
  const panel = document.getElementById('krBellPanel');
  const list = document.getElementById('krBellList');
  const mark = document.getElementById('krBellMark');
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  function timeAgo(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '';
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return d.toLocaleDateString('en-KE', { day: '2-digit', month: 'short' });
  }

  async function fetchNotifs() {
    const resp = await fetch('/running-rooms/api/notifications', { cache: 'no-store' });
    if (!resp.ok) throw new Error('fetch failed');
    return resp.json();
  }

  async function markAllRead() {
    try {
      await fetch('/running-rooms/api/notifications/read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
        },
        body: JSON.stringify({}),
      });
      await render();
    } catch (e) { /* ignore */ }
  }

  // Check the crew member out of the running room right from the bell. Mirrors the
  // room-desks checkout action; attendance_id is the attendance record to close out.
  async function bellCheckout(attendanceId) {
    if (!attendanceId) return;
    try {
      await fetch('/running-rooms/api/records/' + Number(attendanceId) + '/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
        },
        body: JSON.stringify({}),
      });
    } catch (e) {
      alert('Checkout failed. Please use the running room desk.');
      return;
    }
    await markAllRead();
    await render();
  }

  async function render() {
    let data;
    try {
      data = await fetchNotifs();
    } catch (e) {
      return;
    }
    const unread = Number(data.unread_count || 0);
    const items = data.notifications || [];

    if (unread > 0) {
      count.hidden = false;
      count.textContent = unread > 99 ? '99+' : String(unread);
    } else {
      count.hidden = true;
    }

    if (!items.length) {
      list.innerHTML = '<div class="kr-bell-empty">No notifications yet.</div>';
      return;
    }

    list.innerHTML = items.map((n) => `
      <div class="kr-bell-item${n.read ? ' read' : ''}">
        <div class="kr-bell-item-dot"></div>
        <div class="kr-bell-item-body">
          <div class="kr-bell-item-title">${esc(n.title)}</div>
          ${n.body ? `<div class="kr-bell-item-text">${esc(n.body)}</div>` : ''}
          ${n.data && n.data.action === 'checkout' && n.data.attendance_id
            ? `<button class="kr-bell-action" onclick="bellCheckout(${Number(n.data.attendance_id)})">Check out crew</button>`
            : ''}
          <div class="kr-bell-item-time">${esc(timeAgo(n.created_at))}</div>
        </div>
      </div>`).join('');
  }

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (panel.hidden) {
      render();
      panel.hidden = false;
    } else {
      panel.hidden = true;
    }
  });

  mark.addEventListener('click', (e) => {
    e.stopPropagation();
    markAllRead();
  });

  document.addEventListener('click', () => {
    panel.hidden = true;
  });

  render();
  setInterval(render, 60000);
})();
