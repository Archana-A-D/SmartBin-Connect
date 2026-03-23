<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

require_once 'backend/db.php';

$user_name    = 'User';
$user_email   = '';
$user_phone   = '';
$user_ward    = '';
$user_address = '';

try {
    $stmt = $pdo->prepare("SELECT name, email, phone, ward, address FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $user_name    = htmlspecialchars($row['name']    ?? 'User');
        $user_email   = htmlspecialchars($row['email']   ?? '');
        $user_phone   = htmlspecialchars($row['phone']   ?? '');
        $user_ward    = htmlspecialchars($row['ward']    ?? '');
        $user_address = htmlspecialchars($row['address'] ?? '');
        $_SESSION['user_name']  = $row['name']  ?? 'User';
        $_SESSION['user_email'] = $row['email'] ?? '';
        $_SESSION['user_phone'] = $row['phone'] ?? '';
        $_SESSION['user_ward']  = $row['ward']  ?? '';
    }
} catch (Exception $e) {
    $user_name  = htmlspecialchars($_SESSION['user_name']  ?? 'User');
    $user_email = htmlspecialchars($_SESSION['user_email'] ?? '');
    $user_phone = htmlspecialchars($_SESSION['user_phone'] ?? '');
    $user_ward  = htmlspecialchars($_SESSION['user_ward']  ?? '');
}

$ward_display  = $user_ward ? 'Ward ' . $user_ward : '—';
$avatar_letter = strtoupper(substr($user_name, 0, 1)) ?: 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | SmartWaste</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="user-dashboard.css">
</head>
<body>

  <!-- ░░ SIDEBAR ░░ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">♻</div>
      <span class="brand-name">SmartBin Connect</span>
    </div>

    <nav class="sidebar-nav">
      <a href="#" class="nav-item active" data-section="request">
        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
        <span>New Request</span>
      </a>
      <a href="#" class="nav-item" data-section="history">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>My Requests</span>
      </a>
      <!-- NOTIFICATIONS nav item -->
      <a href="#" class="nav-item" data-section="notifications">
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span>Notifications</span>
        <span class="nav-notif-badge" id="sidebarNotifBadge" style="display:none">0</span>
      </a>
      <a href="#" class="nav-item" data-section="profile">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>My Profile</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-meta" onclick="switchSection('profile')" title="View profile">
        <div class="user-avatar"><?= $avatar_letter ?></div>
        <div class="user-info">
          <strong><?= $user_name ?></strong>
          <small><?= $user_email ?: $ward_display ?></small>
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

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-btn" onclick="toggleSidebar()">
          <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="topbar-title">
          <span id="pageTitle">New Request</span>
          <span class="topbar-sub">SmartWaste User Portal</span>
        </div>
      </div>
      <div class="topbar-right">
        <div class="status-pill">
          <span class="pulse-dot"></span>
          <span>Online</span>
        </div>

        <!-- NOTIFICATION BELL -->
        <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifDropdown()" title="Notifications">
          <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-dot" id="notifDot"></span>
        </button>

        <div class="topbar-avatar" onclick="switchSection('profile')" title="My Profile">
          <?= $avatar_letter ?>
        </div>
      </div>
    </header>

    <!-- NOTIFICATION DROPDOWN (topbar bell) -->
    <div class="notif-dropdown" id="notifDropdown">
      <div class="notif-dropdown-header">
        <h4>
          🔔 Notifications
          <span class="notif-count-badge" id="notifCountBadge" style="display:none">0</span>
        </h4>
        <button class="notif-mark-all" onclick="markAllRead()">Mark all read</button>
      </div>
      <div class="notif-list" id="notifDropdownList">
        <div class="notif-empty">
          <div class="empty-icon">🔕</div>
          <p>No notifications yet</p>
        </div>
      </div>
      <div class="notif-dropdown-footer">
        <button class="notif-see-all" onclick="switchSection('notifications');closeNotifDropdown()">
          View all notifications →
        </button>
      </div>
    </div>

    <main class="content">

      <!-- Toast -->
      <div id="toast" class="toast" role="alert" aria-live="polite"></div>

      <!-- ══════════════════════════════════
           SECTION: New Request
      ══════════════════════════════════ -->
      <section class="section active" id="section-request">
        <div class="section-header">
          <h2>Request Waste Pickup</h2>
          <p>Fill in the details below to schedule your on-demand collection.</p>
        </div>

        <div class="form-card">
          <form id="pickupForm" novalidate>
            <div class="form-grid">

              <div class="field">
                <label for="phone">
                  <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.61 4.4 2 2 0 0 1 3.6 2.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z"/></svg>
                  Phone Number
                </label>
                <div class="inp-wrap">
                  <input type="tel" id="phone" name="phone" placeholder="+91 98765 43210"
                         value="<?= $user_phone ?>" inputmode="tel" required>
                </div>
              </div>

              <div class="field">
                <label for="ward">
                  <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                  Select Ward
                </label>
                <div class="inp-wrap select-wrap">
                  <select name="ward" id="ward" required>
                    <option value="">Choose your ward…</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= ($user_ward == $i ? 'selected' : '') ?>>Ward <?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                  <svg class="select-arrow" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <div class="field full-width">
                <label for="req-address">
                  <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  Address
                </label>
                <div class="inp-wrap">
                  <textarea id="req-address" name="address" placeholder="Enter your full address…" rows="3" required></textarea>
                </div>
                <button type="button" class="location-btn" onclick="getLocation()">
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
                  Use My GPS Location
                </button>
                <input type="hidden" name="latitude"    id="latitude">
                <input type="hidden" name="longitude"   id="longitude">
                <input type="hidden" name="pickup_date" id="pickupDateInput">
              </div>

              <div class="field full-width">
                <label>
                  <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  Waste Types <span class="req-star">*</span>
                </label>
                <div class="waste-grid">
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Plastic">
                    <span class="waste-inner"><span class="waste-emoji">🧴</span><span class="waste-name">Plastic</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Paper &amp; Cardboard">
                    <span class="waste-inner"><span class="waste-emoji">📄</span><span class="waste-name">Paper &amp; Cardboard</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Glass">
                    <span class="waste-inner"><span class="waste-emoji">🫙</span><span class="waste-name">Glass</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="E-Waste">
                    <span class="waste-inner"><span class="waste-emoji">💻</span><span class="waste-name">E-Waste</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Clothing &amp; Textiles">
                    <span class="waste-inner"><span class="waste-emoji">👕</span><span class="waste-name">Clothing &amp; Textiles</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Footwear">
                    <span class="waste-inner"><span class="waste-emoji">👟</span><span class="waste-name">Footwear</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Bags &amp; Sacks">
                    <span class="waste-inner"><span class="waste-emoji">🛍️</span><span class="waste-name">Bags &amp; Sacks</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Medical Waste">
                    <span class="waste-inner"><span class="waste-emoji">💊</span><span class="waste-name">Medical Waste</span></span>
                  </label>
                  <label class="waste-card">
                    <input type="checkbox" name="waste[]" value="Mixed Waste">
                    <span class="waste-inner"><span class="waste-emoji">🗑️</span><span class="waste-name">Mixed Waste</span></span>
                  </label>
                </div>
              </div>

            </div>

            <div class="form-footer">
              <button type="submit" class="submit-btn" id="submitBtn">
                <svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
                <span class="btn-txt">Submit Pickup Request</span>
                <div class="btn-spin"></div>
              </button>
            </div>
          </form>

          <div class="pickup-success-banner" id="pickupSuccessBanner">
            <div class="psb-icon">✅</div>
            <div class="psb-body">
              <strong>Request submitted successfully!</strong>
              <span id="psb-date">—</span>
            </div>
            <button class="psb-close" onclick="document.getElementById('pickupSuccessBanner').style.display='none'">&times;</button>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════
           SECTION: My Requests
      ══════════════════════════════════ -->
      <section class="section" id="section-history">
        <div class="section-header">
          <h2>My Pickup Requests</h2>
          <p>Track status, scheduled pickup dates, and manage your availability.</p>
        </div>

        <div class="table-card">
          <div class="table-toolbar">
            <div class="search-wrap">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="searchInput" placeholder="Search requests…" oninput="filterTable()">
            </div>
            <button class="refresh-btn" onclick="loadRequests()">
              <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
              Refresh
            </button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th><th>Ward</th><th>Waste Type</th>
                  <th>Requested At</th><th>Pickup Date</th>
                  <th>Status</th><th>Availability</th>
                </tr>
              </thead>
              <tbody id="requestBody">
                <tr class="skeleton-row"><td colspan="7"><div class="skeleton"></div></td></tr>
              </tbody>
            </table>
          </div>
          <div class="table-empty" id="emptyState" style="display:none">
            <div class="empty-icon">📭</div>
            <p>No pickup requests yet.</p>
            <small>Submit your first request using the form.</small>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════
           SECTION: Notifications
      ══════════════════════════════════ -->
      <section class="section" id="section-notifications">
        <div class="section-header">
          <h2>Notifications</h2>
          <p>Alerts about your pickups, reschedules, and collector updates.</p>
        </div>

        <div class="notif-section-card">
          <div class="notif-section-header">
            <h3>
              🔔 All Notifications
              <span class="notif-count-badge" id="notifSectionBadge" style="display:none">0</span>
            </h3>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <div class="notif-filter-tabs">
                <button class="notif-tab active" onclick="filterNotifs('all', this)">All</button>
                <button class="notif-tab" onclick="filterNotifs('reschedule', this)">Reschedules</button>
                <button class="notif-tab" onclick="filterNotifs('unread', this)">Unread</button>
              </div>
              <button class="notif-mark-all" onclick="markAllRead()" style="font-size:.75rem;padding:5px 12px">
                ✓ Mark all read
              </button>
            </div>
          </div>

          <div class="notif-full-list" id="notifFullList">
            <div class="notif-full-empty">
              <div class="empty-icon">🔕</div>
              <p>No notifications yet</p>
              <small>You'll see pickup alerts and reschedule notices here.</small>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════
           SECTION: My Profile
      ══════════════════════════════════ -->
      <section class="section" id="section-profile">
        <div class="section-header">
          <h2>My Profile</h2>
          <p>View and update your personal details.</p>
        </div>

        <div class="profile-card">
          <div class="profile-avatar-area">
            <div class="profile-avatar"><?= $avatar_letter ?></div>
            <div class="profile-avatar-info">
              <h3 id="profile-heading-name"><?= $user_name ?></h3>
              <span class="profile-role-badge">🌿 Resident User</span>
            </div>
          </div>

          <div id="profileView">
            <div class="profile-details-grid">
              <div class="profile-detail-item">
                <div class="detail-icon">👤</div>
                <div class="detail-content"><label>Full Name</label><span id="view-name"><?= $user_name ?></span></div>
              </div>
              <div class="profile-detail-item">
                <div class="detail-icon">✉️</div>
                <div class="detail-content"><label>Email Address</label><span id="view-email"><?= $user_email ?: '—' ?></span></div>
              </div>
              <div class="profile-detail-item">
                <div class="detail-icon">📱</div>
                <div class="detail-content"><label>Phone Number</label><span id="view-phone"><?= $user_phone ?: '—' ?></span></div>
              </div>
              <div class="profile-detail-item">
                <div class="detail-icon">🏘️</div>
                <div class="detail-content"><label>Ward</label><span id="view-ward"><?= $ward_display ?></span></div>
              </div>
              <div class="profile-detail-item profile-full">
                <div class="detail-icon">🏠</div>
                <div class="detail-content"><label>Address</label><span id="view-address"><?= $user_address ?: '—' ?></span></div>
              </div>
            </div>
            <div class="profile-actions">
              <button class="edit-profile-btn" onclick="toggleEditMode(true)">
                <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Profile
              </button>
            </div>
          </div>

          <div id="profileEdit" style="display:none">
            <form id="profileForm" novalidate>
              <div class="form-grid">
                <div class="field">
                  <label for="edit-name">Full Name</label>
                  <div class="inp-wrap"><input type="text" id="edit-name" name="name" value="<?= $user_name ?>" placeholder="Your full name" required></div>
                </div>
                <div class="field">
                  <label for="edit-phone">Phone Number</label>
                  <div class="inp-wrap"><input type="tel" id="edit-phone" name="phone" value="<?= $user_phone ?>" placeholder="+91 98765 43210" inputmode="tel"></div>
                </div>
                <div class="field full-width">
                  <label for="edit-email">Email Address</label>
                  <div class="inp-wrap"><input type="email" id="edit-email" name="email" value="<?= $user_email ?>" placeholder="you@example.com" required></div>
                </div>
                <div class="field full-width">
                  <label for="edit-address">Address</label>
                  <div class="inp-wrap"><textarea id="edit-address" name="address" rows="3" placeholder="Enter your full address…"><?= $user_address ?></textarea></div>
                </div>
                <div class="field full-width">
                  <label for="edit-password">New Password <span style="color:var(--text-4);font-weight:400;text-transform:none;letter-spacing:0">(leave blank to keep current)</span></label>
                  <div class="inp-wrap pw-wrap">
                    <input type="password" id="edit-password" name="password" placeholder="Enter new password…">
                    <button type="button" class="eye-inline" onclick="togglePassView(this)">
                      <svg class="eye-on" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      <svg class="eye-off" viewBox="0 0 24 24" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                  </div>
                </div>
              </div>
              <div class="profile-edit-actions">
                <button type="submit" class="submit-btn" id="saveProfileBtn">
                  <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                  <span class="btn-txt">Save Changes</span>
                  <div class="btn-spin"></div>
                </button>
                <button type="button" class="cancel-btn" onclick="toggleEditMode(false)">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- ░░ AVAILABILITY MODAL ░░ -->
  <div class="modal-backdrop" id="availModal" onclick="closeAvailModal(event)">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-header">
        <h3>⚠️ Mark Unavailability</h3>
        <button class="modal-close" onclick="closeAvailModal()">&times;</button>
      </div>
      <div class="modal-body">
        <p>You are currently scheduled for:</p>
        <div class="modal-date-box" id="modalCurrentDate">—</div>
        <p class="modal-info">If you are <strong>not available</strong> on this date, your request will be moved to the next available batch. The collector will notify you of the new date.</p>
        <div class="modal-new-date-row">
          <span>New pickup date will be:</span>
          <strong id="modalNewDate" class="modal-new-date">—</strong>
        </div>
      </div>
      <div class="modal-footer">
        <button class="modal-cancel-btn" onclick="closeAvailModal()">Keep Current Date</button>
        <button class="modal-confirm-btn" id="confirmAvailBtn" onclick="confirmUnavailable()">
          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          Yes, Reschedule
        </button>
      </div>
    </div>
  </div>

  <script src="user-dashboard.js"></script>
</body>
</html>