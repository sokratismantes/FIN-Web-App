<?php
declare(strict_types=1);

/**
 * Fin — Landing (Login / Sign up) page (τύπου “Banking” hero)
 * Κρατάει τα χρώματα του app + χρησιμοποιεί ΤΟ ΔΙΚΟ ΣΟΥ logo.
 *
 * Βάλε το logo αρχείο στο ίδιο folder και ονόμασέ το:
 *   fin_logo.png
 * ή άλλαξε το $logoPath.
 *
 * Redirect μετά το unlock / signup:
 */
$redirect = 'index.php';
$logoPath = 'fin_logo.png';
?><!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>Fin — Offline Finance</title>
  <meta name="theme-color" content="#0A0C12" />
  <style>
    :root{
      --pad: 16px; --maxw: 1100px;
      --bg: #0A0C12;

      --surface: rgba(255,255,255,.035);
      --surface2: rgba(255,255,255,.055);

      --border: rgba(255,255,255,.10);
      --border2: rgba(255,255,255,.16);

      --text: rgba(255,255,255,.94);
      --muted: rgba(255,255,255,.65);
      --muted2: rgba(255,255,255,.48);

      --accent: #6D5EF7;
      --accent2: #49D9A1;

      --shadowSoft: 0 18px 55px rgba(0,0,0,.35);
      --shadow: 0 28px 90px rgba(0,0,0,.58);

      --tap: cubic-bezier(.2,.8,.2,1);
      --font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial;

      --glassBg: rgba(255,255,255,.055);
      --glassBg2: rgba(255,255,255,.085);
      --glassStroke: rgba(255,255,255,.14);
      --glassStroke2: rgba(255,255,255,.22);
      --glassHi: rgba(255,255,255,.22);
    }

    html[data-theme="light"]{
      --bg: #F6F7FB;
      --surface: rgba(10,15,25,.035);
      --surface2: rgba(10,15,25,.055);
      --border: rgba(10,15,25,.10);
      --border2: rgba(10,15,25,.16);
      --text: rgba(10,15,25,.92);
      --muted: rgba(10,15,25,.62);
      --muted2: rgba(10,15,25,.46);
      --shadowSoft: 0 14px 45px rgba(10,15,25,.10);
      --shadow: 0 22px 78px rgba(10,15,25,.20);

      --glassBg: rgba(10,15,25,.035);
      --glassBg2: rgba(10,15,25,.06);
      --glassStroke: rgba(10,15,25,.12);
      --glassStroke2: rgba(10,15,25,.18);
      --glassHi: rgba(255,255,255,.70);
    }

    *{ box-sizing:border-box; }
    html,body{ height:100%; }
    body{
      margin:0; font-family: var(--font); background: var(--bg); color: var(--text);
      -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
      overflow-x:hidden;
    }
    a{ color:inherit; text-decoration:none; }

    .bg::before{
      content:""; position: fixed; inset: -240px; z-index:-2; pointer-events:none;
      background:
        radial-gradient(900px 520px at 18% -10%, rgba(109,94,247,.26), transparent 60%),
        radial-gradient(820px 560px at 85% 6%, rgba(73,217,161,.13), transparent 65%),
        radial-gradient(980px 640px at 50% 105%, rgba(255,77,109,.08), transparent 65%);
      filter: saturate(110%);
    }
    .noise{
      position: fixed; inset:0; z-index:-1; pointer-events:none;
      opacity: .06;
      background-image:
        radial-gradient(circle at 10% 10%, rgba(255,255,255,.9) 1px, transparent 1px),
        radial-gradient(circle at 60% 30%, rgba(255,255,255,.9) 1px, transparent 1px),
        radial-gradient(circle at 30% 80%, rgba(255,255,255,.9) 1px, transparent 1px);
      background-size: 120px 120px, 180px 180px, 220px 220px;
      mix-blend-mode: overlay;
    }

    .wrap{
      max-width: var(--maxw);
      margin: 0 auto;
      padding: calc(18px + env(safe-area-inset-top)) var(--pad) calc(28px + env(safe-area-inset-bottom));
      min-height: 100%;
      display:flex;
      flex-direction:column;
      gap: 18px;
    }

    /* Top nav (τύπου landing) */
    .nav{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 14px;
      position: sticky;
      top: 0;
      z-index: 10;
      padding: 10px 0 6px;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      background: linear-gradient(to bottom, rgba(10,12,18,.90), rgba(10,12,18,.55), rgba(10,12,18,0));
    }
    html[data-theme="light"] .nav{
      background: linear-gradient(to bottom, rgba(246,247,251,.92), rgba(246,247,251,.70), rgba(246,247,251,0));
    }

    .brand{
      display:flex; align-items:center; gap: 10px; min-width:0;
      user-select:none;
    }
    .logo{
      width:42px; height:42px; border-radius: 16px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.02);
      overflow:hidden;
      box-shadow: 0 18px 60px rgba(109,94,247,.14);
      flex: 0 0 auto;
    }
    .logo img{ width:100%; height:100%; object-fit: cover; display:block; transform: scale(1.04); }
    .brandMeta{ min-width:0; }
    .brandMeta .name{
      font-size: 14.5px; font-weight: 950; letter-spacing:-.25px; margin:0;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .brandMeta .sub{
      margin-top: 2px;
      font-size: 12px;
      color: var(--muted);
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }

    .navLinks{
      display:none;
      gap: 18px;
      align-items:center;
      color: var(--muted);
      font-size: 12.5px;
      user-select:none;
    }
    .navLinks a{ padding: 8px 10px; border-radius: 999px; }
    .navLinks a:hover{ color: var(--text); background: var(--surface); border: 1px solid var(--border); padding: 7px 9px; }
    @media (min-width: 880px){
      .navLinks{ display:flex; }
    }

    .navActions{ display:flex; gap: 10px; align-items:center; }

    .icon-btn{
      width:42px;height:42px;
      display:inline-flex; align-items:center; justify-content:center;
      border: 1px solid var(--border);
      border-radius: 14px;
      background: var(--surface);
      cursor:pointer;
      user-select:none;
      transition: transform .16s var(--tap);
      backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    }
    .icon-btn:hover{ border-color: var(--border2); background: var(--surface2); }
    .icon-btn:active{ transform: translateY(1px) scale(.99); }

    .btn{
      padding: 10px 12px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: var(--surface);
      color: var(--text);
      font-weight: 950;
      font-size: 12.5px;
      cursor:pointer;
      user-select:none;
      transition: transform .16s var(--tap);
      white-space:nowrap;
    }
    .btn:hover{ border-color: var(--border2); background: var(--surface2); }
    .btn:active{ transform: translateY(1px) scale(.99); }
    .btn.primary{
      border-color: rgba(255,255,255,.12);
      background: linear-gradient(135deg, rgba(109,94,247,.95), rgba(73,217,161,.85));
      color: rgba(255,255,255,.96);
      box-shadow: 0 18px 60px rgba(109,94,247,.20);
    }
    html[data-theme="light"] .btn.primary{ border-color: rgba(10,15,25,.08); }

    /* Hero section (σαν το παράδειγμα) */
    .hero{
      border-radius: 28px;
      border: 1px solid var(--border2);
      background:
        radial-gradient(900px 520px at 18% -10%, rgba(109,94,247,.28), transparent 60%),
        radial-gradient(820px 560px at 85% 8%, rgba(73,217,161,.12), transparent 65%),
        linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
      box-shadow: var(--shadow);
      overflow:hidden;
      position: relative;
    }
    .hero::after{
      content:""; position:absolute; inset:0; pointer-events:none;
      background: radial-gradient(760px 260px at 20% 0%, rgba(255,255,255,.10), transparent 60%);
      opacity: .55; mix-blend-mode: overlay;
    }

    .heroInner{
      padding: 18px;
      display:grid;
      grid-template-columns: 1fr;
      gap: 16px;
      align-items:center;
      position: relative;
      z-index: 1;
    }
    @media (min-width: 900px){
      .heroInner{
        grid-template-columns: 1.05fr .95fr;
        gap: 18px;
        padding: 22px;
      }
    }

    .kicker{
      display:inline-flex; align-items:center; gap:10px;
      padding: 9px 12px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.02);
      color: var(--muted);
      font-size: 12.5px;
      user-select:none;
      width: fit-content;
    }
    .kdot{ width:8px; height:8px; border-radius:999px; background: linear-gradient(135deg, rgba(109,94,247,.95), rgba(73,217,161,.85)); }

    .h1{
      margin: 12px 0 10px;
      font-size: 34px;
      line-height: 1.05;
      font-weight: 980;
      letter-spacing: -0.9px;
    }
    @media (min-width: 900px){
      .h1{ font-size: 44px; }
    }

    .lead{
      margin:0;
      color: var(--muted);
      font-size: 13.5px;
      line-height: 1.55;
      max-width: 62ch;
    }

    .ctaRow{
      display:flex;
      gap: 10px;
      flex-wrap:wrap;
      margin-top: 14px;
      align-items:center;
    }

    .ghost{
      background: rgba(255,255,255,.02);
      color: var(--muted);
    }

    .miniRow{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top: 12px;
      color: var(--muted2);
      font-size: 12px;
    }
    .miniRow span{
      display:inline-flex; align-items:center; gap:8px;
      padding: 8px 10px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.08);
      background: rgba(255,255,255,.01);
    }
    html[data-theme="light"] .miniRow span{ border-color: rgba(10,15,25,.10); }

    /* Illustration side: “phone card” με το logo σου */
    .illus{
      display:flex;
      align-items:center;
      justify-content:center;
      position: relative;
      padding: 10px 0;
    }

    .phone{
      width: min(360px, 100%);
      aspect-ratio: 10 / 12;
      border-radius: 30px;
      border: 1px solid var(--border2);
      background:
        radial-gradient(600px 300px at 30% 10%, rgba(255,255,255,.10), transparent 55%),
        radial-gradient(420px 280px at 80% 90%, rgba(73,217,161,.12), transparent 60%),
        linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
      box-shadow: 0 32px 120px rgba(0,0,0,.55);
      overflow:hidden;
      position: relative;
      transform: translateZ(0);
    }
    .phone::before{
      content:""; position:absolute; inset:-2px; pointer-events:none;
      background:
        radial-gradient(740px 260px at 20% 0%, rgba(255,255,255,.12), transparent 60%),
        radial-gradient(520px 240px at 85% 15%, rgba(109,94,247,.18), transparent 60%);
      opacity:.55;
    }
    .phoneTop{
      height: 14px;
      width: 36%;
      border-radius: 0 0 16px 16px;
      background: rgba(255,255,255,.10);
      border: 1px solid rgba(255,255,255,.10);
      margin: 10px auto 0;
      position: relative;
      z-index:1;
    }
    .screen{
      padding: 16px;
      height: calc(100% - 28px);
      position: relative;
      z-index: 1;
      display:flex;
      flex-direction:column;
      gap: 12px;
      justify-content:center;
      align-items:center;
    }
    .logoBig{
      width: 78%;
      aspect-ratio: 1 / 1;
      border-radius: 28px;
      border: 1px solid var(--border2);
      background: rgba(255,255,255,.02);
      overflow:hidden;
      box-shadow: 0 22px 90px rgba(109,94,247,.18);
    }
    .logoBig img{ width:100%; height:100%; object-fit: cover; display:block; transform: scale(1.04); }

    .statRow{
      width: 92%;
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .stat{
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(255,255,255,.02);
      padding: 12px 12px;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    html[data-theme="light"] .stat{ border-color: rgba(10,15,25,.10); }
    .stat .lab{ font-size: 12px; color: var(--muted); }
    .stat .val{
      margin-top: 8px;
      font-weight: 980;
      letter-spacing: -.4px;
      font-size: 18px;
    }
    .val.accent{ color: var(--accent2); }
    .val.accent2{ color: rgba(109,94,247,.98); }

    /* Modal (login/signup sheet) */
    .overlay{
      position: fixed; inset: 0;
      background: rgba(0,0,0,.55);
      display:none;
      z-index: 40;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }
    html[data-theme="light"] .overlay{ background: rgba(10,15,25,.30); }
    .overlay.show{ display:block; }

    .sheet{
      position: fixed;
      left: 50%;
      transform: translateX(-50%);
      bottom: -110%;
      width: min(560px, calc(100% - 24px));
      z-index: 50;
      transition: bottom .28s var(--tap);
      padding-bottom: calc(12px + env(safe-area-inset-bottom));
    }
    .sheet.show{ bottom: 12px; }

    .sheetCard{
      border-radius: 28px;
      border: 1px solid var(--border2);
      background: rgba(255,255,255,.04);
      box-shadow: var(--shadow);
      overflow:hidden;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      position: relative;
    }
    .sheetCard::after{
      content:""; position:absolute; inset:0; pointer-events:none;
      background: radial-gradient(700px 240px at 20% 0%, rgba(255,255,255,.10), transparent 60%);
      opacity: .55; mix-blend-mode: overlay;
    }
    .handle{
      width: 54px; height: 6px; border-radius: 999px;
      background: rgba(255,255,255,.18); margin: 12px auto 0;
      position: relative; z-index:1;
    }
    html[data-theme="light"] .handle{ background: rgba(10,15,25,.16); }

    .sheetInner{
      padding: 14px 16px 16px;
      position: relative;
      z-index: 1;
    }
    .sheetHead{
      display:flex; align-items:center; justify-content:space-between; gap:10px;
      margin-bottom: 10px;
    }
    .sheetHead .t{
      font-weight: 980; letter-spacing:-.3px;
    }
    .close{
      width:38px; height:38px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: var(--surface);
      cursor:pointer;
      user-select:none;
    }
    .close:hover{ border-color: var(--border2); background: var(--surface2); }

    .seg{
      display:flex;
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow:hidden;
      background: rgba(255,255,255,.02);
      margin-top: 8px;
    }
    .seg button{
      flex:1;
      padding: 10px 12px;
      border:0;
      background: transparent;
      color: var(--muted);
      font-weight: 980;
      cursor:pointer;
      letter-spacing: -.1px;
    }
    .seg button.on{
      background: var(--surface2);
      color: var(--text);
      border: 1px solid var(--border2);
      border-left:0;border-right:0;
    }

    .field{
      margin-top: 12px;
      border-radius: 18px;
      padding: 12px;
      background: linear-gradient(180deg, var(--glassBg2), var(--glassBg));
      border: 1px solid var(--glassStroke);
      box-shadow:
        0 16px 55px rgba(0,0,0,.25),
        inset 0 1px 0 rgba(255,255,255,.08);
      backdrop-filter: blur(16px) saturate(130%);
      -webkit-backdrop-filter: blur(16px) saturate(130%);
      position: relative;
      overflow:hidden;
    }
    .field::before{
      content:""; position:absolute; inset:-2px; pointer-events:none;
      background:
        radial-gradient(600px 220px at 20% 0%, var(--glassHi), transparent 55%),
        radial-gradient(520px 200px at 85% 10%, rgba(109,94,247,.16), transparent 60%);
      opacity:.35;
    }
    .field label{
      display:flex; align-items:center; justify-content:space-between;
      font-size: 12px; color: var(--muted);
      margin-bottom: 8px;
      position: relative; z-index: 1;
      gap: 10px;
    }
    .hintTiny{ font-size: 12px; color: var(--muted2); white-space:nowrap; }
    input{
      width:100%;
      border:0; outline:0;
      background: transparent;
      color: var(--text);
      font-weight: 980;
      font-size: 22px;
      letter-spacing: 6px;
      position: relative; z-index: 1;
      -webkit-appearance:none; appearance:none;
      text-align:center;
      padding: 2px 0;
    }
    input::placeholder{ color: rgba(255,255,255,.35); letter-spacing: 4px; }
    html[data-theme="light"] input::placeholder{ color: rgba(10,15,25,.35); }

    .field:focus-within{
      border-color: var(--glassStroke2);
      box-shadow:
        0 18px 70px rgba(0,0,0,.32),
        0 0 0 3px rgba(109,94,247,.18),
        inset 0 1px 0 rgba(255,255,255,.10);
    }

    .sheetCta{ display:flex; gap:10px; margin-top: 14px; }
    .sheetCta .btn{ width:100%; }

    #toast{
      position:fixed; left:50%; transform:translateX(-50%);
      bottom: 22px; z-index: 70;
      padding: 10px 12px; border-radius: 14px;
      border: 1px solid var(--border);
      background: var(--surface);
      color: var(--text);
      box-shadow: var(--shadowSoft);
      display:none;
      font-size: 13px;
      max-width: min(560px, calc(100% - 32px));
      user-select:none;
      backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    }

    :focus-visible{ outline: 2px solid rgba(109,94,247,.8); outline-offset: 2px; border-radius: 12px; }
    @media (prefers-reduced-motion: reduce){ *{ transition:none !important; scroll-behavior:auto !important; } }
  </style>
</head>

<body class="bg">
  <div class="noise" aria-hidden="true"></div>

  <div class="wrap">

    <header class="nav">
      <div class="brand">
        <div class="logo" aria-label="Fin logo">
          <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Fin logo" />
        </div>
        <div class="brandMeta">
          <p class="name">Fin</p>
          <div class="sub">Offline finance</div>
        </div>
      </div>

      <nav class="navLinks" aria-label="Navigation">
        <a href="#features">Features</a>
        <a href="#privacy">Privacy</a>
        <a href="#how">How it works</a>
      </nav>

      <div class="navActions">
        <button class="icon-btn" type="button" aria-label="Theme" title="Theme" onclick="toggleTheme()">🌓</button>
        <button class="btn ghost" type="button" onclick="openAuth('login')">Login</button>
        <button class="btn primary" type="button" onclick="openAuth('signup')">Sign up</button>
      </div>
    </header>

    <section class="hero" aria-label="Hero">
      <div class="heroInner">
        <div>
          <div class="kicker"><span class="kdot"></span> Local-only • No cloud • Your device</div>
          <div class="h1">Track money offline.<br/>Feel in control.</div>
          <p class="lead">
            Fin είναι finance tracker “local-first”: τα δεδομένα σου μένουν στη συσκευή,
            με PIN lock και γρήγορο UI στο στυλ του app.
          </p>

          <div class="ctaRow">
            <button class="btn primary" type="button" onclick="openAuth('signup')">Get started</button>
            <button class="btn" type="button" onclick="openAuth('login')">Try login</button>
          </div>

          <div class="miniRow" id="features">
            <span>🔒 PIN lock</span>
            <span>📦 Offline storage</span>
            <span>📊 Insights</span>
            <span id="privacy">🛡️ No account</span>
          </div>
        </div>

        <div class="illus" aria-label="Illustration">
          <div class="phone">
            <div class="phoneTop"></div>
            <div class="screen">
              <div class="logoBig">
                <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Fin logo large" />
              </div>
              <div class="statRow">
                <div class="stat">
                  <div class="lab">This month</div>
                  <div class="val accent2">+ Income</div>
                </div>
                <div class="stat">
                  <div class="lab">This month</div>
                  <div class="val accent">- Expense</div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <section id="how" style="color:var(--muted); font-size:12.5px; line-height:1.5; padding: 0 2px;">
      Tip: Μπορείς να χρησιμοποιήσεις την ίδια λογική PIN που ήδη έχεις στο app (ίδια keys στο localStorage).
    </section>

  </div>

  <!-- Auth overlay + sheet -->
  <div class="overlay" id="overlay" onclick="closeAuth()"></div>

  <div class="sheet" id="sheetAuth" aria-hidden="true">
    <div class="sheetCard" role="dialog" aria-modal="true" aria-label="Login / Signup">
      <div class="handle"></div>
      <div class="sheetInner">
        <div class="sheetHead">
          <div class="t" id="authTitle">—</div>
          <button class="close" type="button" onclick="closeAuth()" aria-label="Close">✕</button>
        </div>

        <div class="seg" role="tablist" aria-label="Auth mode">
          <button id="tabLogin" class="on" type="button" onclick="setAuthMode('login')">Login</button>
          <button id="tabSignup" type="button" onclick="setAuthMode('signup')">Sign up</button>
        </div>

        <div class="field">
          <label for="pin">
            <span>PIN</span>
            <span class="hintTiny">4 digits</span>
          </label>
          <input id="pin" type="password" inputmode="numeric" maxlength="4" placeholder="••••" />
        </div>

        <div class="field" id="confirmWrap" style="display:none;">
          <label for="pin2">
            <span>Confirm PIN</span>
            <span class="hintTiny">repeat</span>
          </label>
          <input id="pin2" type="password" inputmode="numeric" maxlength="4" placeholder="••••" />
        </div>

        <div class="sheetCta">
          <button class="btn" type="button" onclick="clearPins()">Clear</button>
          <button class="btn primary" type="button" onclick="submitAuth()">Continue</button>
        </div>

        <div style="margin-top:12px; display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; color:var(--muted2); font-size:12px;">
          <span>Enter = Continue • Esc = Close</span>
          <span style="cursor:pointer; font-weight:900; color: rgba(255,77,109,.92);" onclick="forgetPin()">Forget PIN</span>
        </div>
      </div>
    </div>
  </div>

  <div id="toast"></div>

  <script>
    const REDIRECT = <?= json_encode($redirect) ?>;

    // Same keys as your app
    const PIN_KEY = 'fin_pin_hash_v1';
    const UNLOCK_KEY = 'fin_pin_unlocked_v1';

    const state = { mode: 'login' };

    // Theme
    function applyTheme(theme){
      const html = document.documentElement;
      if(theme === 'light') html.setAttribute('data-theme','light');
      else html.removeAttribute('data-theme');
      localStorage.setItem('fin_theme', theme);
    }
    function toggleTheme(){
      const current = localStorage.getItem('fin_theme') || 'dark';
      applyTheme(current === 'dark' ? 'light' : 'dark');
      toast((current === 'dark') ? 'Light mode' : 'Dark mode');
    }
    (function initTheme(){
      const saved = localStorage.getItem('fin_theme');
      if(saved) applyTheme(saved);
    })();

    // Toast
    function toast(msg){
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.display = 'block';
      t.style.opacity = '1';
      clearTimeout(window.__toastTimer);
      window.__toastTimer = setTimeout(()=>{
        t.style.opacity = '0';
        setTimeout(()=> t.style.display='none', 180);
      }, 1700);
    }

    // Crypto
    async function sha256(text){
      const enc = new TextEncoder().encode(text);
      const buf = await crypto.subtle.digest('SHA-256', enc);
      return [...new Uint8Array(buf)].map(b=>b.toString(16).padStart(2,'0')).join('');
    }
    function hasPin(){ return !!localStorage.getItem(PIN_KEY); }

    // Auth sheet
    function openAuth(mode){
      document.getElementById('overlay').classList.add('show');
      document.getElementById('sheetAuth').classList.add('show');
      document.getElementById('sheetAuth').setAttribute('aria-hidden','false');
      setAuthMode(mode || (hasPin() ? 'login' : 'signup'));
      setTimeout(()=> document.getElementById('pin').focus(), 120);
    }
    function closeAuth(){
      document.getElementById('overlay').classList.remove('show');
      document.getElementById('sheetAuth').classList.remove('show');
      document.getElementById('sheetAuth').setAttribute('aria-hidden','true');
    }

    function setAuthMode(mode){
      state.mode = mode;
      document.getElementById('tabLogin').classList.toggle('on', mode==='login');
      document.getElementById('tabSignup').classList.toggle('on', mode==='signup');
      document.getElementById('confirmWrap').style.display = (mode==='signup') ? 'block' : 'none';

      document.getElementById('authTitle').textContent = (mode==='signup') ? 'Create PIN' : 'Unlock';
      clearPins();
    }

    function clearPins(){
      document.getElementById('pin').value = '';
      document.getElementById('pin2').value = '';
    }

    function forgetPin(){
      if(!confirm('Θες σίγουρα να σβήσεις το PIN; (Δεν επηρεάζει τα δεδομένα, μόνο το κλείδωμα)')) return;
      localStorage.removeItem(PIN_KEY);
      localStorage.removeItem(UNLOCK_KEY);
      toast('PIN removed');
      setAuthMode('signup');
    }

    async function submitAuth(){
      const p1 = (document.getElementById('pin').value || '').trim();
      const p2 = (document.getElementById('pin2').value || '').trim();

      if(!/^\d{4}$/.test(p1)) return toast('PIN πρέπει να είναι 4 ψηφία');

      if(state.mode === 'signup'){
        if(!/^\d{4}$/.test(p2)) return toast('Επιβεβαίωση: 4 ψηφία');
        if(p1 !== p2) return toast('PIN mismatch');
        const h = await sha256(p1);
        localStorage.setItem(PIN_KEY, h);
        localStorage.setItem(UNLOCK_KEY, '1');
        toast('PIN set');
        setTimeout(()=> location.href = REDIRECT, 220);
        return;
      }

      const stored = localStorage.getItem(PIN_KEY);
      if(!stored){
        toast('Δεν υπάρχει PIN — κάνε Sign up');
        setAuthMode('signup');
        return;
      }

      const h = await sha256(p1);
      if(h !== stored){
        toast('Λάθος PIN');
        document.getElementById('pin').value = '';
        document.getElementById('pin').focus();
        return;
      }

      localStorage.setItem(UNLOCK_KEY, '1');
      toast('Unlocked');
      setTimeout(()=> location.href = REDIRECT, 180);
    }

    // Digits only
    function digitsOnly(el){
      el.addEventListener('input', ()=>{
        el.value = (el.value || '').replace(/[^\d]/g,'').slice(0,4);
      });
    }
    digitsOnly(document.getElementById('pin'));
    digitsOnly(document.getElementById('pin2'));

    // Keyboard
    window.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape') closeAuth();
      if(e.key === 'Enter' && document.getElementById('sheetAuth').classList.contains('show')) submitAuth();
    });
  </script>
</body>
</html>