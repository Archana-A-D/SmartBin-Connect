'use strict';

let pendingAvailId = null;
let pendingNewDate = null;
let allNotifications = [];  // cached
let notifDropdownOpen = false;

/* ══════════════════════════════════════════════
   INIT
══════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initPickupForm();
  initProfileForm();
  loadNotifications();                    // load on page open
  setInterval(loadNotifications, 60000);  // poll every 60s

  // Close dropdown when clicking outside
  document.addEventListener('click', e => {
    const bell     = document.getElementById('notifBellBtn');
    const dropdown = document.getElementById('notifDropdown');
    if (notifDropdownOpen && !bell.contains(e.target) && !dropdown.contains(e.target)) {
      closeNotifDropdown();
    }
  });
});

/* ══════════════════════════════════════════════
   PICKUP DATE HELPERS
══════════════════════════════════════════════ */
function getPickupSundaysInMonth(year, month) {
  const sundays = [];
  const d = new Date(year, month, 1);
  while (d.getDay() !== 0) d.setDate(d.getDate() + 1);
  while (d.getMonth() === month) { sundays.push(new Date(d)); d.setDate(d.getDate() + 7); }
  const result = [];
  if (sundays[1]) result.push(sundays[1]);
  if (sundays[3]) result.push(sundays[3]);
  return result;
}

function getNextPickupSunday(fromDate) {
  const base = new Date(fromDate);
  base.setHours(0, 0, 0, 0);
  for (let offset = 0; offset <= 4; offset++) {
    let y = base.getFullYear(), m = base.getMonth() + offset;
    if (m > 11) { y += Math.floor(m / 12); m = m % 12; }
    for (const c of getPickupSundaysInMonth(y, m)) {
      c.setHours(0, 0, 0, 0);
      if (c > base) return c;
    }
  }
  return null;
}

function formatPickupDate(d) {
  if (!d) return '—';
  return d.toLocaleDateString('en-IN', { weekday:'long', day:'numeric', month:'short', year:'numeric' });
}

function toLocalDateStr(d) {
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}

function parseDateStr(str) {
  if (!str) return null;
  const [y, m, d] = str.split('-').map(Number);
  return new Date(y, m - 1, d);
}

function setPickupDateHidden() {
  // Pickup date is set by the collector, not at submission time
  const hidden = document.getElementById('pickupDateInput');
  if (hidden) hidden.value = '';
}

/* ══════════════════════════════════════════════
   SIDEBAR / NAV
══════════════════════════════════════════════ */
function initNav() {
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();
      switchSection(item.dataset.section);
      closeSidebar();
    });
  });
}

function switchSection(name) {
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const nav = document.querySelector(`.nav-item[data-section="${name}"]`);
  if (nav) nav.classList.add('active');

  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  const sec = document.getElementById('section-' + name);
  if (sec) sec.classList.add('active');

  const titles = { request:'New Request', history:'My Requests', notifications:'Notifications', profile:'My Profile' };
  const el = document.getElementById('pageTitle');
  if (el) el.textContent = titles[name] || name;

  if (name === 'history')       loadRequests();
  if (name === 'notifications') renderFullNotifList(allNotifications);
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

/* ══════════════════════════════════════════════
   TOAST
══════════════════════════════════════════════ */
let toastTimer = null;
function showToast(msg, type = 'success', duration = 3500) {
  const t = document.getElementById('toast');
  if (!t) return;
  clearTimeout(toastTimer);
  t.textContent = msg;
  t.className = `toast ${type} show`;
  toastTimer = setTimeout(() => t.classList.remove('show'), duration);
}

/* ══════════════════════════════════════════════
   NOTIFICATIONS — LOAD
══════════════════════════════════════════════ */
function loadNotifications() {
  fetch('backend/get_user_notifications.php')
    .then(r => r.json())
    .then(data => {
      if (!Array.isArray(data)) return;
      allNotifications = data;

      const unread = data.filter(n => n.is_read == 0).length;
      updateNotifBadges(unread);
      renderDropdownNotifs(data.slice(0, 5)); // top 5 in dropdown

      // If notifications section is visible, re-render it too
      const sec = document.getElementById('section-notifications');
      if (sec && sec.classList.contains('active')) {
        renderFullNotifList(data);
      }

      // Show toast for brand new reschedule notifications
      const newReschedule = data.find(n => n.is_read == 0 && n.type === 'reschedule');
      if (newReschedule) {
        showToast('📅 Your pickup has been rescheduled. Tap 🔔 for details.', 'warning', 6000);
      }
    })
    .catch(() => {}); // silent fail for polling
}

/* ── Update badge numbers ─────────────────── */
function updateNotifBadges(count) {
  const bell       = document.getElementById('notifBellBtn');
  const dot        = document.getElementById('notifDot');
  const dropBadge  = document.getElementById('notifCountBadge');
  const secBadge   = document.getElementById('notifSectionBadge');
  const navBadge   = document.getElementById('sidebarNotifBadge');

  if (count > 0) {
    bell.classList.add('has-unread');
    if (dot)       { dot.style.display = 'block'; }
    if (dropBadge) { dropBadge.textContent = count > 99 ? '99+' : count; dropBadge.style.display = 'inline-flex'; }
    if (secBadge)  { secBadge.textContent  = count > 99 ? '99+' : count; secBadge.style.display  = 'inline-flex'; }
    if (navBadge)  { navBadge.textContent  = count > 99 ? '99+' : count; navBadge.style.display  = 'inline-flex'; }
  } else {
    bell.classList.remove('has-unread');
    if (dot)       dot.style.display       = 'none';
    if (dropBadge) dropBadge.style.display = 'none';
    if (secBadge)  secBadge.style.display  = 'none';
    if (navBadge)  navBadge.style.display  = 'none';
  }
}

/* ── Dropdown (bell) render ───────────────── */
function renderDropdownNotifs(items) {
  const list = document.getElementById('notifDropdownList');
  if (!list) return;

  if (!items.length) {
    list.innerHTML = `
      <div class="notif-empty">
        <div class="empty-icon">🔕</div>
        <p>No notifications yet</p>
      </div>`;
    return;
  }

  list.innerHTML = items.map(n => `
    <div class="notif-item ${n.is_read == 0 ? 'unread' : ''} ${n.type || 'info'}"
         onclick="markOneRead(${n.id})">
      <div class="notif-icon ${n.type || 'info'}">${notifIcon(n.type)}</div>
      <div class="notif-body">
        <p>${escHtml(n.message)}</p>
        <span class="notif-time">🕐 ${formatNotifTime(n.created_at)}</span>
      </div>
      <div class="notif-unread-dot"></div>
    </div>
  `).join('');
}

/* ── Full list (section) render ───────────── */
function renderFullNotifList(items, filter = 'all') {
  const list = document.getElementById('notifFullList');
  if (!list) return;

  let filtered = items;
  if (filter === 'reschedule') filtered = items.filter(n => n.type === 'reschedule');
  if (filter === 'unread')     filtered = items.filter(n => n.is_read == 0);

  if (!filtered.length) {
    list.innerHTML = `
      <div class="notif-full-empty">
        <div class="empty-icon">🔕</div>
        <p>No notifications</p>
        <small>${filter === 'all' ? "You'll see pickup alerts and reschedule notices here." : 'Nothing in this category.'}</small>
      </div>`;
    return;
  }

  list.innerHTML = filtered.map(n => `
    <div class="notif-full-item ${n.is_read == 0 ? 'unread' : ''}"
         onclick="markOneRead(${n.id})">
      <div class="notif-full-icon">${notifIcon(n.type)}</div>
      <div class="notif-full-body">
        <div class="notif-title">${notifTitle(n.type)}</div>
        <div class="notif-msg">${escHtml(n.message)}</div>
        <div class="notif-meta">
          <span class="notif-tag ${n.type || 'info'}">${notifLabel(n.type)}</span>
          <span class="notif-full-time">🕐 ${formatNotifTime(n.created_at)}</span>
        </div>
      </div>
    </div>
  `).join('');
}

/* ── Helpers ──────────────────────────────── */
function notifIcon(type) {
  const icons = { reschedule:'📅', alert:'🚛', info:'ℹ️', truck:'🚛' };
  return icons[type] || '🔔';
}
function notifTitle(type) {
  const titles = { reschedule:'Pickup Rescheduled', alert:'Truck Alert', info:'Notice', truck:'Truck On The Way' };
  return titles[type] || 'Notification';
}
function notifLabel(type) {
  const labels = { reschedule:'Reschedule', alert:'Alert', info:'Info', truck:'Truck' };
  return labels[type] || 'Notice';
}

function formatNotifTime(ts) {
  if (!ts) return '—';
  const d = new Date(ts);
  const now = new Date();
  const diff = Math.floor((now - d) / 1000);
  if (diff < 60)   return 'Just now';
  if (diff < 3600) return Math.floor(diff/60) + ' min ago';
  if (diff < 86400)return Math.floor(diff/3600) + ' hr ago';
  return d.toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric' });
}

/* ── Bell toggle ──────────────────────────── */
function toggleNotifDropdown() {
  notifDropdownOpen ? closeNotifDropdown() : openNotifDropdown();
}
function openNotifDropdown() {
  document.getElementById('notifDropdown').classList.add('open');
  notifDropdownOpen = true;
}
function closeNotifDropdown() {
  document.getElementById('notifDropdown').classList.remove('open');
  notifDropdownOpen = false;
}

/* ── Mark one read ────────────────────────── */
function markOneRead(id) {
  fetch('backend/mark_notification_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + id
  }).then(() => loadNotifications());
}

/* ── Mark all read ────────────────────────── */
function markAllRead() {
  fetch('backend/mark_notification_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'all=1'
  }).then(() => {
    loadNotifications();
    showToast('All notifications marked as read.', 'success');
  });
}

/* ── Filter tabs ──────────────────────────── */
function filterNotifs(type, btn) {
  document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  renderFullNotifList(allNotifications, type);
}

/* ══════════════════════════════════════════════
   LOAD MY REQUESTS
══════════════════════════════════════════════ */
function loadRequests() {
  const tbody      = document.getElementById('requestBody');
  const emptyState = document.getElementById('emptyState');
  if (!tbody) return;

  tbody.innerHTML = `<tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>`;
  if (emptyState) emptyState.style.display = 'none';

  fetch('backend/get_user_requests.php')
    .then(res => { if (!res.ok) throw new Error('Network'); return res.json(); })
    .then(data => {
      tbody.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
        return;
      }

      let truckAlerted = false;

      data.forEach((req, idx) => {
        const seqNo = idx + 1;

        let activeDateStr = null;
        if (req.unavailable == 1 && req.rescheduled_date) {
          activeDateStr = req.rescheduled_date;
        } else if (req.pickup_date) {
          activeDateStr = req.pickup_date;
        }

        const pickupDisplay = activeDateStr
          ? `<span class="pickup-date-main">${formatPickupDate(parseDateStr(activeDateStr))}</span>`
          : '—';

        const statusClass = (req.status || 'requested').toLowerCase().replace(/\s+/g, '-');
        const statusLabel = req.status
          ? req.status.charAt(0).toUpperCase() + req.status.slice(1)
          : '—';

        let availCell = '<span class="avail-na">—</span>';
        if (req.status === 'requested' || req.status === 'accepted' || req.status === 'rescheduled') {
          if (req.unavailable == 1) {
            const reschedStr = req.rescheduled_date
              ? formatPickupDate(parseDateStr(req.rescheduled_date)) : '—';
            availCell = `<span class="avail-rescheduled">🔄 Rescheduled<br><small>${reschedStr}</small></span>`;
          } else {
            availCell = `<button class="avail-btn" onclick="openAvailModal(${req.id},'${req.pickup_date || ''}')">📅 Unavailable?</button>`;
          }
        } else if (req.status === 'completed') {
          availCell = '<span class="avail-done">✅ Done</span>';
        }

        if (req.alert_sent == 1 && req.status !== 'completed' && !truckAlerted) {
          truckAlerted = true;
          setTimeout(() => showToast('🚛 Collection truck arrives in ~10 min! Have your waste ready.', 'warning', 7000), 600);
        }

        const reqTime = req.request_time
          ? new Date(req.request_time).toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
          : '—';

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><strong>${seqNo}</strong></td>
          <td>Ward ${req.ward_id}</td>
          <td>${req.waste_type || '—'}</td>
          <td class="time-cell">${reqTime}</td>
          <td>${pickupDisplay}</td>
          <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
          <td>${availCell}</td>`;
        tbody.appendChild(tr);
      });
    })
    .catch(() => {
      tbody.innerHTML = `<tr><td colspan="7" class="table-error">⚠️ Failed to load. <a href="#" onclick="loadRequests();return false;">Retry</a></td></tr>`;
      showToast('Failed to load requests', 'error');
    });
}

/* ══════════════════════════════════════════════
   AVAILABILITY MODAL
══════════════════════════════════════════════ */
function openAvailModal(id, currentDateStr) {
  pendingAvailId = id;
  const currentDate = currentDateStr ? parseDateStr(currentDateStr) : null;

  document.getElementById('modalCurrentDate').textContent =
    currentDate ? formatPickupDate(currentDate) : 'Not yet scheduled';
  document.getElementById('modalNewDate').textContent =
    'Next available batch (you will be notified once date is set)';
  document.getElementById('availModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeAvailModal(e) {
  if (e && e.target !== document.getElementById('availModal')) return;
  document.getElementById('availModal').style.display = 'none';
  document.body.style.overflow = '';
  pendingAvailId = null; pendingNewDate = null;
}

function confirmUnavailable() {
  if (!pendingAvailId) return;
  const btn = document.getElementById('confirmAvailBtn');
  btn.disabled = true; btn.textContent = 'Rescheduling…';

  // Only send id — backend finds the next batch automatically
  fetch('backend/mark_unavailable.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${encodeURIComponent(pendingAvailId)}`
  })
    .then(res => res.json())
    .then(resp => {
      document.getElementById('availModal').style.display = 'none';
      document.body.style.overflow = '';
      btn.disabled = false;
      btn.innerHTML = `<svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" fill="none" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Yes, Reschedule`;

      if (resp.success) {
        const msg = resp.new_date
          ? `✅ Rescheduled to ${formatPickupDate(parseDateStr(resp.new_date))}`
          : '✅ Moved to queue. You will be notified when a date is set.';
        showToast(msg, 'success', 5000);
      } else {
        showToast('⚠️ ' + (resp.message || 'Could not reschedule'), 'error');
      }
      pendingAvailId = null; pendingNewDate = null;
      loadRequests();
    })
    .catch(() => {
      btn.disabled = false;
      showToast('Failed to reschedule. Please try again.', 'error');
    });
}

/* ══════════════════════════════════════════════
   PICKUP FORM
══════════════════════════════════════════════ */
function initPickupForm() {
  setPickupDateHidden();
  const form = document.getElementById('pickupForm');
  if (!form) return;

  form.addEventListener('submit', e => {
    e.preventDefault();
    if (!form.querySelectorAll('input[name="waste[]"]:checked').length) {
      showToast('Please select at least one waste type.', 'warning');
      return;
    }
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');

    fetch('backend/request_pickup.php', { method: 'POST', body: new FormData(form) })
      .then(res => res.json())
      .then(() => {
        btn.classList.remove('loading');
        const banner   = document.getElementById('pickupSuccessBanner');
        const dateSpan = document.getElementById('psb-date');
        if (banner && dateSpan) {
          dateSpan.textContent = 'The collector will notify you once a pickup date is scheduled.';
          banner.style.display = 'flex';
          setTimeout(() => banner.scrollIntoView({ behavior:'smooth', block:'nearest' }), 50);
        }
        showToast('✅ Pickup request submitted!', 'success', 4000);
        form.reset();
      })
      .catch(() => {
        btn.classList.remove('loading');
        showToast('Failed to submit. Please try again.', 'error');
      });
  });
}

/* ══════════════════════════════════════════════
   GPS
══════════════════════════════════════════════ */
function getLocation() {
  const btn = document.querySelector('.location-btn');
  if (!navigator.geolocation) { showToast('Geolocation not supported.', 'error'); return; }
  if (btn) { btn.textContent = '⏳ Detecting…'; btn.disabled = true; }

  navigator.geolocation.getCurrentPosition(
    pos => {
      const lat = pos.coords.latitude.toFixed(6);
      const lng = pos.coords.longitude.toFixed(6);
      document.getElementById('latitude').value  = lat;
      document.getElementById('longitude').value = lng;
      const addr = document.getElementById('req-address');
      if (addr && !addr.value.trim()) addr.placeholder = `GPS: ${lat}, ${lng}`;
      showToast(`📍 Location captured (${lat}, ${lng})`, 'success');
      if (btn) { btn.innerHTML = '📍 Location Set ✓'; btn.disabled = false; }
    },
    err => {
      const msgs = ['','Location access denied.','Location unavailable.','Location timed out.'];
      showToast(msgs[err.code] || 'Location error.', 'error');
      if (btn) { btn.innerHTML = '📍 Use My GPS Location'; btn.disabled = false; }
    },
    { timeout:10000, maximumAge:60000, enableHighAccuracy:true }
  );
}

/* ══════════════════════════════════════════════
   PROFILE
══════════════════════════════════════════════ */
function toggleEditMode(editing) {
  document.getElementById('profileView').style.display = editing ? 'none'  : 'block';
  document.getElementById('profileEdit').style.display = editing ? 'block' : 'none';
}

function togglePassView(btn) {
  const inp  = btn.closest('.pw-wrap').querySelector('input');
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.querySelector('.eye-on').style.display  = show ? 'none'  : 'block';
  btn.querySelector('.eye-off').style.display = show ? 'block' : 'none';
}

function initProfileForm() {
  const form = document.getElementById('profileForm');
  if (!form) return;

  form.addEventListener('submit', e => {
    e.preventDefault();
    const btn = document.getElementById('saveProfileBtn');
    btn.classList.add('loading');

    fetch('backend/update_profile.php', { method:'POST', body: new FormData(form) })
      .then(res => res.json())
      .then(data => {
        btn.classList.remove('loading');
        if (data.success) {
          const name    = document.getElementById('edit-name').value.trim();
          const email   = document.getElementById('edit-email').value.trim();
          const phone   = document.getElementById('edit-phone').value.trim();
          const address = document.getElementById('edit-address').value.trim();
          document.getElementById('view-name').textContent    = name    || '—';
          document.getElementById('view-email').textContent   = email   || '—';
          document.getElementById('view-phone').textContent   = phone   || '—';
          document.getElementById('view-address').textContent = address || '—';
          const heading = document.getElementById('profile-heading-name');
          if (heading) heading.textContent = name || '—';
          toggleEditMode(false);
          showToast('✅ Profile updated successfully!', 'success');
        } else {
          showToast(data.message || 'Update failed. Try again.', 'error');
        }
      })
      .catch(() => {
        btn.classList.remove('loading');
        showToast('Network error. Please try again.', 'error');
      });
  });
}

/* ══════════════════════════════════════════════
   TABLE SEARCH
══════════════════════════════════════════════ */
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#requestBody tr:not(.skeleton-row)').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

/* ══════════════════════════════════════════════
   LOGOUT
══════════════════════════════════════════════ */
function logout() {
  if (!confirm('Are you sure you want to log out?')) return;
  localStorage.removeItem('loggedIn');
  sessionStorage.clear();
  window.location.href = 'login.html';
}

/* ══════════════════════════════════════════════
   UTILS
══════════════════════════════════════════════ */
function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}