'use strict';

/* ── STATE ─────────────────────────────────────────────────── */
let wasteChartInst     = null;
let wardChartInst      = null;
let wasteChartBigInst  = null;
let wardChartBigInst   = null;
let overdueFilterActive = false;
let debounceTimer       = null;
let adminNotifications  = [];

/* ── CHART DEFAULTS ────────────────────────────────────────── */
Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
Chart.defaults.color       = '#4b7a57';

const CHART_COLORS = ['#22c55e','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#0891b2','#f97316','#ec4899','#84cc16'];

/* ── INIT ──────────────────────────────────────────────────── */
window.onload = function(){
    loadStats();
    loadRequests();
    loadWasteChart();
    loadWardChart();
    loadWardSummary();
    loadAdminNotifications();
    setInterval(loadStats,            60000);
    setInterval(loadAdminNotifications, 30000);
};

/* ── SECTION SWITCH ────────────────────────────────────────── */
function switchSection(name, navEl){
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

    const sec = document.getElementById('section-' + name);
    if(sec) sec.classList.add('active');
    if(navEl) navEl.classList.add('active');

    const titles = {
        overview:'Overview', requests:'All Requests',
        analytics:'Analytics', wards:'Ward Summary', notifications:'Notifications'
    };
    document.getElementById('pageTitle').innerText = titles[name] || name;

    if(name === 'analytics'){
        setTimeout(()=>{ loadWasteChartBig(); loadWardChartBig(); }, 100);
    }
    if(name === 'notifications') loadAdminNotifications();
    if(name === 'requests')      loadRequests();
    if(name === 'wards')         loadWardSummary();

    closeSidebar();
    return false;
}

/* ── SIDEBAR ───────────────────────────────────────────────── */
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }
function closeSidebar()  { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('open'); }

/* ── TOAST ─────────────────────────────────────────────────── */
let toastTimer = null;
function showToast(msg, type='success', dur=3500){
    const t = document.getElementById('toast');
    if(!t) return;
    clearTimeout(toastTimer);
    t.textContent = msg;
    t.className = `toast ${type} show`;
    toastTimer = setTimeout(()=> t.classList.remove('show'), dur);
}

/* ── STATS ─────────────────────────────────────────────────── */
function loadStats(){
    fetch('backend/get_admin_stats.php')
        .then(r => r.json())
        .then(data => {
            animateNumber('totalRequests', data.total    || 0);
            animateNumber('completed',     data.completed || 0);
            animateNumber('pending',       data.pending   || 0);
            animateNumber('overdue',       data.overdue   || 0);
            animateNumber('collectors',    data.collectors|| 0);
            animateNumber('users',         data.users     || 0);

            // Show overdue banner
            const banner = document.getElementById('overdueBanner');
            const navBadge = document.getElementById('overdueNavBadge');
            if(data.overdue > 0){
                banner.style.display = 'flex';
                document.getElementById('overdueText').innerText =
                    data.overdue + ' request' + (data.overdue!==1?'s':'') +
                    ' have been pending for over 15 days across your wards.';
                navBadge.style.display = 'flex';
            } else {
                banner.style.display = 'none';
                navBadge.style.display = 'none';
            }
        });
}

function animateNumber(id, target){
    const el = document.getElementById(id);
    if(!el) return;
    const start = parseInt(el.innerText) || 0;
    const diff  = target - start;
    const steps = 20;
    let step = 0;
    const timer = setInterval(()=>{
        step++;
        el.innerText = Math.round(start + diff * (step/steps));
        if(step >= steps){ el.innerText = target; clearInterval(timer); }
    }, 20);
}

/* ── REQUESTS ──────────────────────────────────────────────── */
function loadRequests(){
    const search  = document.getElementById('searchInput')?.value  || '';
    const status  = document.getElementById('filterStatus')?.value || 'all';
    const ward    = document.getElementById('filterWard')?.value   || 'all';
    const overdue = overdueFilterActive ? '1' : '0';

    const url = `backend/get_all_requests.php?status=${status}&ward=${ward}&search=${encodeURIComponent(search)}&overdue=${overdue}&limit=200`;

    const tbody = document.getElementById('requestTable');
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><div class="skeleton" style="max-width:300px;margin:0 auto"></div></div></td></tr>`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if(!Array.isArray(data) || data.length === 0){
                tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📭</div><p>No requests found.</p><small>Try adjusting your filters.</small></div></td></tr>`;
                return;
            }

            tbody.innerHTML = data.map((req, i) => {
                const daysOld  = parseInt(req.days_old) || 0;
                const isOverdue = daysOld >= 15 && req.status !== 'completed';
                const daysClass = daysOld < 7 ? 'days-normal' : daysOld < 15 ? 'days-warning' : 'days-danger';
                const statusCls = `status-${(req.status||'requested').toLowerCase()}`;

                const overdueBtn = isOverdue
                    ? `<button class="btn-notify-overdue" onclick="openOverdueModal(${req.ward_id})">🚨 Alert Collector</button>`
                    : '—';

                return `<tr class="${isOverdue ? 'overdue-row' : ''}">
                  <td style="font-family:monospace;font-size:.8rem;color:var(--text-4)">${i+1}</td>
                  <td style="font-size:.82rem">${escHtml(req.user_name||'—')}<br><span style="color:var(--text-4);font-size:.72rem">${escHtml(req.user_phone||'')}</span></td>
                  <td style="max-width:180px;word-break:break-word;font-size:.83rem">${escHtml(req.address||'—')}</td>
                  <td><span style="font-family:monospace;font-size:.8rem">W${req.ward_id}</span></td>
                  <td><span style="background:var(--g100);color:var(--g800);font-size:.75rem;padding:3px 8px;border-radius:4px;font-weight:600">${escHtml(req.waste_type)}</span></td>
                  <td style="font-size:.78rem;color:var(--text-4);white-space:nowrap">${formatTime(req.request_time)}</td>
                  <td><span class="days-badge ${daysClass}">${daysOld}d</span></td>
                  <td><span class="status-badge ${statusCls}">${capitalise(req.status||'requested')}</span></td>
                  <td>${overdueBtn}</td>
                </tr>`;
            }).join('');
        })
        .catch(() => showToast('Failed to load requests','error'));
}

function debounceLoad(){
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadRequests, 350);
}

function filterByOverdue(){
    overdueFilterActive = !overdueFilterActive;
    const btn = document.getElementById('btnOverdue');
    if(btn) btn.classList.toggle('active', overdueFilterActive);
    loadRequests();
    switchSection('requests', document.querySelector('[data-section="requests"]'));
}

/* ── WASTE CHART (overview) ────────────────────────────────── */
function loadWasteChart(){
    fetch('backend/get_waste_stats.php')
        .then(r => r.json())
        .then(data => {
            if(wasteChartInst){ wasteChartInst.destroy(); }
            const ctx = document.getElementById('wasteChart');
            if(!ctx) return;
            wasteChartInst = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.waste_type),
                    datasets: [{ data: data.map(d => d.count), backgroundColor: CHART_COLORS, borderWidth: 2, borderColor: '#fff' }]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 14, font: { size: 12, weight: '600' } } }
                    }
                }
            });
        });
}

/* ── WARD CHART (overview) ─────────────────────────────────── */
function loadWardChart(){
    fetch('backend/get_ward_stats.php')
        .then(r => r.json())
        .then(data => {
            if(wardChartInst){ wardChartInst.destroy(); }
            const ctx = document.getElementById('wardChart');
            if(!ctx) return;
            wardChartInst = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => 'W' + d.ward_id),
                    datasets: [{
                        label: 'Requests', data: data.map(d => d.count),
                        backgroundColor: 'rgba(34,197,94,.7)', borderColor: '#16a34a',
                        borderWidth: 2, borderRadius: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });
        });
}

/* ── BIG CHARTS (analytics) ────────────────────────────────── */
function loadWasteChartBig(){
    fetch('backend/get_waste_stats.php')
        .then(r => r.json())
        .then(data => {
            if(wasteChartBigInst){ wasteChartBigInst.destroy(); }
            const ctx = document.getElementById('wasteChartBig');
            if(!ctx) return;
            wasteChartBigInst = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.map(d => d.waste_type),
                    datasets: [{ data: data.map(d => d.count), backgroundColor: CHART_COLORS, borderWidth: 3, borderColor: '#fff' }]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'right', labels: { padding: 16, font: { size: 13, weight: '600' } } }
                    }
                }
            });
        });
}

function loadWardChartBig(){
    fetch('backend/get_ward_stats.php')
        .then(r => r.json())
        .then(data => {
            if(wardChartBigInst){ wardChartBigInst.destroy(); }
            const ctx = document.getElementById('wardChartBig');
            if(!ctx) return;
            wardChartBigInst = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => 'Ward ' + d.ward_id),
                    datasets: [{
                        label: 'Total Requests', data: data.map(d => d.count),
                        backgroundColor: CHART_COLORS,
                        borderWidth: 0, borderRadius: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.06)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
}

/* ── WARD SUMMARY ──────────────────────────────────────────── */
function loadWardSummary(){
    fetch('backend/get_ward_summary.php')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('wardStatsTable');
            if(!Array.isArray(data)||data.length===0){
                tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">📭</div><p>No data yet.</p></div></td></tr>`;
                return;
            }

            tbody.innerHTML = data.map(row => {
                const pct       = row.total > 0 ? Math.round((row.completed/row.total)*100) : 0;
                const hasOverdue = parseInt(row.overdue) > 0;

                return `<tr class="${hasOverdue ? 'overdue-row' : ''}">
                  <td><strong>Ward ${row.ward_id}</strong></td>
                  <td><strong>${row.total}</strong></td>
                  <td><span style="color:var(--g700);font-weight:700">${row.completed}</span></td>
                  <td><span style="color:#92400e;font-weight:600">${row.pending}</span></td>
                  <td>
                    ${hasOverdue
                        ? `<span class="days-badge days-danger">${row.overdue} overdue</span>`
                        : '<span class="days-badge days-normal">None</span>'}
                  </td>
                  <td>
                    ${hasOverdue
                        ? `<button class="btn-notify-overdue" onclick="openOverdueModal(${row.ward_id})">🚨 Alert Collector</button>`
                        : '<span style="color:var(--text-4);font-size:.8rem">—</span>'}
                  </td>
                </tr>`;
            }).join('');
        });
}

/* ── OVERDUE MODAL ─────────────────────────────────────────── */
function openOverdueModal(ward){
    document.getElementById('overdueModalWard').value = ward;
    document.getElementById('overdueModalText').innerText =
        `Send an urgent alert to the collector assigned to Ward ${ward} to complete overdue pickups immediately.`;
    document.getElementById('overdueModal').classList.add('open');
}

function closeOverdueModal(){
    document.getElementById('overdueModal').classList.remove('open');
}

document.getElementById('overdueModal').addEventListener('click', function(e){
    if(e.target === this) closeOverdueModal();
});

function sendOverdueAlert(){
    const ward = document.getElementById('overdueModalWard').value;
    const btn  = document.querySelector('.modal-confirm');
    btn.disabled = true; btn.innerText = 'Sending…';

    fetch('backend/notify_overdue_collector.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'ward='+ward
    })
    .then(r=>r.json())
    .then(resp=>{
        btn.disabled = false; btn.innerText = '📤 Send Alert to Collector';
        closeOverdueModal();
        if(resp.success){
            showToast(`✅ Alert sent to ${resp.collector_name} for Ward ${ward}!`, 'success', 5000);
            loadStats();
            loadWardSummary();
            loadAdminNotifications();
        } else {
            showToast('⚠️ ' + (resp.message||'Could not send alert'), 'warning');
        }
    })
    .catch(()=>{
        btn.disabled = false; btn.innerText = '📤 Send Alert to Collector';
        showToast('Failed to send alert','error');
        closeOverdueModal();
    });
}

/* ── ADMIN NOTIFICATIONS ───────────────────────────────────── */
function loadAdminNotifications(){
    fetch('backend/get_admin_notifications.php')
        .then(r=>r.json())
        .then(data=>{
            if(!Array.isArray(data)) return;
            adminNotifications = data;

            const unread = data.filter(n=>n.is_read==0).length;
            const bell   = document.getElementById('notifBell');
            const dot    = document.getElementById('notifDot');
            if(unread>0){ bell.classList.add('has-unread'); dot.style.display='block'; }
            else         { bell.classList.remove('has-unread'); dot.style.display='none'; }

            const container = document.getElementById('adminNotifList');
            if(!container) return;

            if(!data.length){
                container.innerHTML = `<div class="empty-state" style="padding:40px"><div class="empty-icon">🔕</div><p>No notifications yet.</p></div>`;
                return;
            }

            container.innerHTML = data.map(n => `
                <div class="notif-item-admin ${n.is_read==0?'unread':''}" onclick="markAdminNotifRead(${n.id})">
                  <div class="notif-admin-icon">${n.type==='cannot_collect'?'⚠️':'🚨'}</div>
                  <div class="notif-admin-body">
                    <div class="notif-msg">${escHtml(n.message)}</div>
                    <div class="notif-meta">
                      <span class="notif-type-tag notif-type-${n.type||'info'}">${(n.type||'notice').replace('_',' ')}</span>
                      <span>Ward ${n.ward_id||'—'}</span>
                      <span>🕐 ${formatTimeAgo(n.created_at)}</span>
                    </div>
                  </div>
                </div>`).join('');
        });
}

function markAdminNotifRead(id){
    fetch('backend/mark_admin_notif_read.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+id
    }).then(()=>loadAdminNotifications());
}

function markAllAdminNotifRead(){
    fetch('backend/mark_admin_notif_read.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'all=1'
    }).then(()=>{ loadAdminNotifications(); showToast('All notifications marked as read.','success'); });
}

/* ── LOGOUT ────────────────────────────────────────────────── */
function logout(){
    window.location.href = 'backend/logout_admin.php';
}

/* ── HELPERS ───────────────────────────────────────────────── */
function escHtml(str){
    if(!str) return '—';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function capitalise(str){ return str.charAt(0).toUpperCase()+str.slice(1); }
function formatTime(ts){
    if(!ts) return '—';
    return new Date(ts).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
}
function formatTimeAgo(ts){
    if(!ts) return '—';
    const d=new Date(ts),now=new Date(),diff=Math.floor((now-d)/1000);
    if(diff<60)    return 'Just now';
    if(diff<3600)  return Math.floor(diff/60)+' min ago';
    if(diff<86400) return Math.floor(diff/3600)+' hr ago';
    return d.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});
}