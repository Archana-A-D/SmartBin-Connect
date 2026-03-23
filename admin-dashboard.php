<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: admin-login.php");
    exit();
}
require_once 'backend/db.php';

// Fetch admin name
$adminName = 'Admin';
try {
    $stmt = $pdo->prepare("SELECT username FROM admins WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row) $adminName = htmlspecialchars($row['username']);
} catch(Exception $e){}

$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | SmartBin Connect</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>

<!-- ░░ SIDEBAR ░░ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">♻</div>
    <span class="brand-name">SmartBin Connect</span>
  </div>

  <nav class="sidebar-nav">
    <a href="#" class="nav-item active" data-section="overview" onclick="switchSection('overview',this)">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      <span>Overview</span>
    </a>
    <a href="#" class="nav-item" data-section="requests" onclick="switchSection('requests',this)">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <span>Requests</span>
      <span class="nav-badge" id="overdueNavBadge" style="display:none">!</span>
    </a>
    <a href="#" class="nav-item" data-section="analytics" onclick="switchSection('analytics',this)">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      <span>Analytics</span>
    </a>
    <a href="#" class="nav-item" data-section="wards" onclick="switchSection('wards',this)">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <span>Ward Summary</span>
    </a>
    <a href="#" class="nav-item" data-section="notifications" onclick="switchSection('notifications',this)">
      <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      <span>Notifications</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-meta">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-info">
        <strong><?= $adminName ?></strong>
        <small>Administrator</small>
      </div>
    </div>
    <button class="logout-btn" onclick="logout()">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </button>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ░░ MAIN ░░ -->
<div class="main-wrap">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-btn" onclick="toggleSidebar()">
        <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topbar-title">
        <span id="pageTitle">Overview</span>
        <span class="topbar-sub">SmartWaste Admin Panel</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="status-pill"><span class="pulse-dot"></span><span>Live</span></div>
      <button class="notif-bell" id="notifBell" onclick="switchSection('notifications', document.querySelector('[data-section=notifications]'))">
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span class="notif-dot" id="notifDot" style="display:none"></span>
      </button>
      <div class="topbar-avatar"><?= $initials ?></div>
    </div>
  </header>

  <main class="content">

    <!-- TOAST -->
    <div id="toast" class="toast" role="alert"></div>

    <!-- ══ SECTION: OVERVIEW ══ -->
    <section class="section active" id="section-overview">
      <div class="section-header">
        <h2>Dashboard Overview</h2>
        <p>Real-time snapshot of SmartWaste operations</p>
      </div>

      <!-- OVERDUE ALERT BANNER -->
      <div class="overdue-banner" id="overdueBanner" style="display:none">
        <div class="ob-icon">⚠️</div>
        <div class="ob-body">
          <strong>Overdue Requests Alert!</strong>
          <span id="overdueText">Some requests have been pending for over 15 days.</span>
        </div>
        <button class="ob-btn" onclick="switchSection('requests', document.querySelector('[data-section=requests]'));filterByOverdue()">View Overdue</button>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-grid">
        <div class="stat-card stat-blue">
          <div class="stat-icon">📋</div>
          <div class="stat-body">
            <p>Total Requests</p>
            <h3 id="totalRequests">—</h3>
          </div>
          <div class="stat-trend" id="trendTotal"></div>
        </div>
        <div class="stat-card stat-green">
          <div class="stat-icon">✅</div>
          <div class="stat-body">
            <p>Completed</p>
            <h3 id="completed">—</h3>
          </div>
        </div>
        <div class="stat-card stat-amber">
          <div class="stat-icon">⏳</div>
          <div class="stat-body">
            <p>Pending</p>
            <h3 id="pending">—</h3>
          </div>
        </div>
        <div class="stat-card stat-red">
          <div class="stat-icon">🚨</div>
          <div class="stat-body">
            <p>Overdue (15d+)</p>
            <h3 id="overdue">—</h3>
          </div>
        </div>
        <div class="stat-card stat-purple">
          <div class="stat-icon">🚛</div>
          <div class="stat-body">
            <p>Collectors</p>
            <h3 id="collectors">—</h3>
          </div>
        </div>
        <div class="stat-card stat-teal">
          <div class="stat-icon">👤</div>
          <div class="stat-body">
            <p>Registered Users</p>
            <h3 id="users">—</h3>
          </div>
        </div>
      </div>

      <!-- QUICK CHARTS ROW -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-header">
            <h3>Waste Type Distribution</h3>
            <p>Breakdown by category</p>
          </div>
          <div class="chart-body">
            <canvas id="wasteChart"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <h3>Requests by Ward</h3>
            <p>Volume per ward</p>
          </div>
          <div class="chart-body">
            <canvas id="wardChart"></canvas>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ SECTION: REQUESTS ══ -->
    <section class="section" id="section-requests">
      <div class="section-header">
        <h2>All Pickup Requests</h2>
        <p>View, filter and manage all ward requests</p>
      </div>

      <div class="table-card">
        <div class="table-toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="searchInput" placeholder="Search address or waste type…" oninput="debounceLoad()">
          </div>
          <select id="filterStatus" onchange="loadRequests()" class="filter-select">
            <option value="all">All Status</option>
            <option value="requested">Requested</option>
            <option value="accepted">Accepted</option>
            <option value="completed">Completed</option>
            <option value="rescheduled">Rescheduled</option>
          </select>
          <select id="filterWard" onchange="loadRequests()" class="filter-select">
            <option value="all">All Wards</option>
            <?php for($i=1;$i<=10;$i++) echo "<option value='$i'>Ward $i</option>"; ?>
          </select>
          <button class="btn-overdue" id="btnOverdue" onclick="filterByOverdue()">🚨 Show Overdue</button>
          <button class="btn-refresh" onclick="loadRequests()">🔄</button>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th><th>User</th><th>Address</th><th>Ward</th>
                <th>Waste Type</th><th>Requested At</th><th>Days Old</th>
                <th>Status</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="requestTable">
              <tr><td colspan="9"><div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══ SECTION: ANALYTICS ══ -->
    <section class="section" id="section-analytics">
      <div class="section-header">
        <h2>Waste Analytics</h2>
        <p>In-depth charts and trends</p>
      </div>
      <div class="charts-row charts-row-tall">
        <div class="chart-card">
          <div class="chart-header">
            <h3>Waste Type Distribution</h3>
          </div>
          <div class="chart-body">
            <canvas id="wasteChartBig"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <h3>Requests by Ward</h3>
          </div>
          <div class="chart-body">
            <canvas id="wardChartBig"></canvas>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ SECTION: WARD SUMMARY ══ -->
    <section class="section" id="section-wards">
      <div class="section-header">
        <h2>Ward Summary</h2>
        <p>Performance and overdue alerts per ward</p>
      </div>
      <div class="table-card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Ward</th><th>Total</th><th>Completed</th><th>Pending</th><th>Overdue (15d+)</th><th>Action</th></tr>
            </thead>
            <tbody id="wardStatsTable">
              <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══ SECTION: NOTIFICATIONS ══ -->
    <section class="section" id="section-notifications">
      <div class="section-header">
        <h2>Admin Notifications</h2>
        <p>Cannot-collect alerts and system messages</p>
      </div>
      <div class="table-card">
        <div class="table-toolbar">
          <button class="btn-refresh" onclick="loadAdminNotifications()">🔄 Refresh</button>
          <button class="btn-overdue" onclick="markAllAdminNotifRead()" style="background:var(--g100);color:var(--g800);border-color:var(--g200)">✓ Mark all read</button>
        </div>
        <div id="adminNotifList">
          <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div>
        </div>
      </div>
    </section>

  </main>
</div>

<!-- OVERDUE NOTIFY MODAL -->
<div class="modal-backdrop" id="overdueModal">
  <div class="modal">
    <div class="modal-header">
      <h3>⚠️ Notify Collector — Overdue Requests</h3>
      <button class="modal-close" onclick="closeOverdueModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p id="overdueModalText">Send an urgent alert to the collector for this ward.</p>
      <input type="hidden" id="overdueModalWard">
      <div class="modal-info">
        📣 The collector will receive an <strong>urgent notification</strong> to complete these pickups immediately.
      </div>
    </div>
    <div class="modal-footer">
      <button class="modal-cancel" onclick="closeOverdueModal()">Cancel</button>
      <button class="modal-confirm" onclick="sendOverdueAlert()">📤 Send Alert to Collector</button>
    </div>
  </div>
</div>

<script src="admin-dashboard.js"></script>
</body>
</html>