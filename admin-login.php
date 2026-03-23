<?php
session_start();
if(isset($_SESSION['admin_id'])){
    header("Location: admin-dashboard.php");
    exit();
}
$error = $_GET['error'] ?? '';
$errorMsg = '';
if($error === '1')            $errorMsg = 'Invalid username or password. Please try again.';
if($error === 'unauthorized') $errorMsg = 'Access denied. You do not have admin privileges.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | SmartBin Connect</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* =============================================
       SmartWaste — Admin Login (matches collector login)
       ============================================= */

    :root {
      --g50:  #f0fdf4; --g100: #dcfce7; --g200: #bbf7d0;
      --g300: #86efac; --g400: #4ade80; --g500: #22c55e;
      --g600: #16a34a; --g700: #15803d; --g800: #166534;
      --g900: #14532d; --g950: #052e16;

      /* Blue accent for admin — differentiates from collector (amber) and user (green) */
      --b300: #93c5fd; --b400: #60a5fa; --b500: #3b82f6;
      --b600: #2563eb; --b700: #1d4ed8;

      --white: #ffffff;
      --font: 'Plus Jakarta Sans', system-ui, sans-serif;
      --ease: cubic-bezier(.4,0,.2,1);
      --bounce: cubic-bezier(.34,1.56,.64,1);
      --dur: .22s;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { height: 100%; }

    body {
      font-family: var(--font);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background-color: var(--g950);
      overflow-x: hidden;
    }
    a { text-decoration: none; color: inherit; }
    button { font-family: var(--font); cursor: pointer; }

    /* ── Background ───────────────────────── */
    .bg {
      position: fixed; inset: 0; z-index: 0;
      background:
        radial-gradient(ellipse 75% 50% at 50% -8%,  rgba(59,130,246,.15) 0%, transparent 60%),
        radial-gradient(ellipse 55% 45% at 95% 95%,  rgba(22,163,74,.18)  0%, transparent 55%),
        radial-gradient(ellipse 45% 40% at 0%  80%,  rgba(20,83,45,.5)    0%, transparent 55%),
        linear-gradient(160deg, #080f1a 0%, #071a0d 40%, var(--g950) 70%, #030d05 100%);
    }
    .bg::after {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(74,222,128,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(74,222,128,.03) 1px, transparent 1px);
      background-size: 52px 52px;
      mask-image: radial-gradient(ellipse 90% 90% at 50% 50%, black 30%, transparent 100%);
    }

    /* Orbs */
    .orb {
      position: fixed; border-radius: 50%;
      filter: blur(80px); pointer-events: none; z-index: 0;
      animation: orbDrift ease-in-out infinite alternate;
    }
    .orb1 { width:460px;height:460px;top:-180px;right:-100px; background:rgba(59,130,246,.1); animation-duration:11s; }
    .orb2 { width:360px;height:360px;bottom:-130px;left:-80px; background:rgba(22,163,74,.1); animation-duration:14s;animation-delay:4s; }
    .orb3 { width:240px;height:240px;top:42%;left:58%; background:rgba(37,99,235,.07); animation-duration:9s;animation-delay:2s; }
    @keyframes orbDrift {
      from { transform:translate(0,0) scale(1); }
      to   { transform:translate(18px,28px) scale(1.07); }
    }

    /* Particles */
    .ptcl {
      position: fixed; bottom:-10px; border-radius:50%;
      background:rgba(96,165,250,.12); pointer-events:none; z-index:0;
      animation:rise linear infinite;
    }
    @keyframes rise {
      0%   { transform:translateY(0) scale(1); opacity:0; }
      10%  { opacity:.8; }
      90%  { opacity:.2; }
      100% { transform:translateY(-105vh) scale(.2); opacity:0; }
    }

    /* ── Navbar ────────────────────────────── */
    .navbar {
      position: relative; z-index: 10;
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.1rem 4%;
      border-bottom: 1px solid rgba(255,255,255,.06);
      backdrop-filter: blur(12px);
      background: rgba(5,46,22,.3);
    }
    .brand { display: flex; align-items: center; gap: .6rem; }
    .brand-icon {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--g500), var(--g800));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
      box-shadow: 0 4px 14px rgba(34,197,94,.35);
    }
    .brand-name {
      font-size: 1.1rem; font-weight: 800;
      color: var(--white); letter-spacing: -.02em;
    }
    .back-btn {
      display: inline-flex; align-items: center; gap: .4rem;
      font-size: .8rem; font-weight: 500;
      color: rgba(255,255,255,.6);
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.12);
      padding: .42rem .95rem; border-radius: 100px;
      transition: all var(--dur);
    }
    .back-btn svg { width:13px;height:13px;stroke:currentColor;fill:none;transition:transform var(--dur); }
    .back-btn:hover { color:var(--g300); background:rgba(255,255,255,.13); }
    .back-btn:hover svg { transform:translateX(-3px); }

    /* ── Main ──────────────────────────────── */
    .main {
      position: relative; z-index: 1; flex: 1;
      display: flex; align-items: center; justify-content: center;
      padding: 2.5rem 1rem 4rem;
    }

    .form-panel {
      width: 100%; max-width: 440px;
      animation: panelIn .55s var(--ease) both;
    }
    @keyframes panelIn {
      from { opacity:0; transform:translateY(24px); }
      to   { opacity:1; transform:none; }
    }

    /* Admin role badge — blue */
    .role-badge {
      display: inline-flex; align-items: center; gap: .4rem;
      font-size: .7rem; font-weight: 700; letter-spacing: .09em;
      text-transform: uppercase;
      color: var(--b300);
      background: rgba(59,130,246,.1);
      border: 1px solid rgba(59,130,246,.25);
      padding: .3rem .85rem; border-radius: 100px;
      margin-bottom: 1rem;
    }
    .badge-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--b400); box-shadow: 0 0 7px var(--b400);
      animation: blink 2s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }

    .form-title {
      font-size: clamp(2rem, 5vw, 2.7rem);
      font-weight: 800; color: var(--white);
      letter-spacing: -.04em; line-height: 1.1;
      margin-bottom: .45rem;
    }
    .form-sub {
      font-size: .88rem; color: rgba(255,255,255,.45);
      margin-bottom: 2rem; font-weight: 400; line-height: 1.6;
    }

    /* Admin info pill */
    .id-info-pill {
      display: flex; align-items: center; gap: .6rem;
      background: rgba(59,130,246,.07);
      border: 1px solid rgba(59,130,246,.18);
      border-radius: 10px; padding: .7rem 1rem;
      margin-bottom: 1.4rem;
      font-size: .8rem; color: rgba(255,255,255,.6);
      line-height: 1.5;
    }
    .id-info-pill .pill-icon { font-size: 1.2rem; flex-shrink: 0; }
    .id-info-pill strong { color: var(--b300); font-weight: 600; }

    /* ── Form ──────────────────────────────── */
    .login-form { display: flex; flex-direction: column; gap: 1rem; }

    .field { display: flex; flex-direction: column; gap: .4rem; }
    .field label {
      font-size: .76rem; font-weight: 600;
      color: rgba(255,255,255,.55); letter-spacing: .025em;
    }

    /* Input wrap */
    .iw {
      position: relative; display: flex; align-items: center;
      background: rgba(255,255,255,.06);
      border: 1.5px solid rgba(255,255,255,.1);
      border-radius: 11px;
      transition: border-color var(--dur), box-shadow var(--dur), background var(--dur);
      overflow: hidden;
    }
    .iw:hover:not(.focused):not(.err) {
      border-color: rgba(255,255,255,.18);
      background: rgba(255,255,255,.09);
    }
    .iw.focused {
      border-color: var(--b400);
      background: rgba(59,130,246,.06);
      box-shadow: 0 0 0 3.5px rgba(59,130,246,.16), 0 4px 20px rgba(0,0,0,.2);
    }
    .iw.err {
      border-color: #f87171;
      background: rgba(248,113,113,.07);
      box-shadow: 0 0 0 3px rgba(248,113,113,.15);
    }

    .iw-icon {
      padding: 0 .55rem 0 .9rem;
      display: flex; align-items: center;
      color: rgba(255,255,255,.22); flex-shrink: 0;
      pointer-events: none; transition: color var(--dur);
    }
    .iw-icon svg { width: 15px; height: 15px; stroke: currentColor; fill: none; }
    .iw.focused .iw-icon { color: var(--b400); }
    .iw.err     .iw-icon { color: #f87171; }

    .iw input {
      flex: 1; border: none; background: transparent;
      font-family: var(--font); font-size: .9rem; color: var(--white);
      padding: .82rem .6rem .82rem 0; outline: none; width: 100%;
      caret-color: var(--b400);
    }
    .iw input::placeholder { color: rgba(255,255,255,.22); font-size: .85rem; }

    /* Autofill */
    .iw input:-webkit-autofill,
    .iw input:-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0 1000px rgba(5,46,22,.95) inset;
      -webkit-text-fill-color: var(--white);
      caret-color: var(--white);
    }

    /* Eye toggle */
    .eye-btn {
      background: none; border: none;
      padding: .5rem .8rem;
      display: flex; align-items: center;
      color: rgba(255,255,255,.25); flex-shrink: 0;
      transition: color var(--dur);
    }
    .eye-btn svg { width: 16px; height: 16px; stroke: currentColor; fill: none; display: block; }
    .eye-btn .eye-off { display: none; }
    .eye-btn.on .eye-on  { display: none; }
    .eye-btn.on .eye-off { display: block; }
    .eye-btn:hover { color: var(--b400); }
    .eye-btn.on    { color: var(--b400); }

    /* Field error */
    .field-err {
      font-size: .71rem; color: #fca5a5; font-weight: 500;
      display: none; align-items: center; gap: .3rem;
      animation: errIn .2s var(--ease);
    }
    .field-err.show { display: flex; }
    .field-err svg { width: 11px; height: 11px; stroke: currentColor; fill: none; flex-shrink: 0; }
    @keyframes errIn { from{opacity:0;transform:translateY(-3px)} to{opacity:1;transform:none} }

    /* Server error banner */
    .server-err {
      display: none;
      align-items: center; gap: .65rem;
      background: rgba(239,68,68,.1);
      border: 1px solid rgba(239,68,68,.25);
      border-radius: 10px; padding: .8rem 1rem;
      margin-bottom: .5rem;
      font-size: .82rem; color: #fca5a5;
      animation: errIn .25s var(--ease);
    }
    .server-err.show { display: flex; }
    .server-err svg { width: 16px; height: 16px; stroke: #f87171; fill: none; flex-shrink: 0; stroke-width: 2; }

    /* Submit button — blue for admin */
    .submit-btn {
      width: 100%; margin-top: .3rem;
      display: flex; align-items: center; justify-content: center; gap: .55rem;
      padding: 1rem 1.5rem;
      background: linear-gradient(135deg, #f59e0b, #b45309);
      color: var(--white);
      font-family: var(--font); font-size: .95rem; font-weight: 700;
      letter-spacing: .01em;
      border: none; border-radius: 12px;
      box-shadow: 0 6px 28px rgba(245,158,11,.35), 0 1px 0 rgba(255,255,255,.12) inset;
      transition: transform var(--dur) var(--bounce), box-shadow var(--dur), opacity var(--dur);
      position: relative; overflow: hidden;
    }
    .submit-btn::before {
      content: ''; position: absolute; top:0; left:-100%;
      width:60%; height:100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.2), transparent);
      transition: left .6s var(--ease);
    }
    .submit-btn:hover::before { left:160%; }
    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 40px rgba(245,158,11,.5), 0 1px 0 rgba(255,255,255,.12) inset;
    }
    .submit-btn:active { transform: translateY(0); }
    .submit-btn.loading { opacity:.7; pointer-events:none; }
    .submit-btn svg { width:17px;height:17px;stroke:currentColor;fill:none;transition:transform var(--dur); }
    .submit-btn:hover svg { transform:translateX(4px); }

    .spinner {
      width:17px; height:17px;
      border:2.5px solid rgba(255,255,255,.3); border-top-color:#fff;
      border-radius:50%; animation:spin .7s linear infinite; display:none;
    }
    .submit-btn.loading .spinner { display:block; }
    .submit-btn.loading .btn-lbl,
    .submit-btn.loading .btn-ico { display:none; }
    @keyframes spin { to{transform:rotate(360deg)} }

    /* Divider */
    .divider {
      display: flex; align-items: center; gap: .75rem;
      margin: 1.3rem 0 .9rem;
      color: rgba(255,255,255,.18); font-size: .74rem;
    }
    .divider::before, .divider::after {
      content:''; flex:1; height:1px; background:rgba(255,255,255,.1);
    }

    /* Portal links */
    .portals {
      display: flex; align-items: center; justify-content: center;
      gap: .5rem; flex-wrap: wrap;
      font-size: .77rem; color: rgba(255,255,255,.3);
    }
    .portal-link {
      display: inline-flex; align-items: center; gap: .3rem;
      font-size: .77rem; font-weight: 600;
      color: rgba(255,255,255,.55);
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.1);
      padding: .3rem .75rem; border-radius: 100px;
      transition: all var(--dur);
    }
    .portal-link:hover {
      background: rgba(74,222,128,.12);
      border-color: rgba(74,222,128,.25);
      color: var(--g300);
    }

    /* Secure note */
    .secure-note {
      display: flex; align-items: center; justify-content: center; gap: .4rem;
      font-size: .7rem; color: rgba(255,255,255,.2); margin-top: 1.2rem;
    }
    .secure-note svg { width:11px;height:11px;stroke:var(--g700);fill:none; }

    /* ── Responsive ──────────────────────── */
    @media (max-width: 480px) {
      .main { padding: 1.8rem .9rem 3rem; }
      .navbar { padding: 1rem 5%; }
      .form-title { font-size: 1.9rem; }
    }
    @media (max-width: 360px) {
      .form-title { font-size: 1.65rem; }
    }
    @media (max-height: 600px) and (orientation: landscape) {
      .main { padding: 1.2rem .9rem 2rem; align-items: flex-start; }
      .login-form { gap: .75rem; }
      .form-sub { margin-bottom: 1.2rem; }
    }
  </style>
</head>
<body>

  <!-- BG layers -->
  <div class="bg"></div>
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>
  <div class="ptcl" style="left:10%;width:4px;height:4px;animation-duration:13s;animation-delay:0s;"></div>
  <div class="ptcl" style="left:30%;width:3px;height:3px;animation-duration:17s;animation-delay:2s;"></div>
  <div class="ptcl" style="left:60%;width:5px;height:5px;animation-duration:11s;animation-delay:5s;"></div>
  <div class="ptcl" style="left:80%;width:3px;height:3px;animation-duration:15s;animation-delay:1s;"></div>

  <!-- Navbar -->
  <nav class="navbar">
    <a href="index.html" class="brand">
      <div class="brand-icon">♻</div>
      <span class="brand-name">SmartBin Connect</span>
    </a>
    <a href="index.html" class="back-btn">
      <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
      Back to Home
    </a>
  </nav>

  <!-- Main -->
  <main class="main">
    <div class="form-panel">

      <div class="role-badge">
        <span class="badge-dot"></span>
        Admin Portal
      </div>

      <h1 class="form-title">Admin Login 🛡️</h1>
      <p class="form-sub">Sign in with your <strong>admin credentials</strong> to access the control panel.</p>

      <!-- Info pill -->
      <div class="id-info-pill">
        <span class="pill-icon">ℹ️</span>
        <span>This portal is for <strong>authorized administrators only</strong>. Unauthorized access attempts are logged.</span>
      </div>

      <!-- Server error banner -->
      <div class="server-err <?= $errorMsg ? 'show' : '' ?>" id="serverErr">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="serverErrText"><?= htmlspecialchars($errorMsg) ?></span>
      </div>

      <form class="login-form" id="adminForm" action="backend/admin_login.php" method="POST" novalidate>

        <!-- Username -->
        <div class="field">
          <label for="username">Username</label>
          <div class="iw" id="iw-user">
            <span class="iw-icon">
              <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </span>
            <input
              type="text"
              id="username"
              name="username"
              placeholder="Enter admin username"
              autocomplete="username"
              autocapitalize="none"
              required>
          </div>
          <div class="field-err" id="err-user">
            <svg viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Please enter your username.
          </div>
        </div>

        <!-- Password -->
        <div class="field">
          <label for="password">Password</label>
          <div class="iw" id="iw-pw">
            <span class="iw-icon">
              <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              required>
            <button type="button" class="eye-btn" id="eyeBtn" aria-label="Toggle password visibility">
              <svg class="eye-on" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg class="eye-off" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
          <div class="field-err" id="err-pw">
            <svg viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Please enter your password.
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="submit-btn" id="submitBtn">
          <div class="spinner"></div>
          <span class="btn-lbl">Sign In to Dashboard</span>
          <svg class="btn-ico" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
          </svg>
        </button>

      </form>

      <div class="divider">other portals</div>

      <div class="portals">
        <span>Go to:</span>
        <a href="login.html" class="portal-link">👤 User Portal</a>
        <a href="collector-login.html" class="portal-link">🚛 Collector Portal</a>
      </div>

      <p class="secure-note">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        256-bit SSL encryption · Authorized personnel only
      </p>

    </div>
  </main>

  <script>
    /* ── PHP-rendered error (no JS needed) ─── */

    /* ── Focus rings ──────────────────────── */
    document.querySelectorAll('.iw input').forEach(inp => {
      const w = inp.closest('.iw');
      inp.addEventListener('focus', () => w.classList.add('focused'));
      inp.addEventListener('blur',  () => w.classList.remove('focused'));
    });

    /* ── Eye toggle ───────────────────────── */
    const eyeBtn = document.getElementById('eyeBtn');
    const pwInp  = document.getElementById('password');
    eyeBtn.addEventListener('click', () => {
      const show = pwInp.type === 'password';
      pwInp.type = show ? 'text' : 'password';
      eyeBtn.classList.toggle('on', show);
      pwInp.focus();
    });

    /* ── Live error clearing ─────────────── */
    document.getElementById('username').addEventListener('input', e => {
      if(e.target.value.trim().length >= 1){
        document.getElementById('iw-user').classList.remove('err');
        document.getElementById('err-user').classList.remove('show');
        document.getElementById('serverErr').classList.remove('show');
      }
    });
    document.getElementById('password').addEventListener('input', e => {
      if(e.target.value.length >= 1){
        document.getElementById('iw-pw').classList.remove('err');
        document.getElementById('err-pw').classList.remove('show');
        document.getElementById('serverErr').classList.remove('show');
      }
    });

    /* ── Form validation + submit ────────── */
    const form      = document.getElementById('adminForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', e => {
      e.preventDefault();
      let ok = true;

      const user = document.getElementById('username').value.trim();
      const pw   = document.getElementById('password').value;

      if(!user){
        document.getElementById('iw-user').classList.add('err');
        document.getElementById('err-user').classList.add('show');
        ok = false;
      }
      if(!pw){
        document.getElementById('iw-pw').classList.add('err');
        document.getElementById('err-pw').classList.add('show');
        ok = false;
      }

      if(!ok) return;

      submitBtn.classList.add('loading');
      form.submit();
    });
  </script>

</body>
</html>
