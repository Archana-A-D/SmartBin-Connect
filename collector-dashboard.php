<?php
session_start();

if(!isset($_SESSION['collector_id'])){
    header("Location: collector-login.html");
    exit();
}

$collectorEmail = $_SESSION['collector_email'] ?? 'Collector';
$collectorWard  = $_SESSION['collector_ward']  ?? '—';
$collectorName  = $_SESSION['collector_name']  ?? 'Collector';
$initials       = strtoupper(substr($collectorName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Collector Dashboard | SmartBin Connect</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css"/>
<link rel="stylesheet" href="collector-dashboard.css">
</head>
<body>

<script>
  localStorage.setItem("collectorLoggedIn", "true");
  localStorage.setItem("collectorWard",  <?= json_encode((string)$collectorWard) ?>);
  localStorage.setItem("collectorId",    <?= json_encode((string)$collectorEmail) ?>);
  localStorage.setItem("collectorName",  <?= json_encode($collectorName) ?>);
</script>

<header class="header">
  <div class="header-brand">
    <div class="logo-icon">♻️</div>
    <h2>SmartBin Connect</h2>
  </div>
  <div class="header-right">
    <span class="ward-badge" id="wardLabel">Ward <?= htmlspecialchars($collectorWard) ?></span>
    <div class="collector-info">
      <div class="collector-avatar"><?= htmlspecialchars($initials) ?></div>
      <span><?= htmlspecialchars($collectorName) ?></span>
    </div>
    <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifDropdown()">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <span class="notif-dot" id="notifDot"></span>
    </button>
    <button class="btn-logout" onclick="logout()">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
      </svg>
      Logout
    </button>
  </div>
</header>

<!-- NOTIFICATION DROPDOWN -->
<div class="notif-dropdown" id="notifDropdown">
  <div class="notif-dropdown-header">
    <h4>🔔 Notifications <span class="notif-count-badge" id="notifCountBadge" style="display:none">0</span></h4>
    <button class="notif-mark-all" onclick="markAllCollectorNotif()">Mark all read</button>
  </div>
  <div class="notif-list" id="notifDropdownList">
    <div class="notif-empty"><div class="empty-icon">🔕</div><p>No notifications</p></div>
  </div>
</div>

<div class="page-body">

  <!-- STAT CARDS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon green">📋</div>
      <div class="stat-body"><p>Total Requests</p><h3 id="stat-total">—</h3></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon amber">🗂</div>
      <div class="stat-body"><p>In Queue</p><h3 id="stat-queued">—</h3></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue">📦</div>
      <div class="stat-body"><p>Active Batch</p><h3 id="stat-batch">—</h3></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green">✅</div>
      <div class="stat-body"><p>Completed</p><h3 id="stat-completed">—</h3></div>
    </div>
  </div>

  <!-- QUEUE ALERT BANNER -->
  <div class="queue-alert-banner" id="queueAlertBanner" style="display:none">
    <div class="qab-icon">🔔</div>
    <div class="qab-body">
      <strong>Queue Alert!</strong>
      <span id="queueAlertText">15+ requests waiting. Create a new batch to assign them.</span>
    </div>
    <button class="qab-btn" onclick="createBatch()">📦 Create Batch Now</button>
  </div>

  <!-- MAIN CARD WITH TABS -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <span class="icon">📦</span>
        <div><h3>Pickup Management</h3><p>Queue · Batches · History</p></div>
      </div>
      <div class="tab-group">
        <button class="tab-btn active" onclick="switchTab('queue',this)">
          🗂 Queue <span class="tab-badge" id="badge-queue"></span>
        </button>
        <button class="tab-btn" onclick="switchTab('batches',this)">📦 Batches</button>
        <button class="tab-btn" onclick="switchTab('history',this)">🕘 History</button>
      </div>
      <button class="btn-notify" onclick="openCannotCollectModal()">⚠️ Cannot Collect Today</button>
      <button class="btn-accept" onclick="refreshAll()">🔄 Refresh</button>
    </div>

    <!-- QUEUE TAB -->
    <div class="tab-content active" id="tabContent-queue">
      <div class="queue-toolbar">
        <div class="queue-info" id="queueInfo">Loading queue…</div>
        <button class="btn-create-batch" onclick="createBatch()" id="btnCreateBatch">
          📦 Create Batch (next 15)
        </button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Address</th><th>Ward</th><th>Waste Type</th><th>Requested At</th><th>Status</th></tr>
          </thead>
          <tbody id="queueTable">
            <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- BATCHES TAB -->
    <div class="tab-content" id="tabContent-batches">
      <div id="batchesList">
        <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading batches…</p></div>
      </div>
    </div>

    <!-- HISTORY TAB -->
    <div class="tab-content" id="tabContent-history">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Address</th><th>Ward</th><th>Waste Type</th><th>Batch</th><th>Pickup Date</th><th>Status</th></tr>
          </thead>
          <tbody id="historyTable">
            <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">⏳</div><p>Loading…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ACTIVE BATCH DETAIL -->
  <div class="card" id="activeBatchCard" style="display:none">
    <div class="card-header">
      <div class="card-title">
        <span class="icon">🚛</span>
        <div>
          <h3 id="activeBatchTitle">Batch Requests</h3>
          <p id="activeBatchSub">Accept and complete pickups</p>
        </div>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <button class="btn-complete" onclick="openBatchDateModal()" id="btnSetDate">📅 Set Pickup Date</button>
        <button class="btn-complete" onclick="completeBatch()"       id="btnCompleteBatch"
                style="background:linear-gradient(135deg,#059669,#047857)">✅ Complete Batch</button>
        <button class="btn-accept"   onclick="closeBatchDetail()">✕ Close</button>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Address</th><th>Ward</th><th>Waste Type</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody id="batchRequestTable">
          <tr><td colspan="6"><div class="empty-state"><p>Select a batch to view requests.</p></div></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ROUTE SECTION -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <span class="icon">🗺️</span>
        <div><h3>Optimized Pickup Route</h3><p>Nearest-neighbour algorithm · Turn-by-turn navigation</p></div>
      </div>
    </div>
    <div class="route-grid">
      <div class="route-sidebar">
        <button class="btn-generate" onclick="generateRoute()" id="btnGenerate">📍 Generate Best Route</button>
        <button class="btn-start-nav" onclick="startNavigation()" id="btnStartNav">▶ Start Navigation</button>
        <button class="btn-stop-nav"  onclick="stopNavigation()"  id="btnStopNav">⏹ Stop Navigation</button>
        <div class="route-info-box">
          <span class="label">Total Distance</span>
          <span class="value" id="routeDistance">—</span>
        </div>
        <div class="route-info-box">
          <span class="label">Current Stop</span>
          <span class="value" id="currentStopInfo" style="font-size:.95rem">—</span>
        </div>
        <ul class="route-stop-list" id="routeList">
          <li style="color:var(--text-muted);font-size:.85rem;padding:10px 0">No route generated yet.</li>
        </ul>
      </div>
      <div class="map-wrapper">
        <div id="map"></div>
        <div id="nav-overlay">
          <div class="nav-arrow" id="navArrow">➡️</div>
          <div class="nav-instruction">
            <div class="direction" id="navDirection">Head north</div>
            <div class="street"    id="navStreet">towards destination</div>
          </div>
          <div class="nav-progress">
            <span class="dist" id="navDist">—</span>
            <span class="eta"  id="navEta">ETA —</span>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /page-body -->

<!-- CANNOT COLLECT MODAL -->
<div class="modal-overlay" id="cannotCollectModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon">!</div>
      <div><h3>Cannot Collect Today</h3><p>Notify users and admin with the selected reschedule date</p></div>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Reason</label>
        <select id="cannotReason">
          <option value="vehicle_breakdown">Vehicle Breakdown</option>
          <option value="staff_shortage">Staff Shortage</option>
          <option value="road_condition">Poor Road Condition</option>
          <option value="emergency">Emergency</option>
          <option value="weather">Adverse Weather</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="form-group">
        <label>Reschedule Date</label>
        <input type="date" id="cannotRescheduleDate" class="date-input">
      </div>
      <div class="form-group">
        <label>Additional Notes (optional)</label>
        <textarea id="cannotNote" rows="3" placeholder="Extra details for admin..."></textarea>
      </div>
      <div id="cannotCollectPreview" style="background:var(--green-50);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:.82rem;color:var(--green-800)">
        Pickup date will be shared with affected users and admin.
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel-modal" onclick="closeCannotCollectModal()">Cancel</button>
      <button class="btn-send-notify"  onclick="sendCannotCollectNotification()">Send Notification</button>
    </div>
  </div>
</div>

<!-- SET BATCH DATE MODAL -->
<div class="modal-overlay" id="batchDateModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon">📅</div>
      <div>
        <h3>Set Pickup Date</h3>
        <p id="batchDateModalSub">All users in this batch will be notified</p>
      </div>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Pickup Date</label>
        <input type="date" id="batchDateModalInput" class="date-input" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:var(--font);font-size:.9rem">
      </div>
      <div style="background:var(--green-50);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:.82rem;color:var(--green-800);margin-top:10px">
        📣 All users in this batch will be <strong>notified immediately</strong> with the pickup date.
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel-modal" onclick="closeBatchDateModal()">Cancel</button>
      <button class="btn-send-notify"  onclick="confirmSetBatchDate()">📅 Confirm & Notify Users</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script src="collector-dashboard.js"></script>
</body>
</html>
