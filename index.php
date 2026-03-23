<?php
declare(strict_types=1);

$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','transactions','budgets','insights','settings','invest']; // CHANGED: added insights
if (!in_array($page, $allowed, true)) $page = 'dashboard';
?><!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>Fin — Offline Finance</title>
  <meta name="theme-color" content="#0A0C12" />
  <style>
    /* === UI (same as your edited version) + a few additions === */
    :root{
      --radius: 20px; --radius2: 26px; --pad: 16px; --maxw: 1100px;
      --bg: #0A0C12;
      --surface: rgba(255,255,255,.035);
      --surface2: rgba(255,255,255,.055);
      --border: rgba(255,255,255,.10);
      --border2: rgba(255,255,255,.16);
      --text: rgba(255,255,255,.94);
      --muted: rgba(255,255,255,.65);
      --muted2: rgba(255,255,255,.48);
      --accent: #6D5EF7; --accent2: #49D9A1;
      --danger: #FF4D6D; --success: #2FE29B; --warn:#FFB020;
      --shadowSoft: 0 18px 55px rgba(0,0,0,.35);
      --shadow: 0 28px 80px rgba(0,0,0,.55);
      --heroGrad:
        radial-gradient(900px 520px at 18% -10%, rgba(109,94,247,.38), transparent 60%),
        radial-gradient(800px 500px at 85% 10%, rgba(73,217,161,.16), transparent 63%),
        linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
      --tap: cubic-bezier(.2,.8,.2,1);
      --font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial;

      /* ---- Liquid Glass controls (Fields + Select/Date) ---- */
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
      --shadow: 0 22px 70px rgba(10,15,25,.18);
      --heroGrad:
        radial-gradient(900px 520px at 18% -10%, rgba(109,94,247,.25), transparent 60%),
        radial-gradient(800px 500px at 85% 10%, rgba(73,217,161,.12), transparent 63%),
        linear-gradient(180deg, rgba(255,255,255,.90), rgba(255,255,255,.70));

      --glassBg: rgba(10,15,25,.035);
      --glassBg2: rgba(10,15,25,.06);
      --glassStroke: rgba(10,15,25,.12);
      --glassStroke2: rgba(10,15,25,.18);
      --glassHi: rgba(255,255,255,.70);
    }
    *{ box-sizing:border-box; }
    html,body{ height:100%; }
    body{ margin:0; font-family: var(--font); background: var(--bg); color: var(--text);
      -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
    .bg-glow::before{
      display: none;
    }
    a{ color:inherit; text-decoration:none; }
    .wrap{ max-width: var(--maxw); margin: 0 auto;
      padding: calc(var(--pad) + env(safe-area-inset-top)) var(--pad) calc(110px + env(safe-area-inset-bottom)); }
    .topbar{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding: 10px 0 12px; position: sticky; top: 0; z-index: 20;
      backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
      background: linear-gradient(to bottom, rgba(10,12,18,.90), rgba(10,12,18,.55), rgba(10,12,18,0));
      margin-bottom: 8px;
    }
    html[data-theme="light"] .topbar{
      background: linear-gradient(to bottom, rgba(246,247,251,.92), rgba(246,247,251,.70), rgba(246,247,251,0));
    }
    .title{ display:flex; flex-direction:column; gap:4px; }
    .title h1{ font-size: 18px; margin:0; letter-spacing:.2px; font-weight: 850; }
    .title .sub{ font-size: 12.5px; color: var(--muted); display:flex; gap:8px; align-items:center; }
    .pill{
      display:inline-flex; align-items:center; gap:8px; padding: 8px 12px;
      border: 1px solid var(--border); border-radius: 999px;
      background: var(--surface); color: var(--text); font-size: 12.5px;
      user-select:none; cursor:pointer;
    }
    .icon-btn{
      width:40px;height:40px; display:inline-flex; align-items:center;justify-content:center;
      border: 1px solid var(--border); border-radius: 12px; background: var(--surface);
      cursor:pointer; user-select:none;
    }
    .pill:hover, .icon-btn:hover{ border-color: var(--border2); background: var(--surface2); }
    .pill:active, .icon-btn:active{ transform: translateY(1px) scale(.99); }
    .actions{ display:flex; align-items:center; gap:10px; }
    .avatar{
      width:40px;height:40px; border-radius: 999px; border: 1px solid var(--border);
      background: radial-gradient(60px 40px at 20% 10%, rgba(255,255,255,.14), transparent 60%), var(--surface);
      display:flex; align-items:center;justify-content:center;
      font-weight: 950; letter-spacing: -.2px; user-select:none;
    }
    .grid{ display:grid; gap: 14px; grid-template-columns: 1fr; }
    @media (min-width: 880px){ .grid{ grid-template-columns: 1.25fr .75fr; align-items:start; } }
    .card{
      border-radius: var(--radius); border: 1px solid var(--border);
      background: var(--surface); box-shadow: var(--shadowSoft); overflow:hidden; position: relative;
    }
    .card::after{
      content:""; position:absolute; inset:0; border-radius: inherit; pointer-events:none;
      background: radial-gradient(700px 240px at 20% 0%, rgba(255,255,255,.10), transparent 60%);
      opacity: .55; mix-blend-mode: overlay;
    }
    .card.hero{ background: var(--heroGrad); border-color: var(--border2); }
    .card .inner{ padding: 16px; }
    .card.soft{ box-shadow:none; }
    .card:hover{ border-color: var(--border2); }
    .card-header{
      display:flex; align-items:flex-start; justify-content:space-between; gap: 12px;
      margin-bottom: 10px; position: relative; z-index: 1;
    }
    .card-title{
      font-size: 12.5px; color: var(--muted); letter-spacing: .25px; text-transform: uppercase;
    }
    .amount{
      font-size: 40px; font-weight: 950; letter-spacing: -0.9px; line-height: 1.05;
      font-variant-numeric: tabular-nums; margin: 6px 0 12px; position: relative; z-index: 1;
    }
    .amount .minor{ font-size: 14px; color: var(--muted); font-weight: 900; margin-left:8px; }
    .row{ display:flex; gap: 10px; align-items:center; flex-wrap:wrap; position: relative; z-index: 1; }
    .chip{
      display:inline-flex; align-items:center; gap:8px; padding: 10px 12px;
      border-radius: 999px; border: 1px solid var(--border); background: var(--surface);
      font-size: 13px; color: var(--text); user-select:none;
    }
    .dot{ width:8px;height:8px;border-radius:999px; display:inline-block; }
    .dot.success{ background: var(--success); }
    .dot.danger{ background: var(--danger); }
    .quick-row{
      display:flex; gap:10px; overflow:auto; padding: 10px 0 2px;
      scrollbar-width: none; position: relative; z-index: 1;
    }
    .quick-row::-webkit-scrollbar{ display:none; }
    .quick{
      flex: 0 0 auto; display:inline-flex; align-items:center; gap:10px;
      padding: 10px 12px; border-radius: 999px; border: 1px solid var(--border);
      background: var(--surface); font-size: 13px; cursor:pointer; user-select:none;
    }
    .quick:hover{ background: var(--surface2); border-color: var(--border2); }
    .quick:active{ transform: translateY(1px) scale(.99); }
    .quick .k{ width:28px;height:28px;border-radius: 10px;
      display:flex; align-items:center; justify-content:center;
      border:1px solid var(--border); background: var(--surface); }
    .section-title{
      display:flex; align-items:center; justify-content:space-between;
      margin: 16px 2px 10px; color: var(--muted); font-size: 13px; letter-spacing: .2px;
    }
    .list{ display:flex; flex-direction:column; position: relative; z-index: 1; }
    .list-head{
      display:flex; justify-content:space-between; align-items:center;
      padding: 14px 16px 10px; border-bottom: 1px solid var(--border);
    }
    .list-head h3{ margin:0; font-size: 14px; color: var(--text); letter-spacing:-.1px; }
    .list-head span{ font-size: 12.5px; color: var(--muted); }
    .item{
      display:flex; align-items:center; justify-content:space-between; gap: 12px;
      padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.06);
      background: transparent; cursor:pointer; user-select:none;
    }
    html[data-theme="light"] .item{ border-bottom-color: rgba(10,15,25,.08); }
    .item:hover{ background: var(--surface2); }
    .item:last-child{ border-bottom: none; }
    .left{ display:flex; align-items:center; gap: 12px; min-width:0; }
    .ic{
      width:42px;height:42px; border-radius: 16px; border: 1px solid var(--border);
      background: radial-gradient(60px 40px at 20% 10%, rgba(255,255,255,.12), transparent 60%), var(--surface);
      display:flex; align-items:center; justify-content:center; flex: 0 0 auto;
    }
    .meta{ min-width:0; }
    .meta .t{ font-size: 14px; font-weight: 900; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .meta .s{ font-size: 12.5px; color: var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;}
    .right{ text-align:right; flex: 0 0 auto; }
    .amt{ font-size: 14px; font-weight: 950; font-variant-numeric: tabular-nums; letter-spacing: -0.2px; }
    .amt.income{ color: var(--success); }
    .amt.expense{ color: var(--danger); }
    .date{ font-size: 12px; color: var(--muted2); margin-top:2px; }
    .kpi{ display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .mini{ padding: 14px 16px; }
    .mini .lab{ font-size: 12px; color: var(--muted); }
    .mini .val{ margin-top: 6px; font-weight: 950; font-size: 18px; font-variant-numeric: tabular-nums; letter-spacing:-.2px; }
    .budget{ padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.06); cursor:pointer; user-select:none; }
    html[data-theme="light"] .budget{ border-bottom-color: rgba(10,15,25,.08); }
    .budget:hover{ background: var(--surface2); }
    .budget:last-child{ border-bottom:none; }
    .budget .top{ display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .budget .name{ display:flex; align-items:center; gap:10px; font-weight:950; letter-spacing:-.1px; }
    .bar{
      height: 8px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.07);
      border-radius: 999px; overflow:hidden; margin-top: 10px;
    }
    html[data-theme="light"] .bar{ background: rgba(10,15,25,.06); border-color: rgba(10,15,25,.08); }
    .bar > div{ height:100%; width: 40%; background: linear-gradient(90deg, rgba(109,94,247,.95), rgba(73,217,161,.85)); }
    .budget .foot{ display:flex; justify-content:space-between; margin-top: 10px; font-size: 12.5px; color: var(--muted); }
    .bottom-nav{
      position: fixed; left: 50%; transform: translateX(-50%); bottom: 0;
      width: min(var(--maxw), 100%);
      padding: 10px var(--pad) calc(10px + env(safe-area-inset-bottom));
      z-index: 50;
      background: linear-gradient(to top, rgba(10,12,18,.92), rgba(10,12,18,.75), rgba(10,12,18,0));
      backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    }
    html[data-theme="light"] .bottom-nav{
      background: linear-gradient(to top, rgba(246,247,251,.92), rgba(246,247,251,.78), rgba(246,247,251,0));
    }
    .nav{
      display:flex; justify-content:space-between; align-items:flex-end; gap: 10px;
      border-radius: 20px; border: 1px solid var(--border); background: var(--surface);
      padding: 10px 10px; box-shadow: var(--shadow);
    }
    .nav a{
      flex:1; text-align:center; color: var(--muted); font-size: 11.5px;
      padding: 8px 6px; border-radius: 16px; user-select:none;
    }
    .nav a .i{ display:block; font-size: 18px; margin-bottom: 4px; }
    .nav a.active{ color: var(--text); background: var(--surface2); border: 1px solid var(--border2); }
    .nav a:active{ transform: translateY(1px) scale(.99); }
    .add-fab{
      position: fixed; left: 50%; transform: translateX(-50%);
      bottom: 74px; width: 56px; height: 56px; border-radius: 18px;
      border: 1px solid rgba(255,255,255,.16);
      background: radial-gradient(100px 60px at 30% 20%, rgba(255,255,255,.18), transparent 55%),
                  linear-gradient(135deg, rgba(109,94,247,.95), rgba(73,217,161,.85));
      box-shadow: 0 18px 60px rgba(109,94,247,.22);
      display:flex; align-items:center; justify-content:center;
      color: rgba(255,255,255,.96); font-size: 26px; cursor:pointer; user-select:none;
      transition: transform .16s var(--tap); z-index: 60;
    }
    .add-fab:active{ transform: translateX(-50%) translateY(2px) scale(.99); }
    .overlay{
      position: fixed; inset: 0; background: rgba(0,0,0,.52); display:none;
      z-index: 80; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
    }
    html[data-theme="light"] .overlay{ background: rgba(10,15,25,.30); }
    .overlay.show{ display:block; }
    .sheet{
      position: fixed; left: 50%; transform: translateX(-50%); bottom: -110%;
      width: min(var(--maxw), 100%);
      z-index: 90; transition: bottom .28s var(--tap);
      padding: 0 var(--pad) calc(12px + env(safe-area-inset-bottom));
      will-change: bottom;
    }
    .sheet.show{ bottom: 0; }
    .sheet-card{
      border-radius: var(--radius2); border: 1px solid var(--border2);
      background: var(--surface);
      backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
      box-shadow: var(--shadow); overflow:hidden;
    }
    .sheet-handle{
      width: 54px; height: 6px; border-radius: 999px;
      background: rgba(255,255,255,.18); margin: 12px auto 0;
    }
    html[data-theme="light"] .sheet-handle{ background: rgba(10,15,25,.16); }
    .sheet-body{ padding: 14px 16px 16px; }
    .seg{
      display:flex; border: 1px solid var(--border); border-radius: 18px;
      overflow:hidden; background: var(--surface);
    }
    .seg button{
      flex:1; padding: 10px 12px; border:0; background: transparent; color: var(--muted);
      font-weight: 950; cursor:pointer; letter-spacing: -.1px;
    }
    .seg button.on{ background: var(--surface2); color: var(--text); border: 1px solid var(--border2); border-left:0;border-right:0; }

    /* (base) field — will be overridden below with liquid-glass version */
    .field{
      margin-top: 12px; border: 1px solid var(--border); border-radius: 16px;
      padding: 12px; background: var(--surface);
    }
    .field label{ display:block; font-size: 12px; color: var(--muted); margin-bottom: 6px; }

    .amount-in{ display:flex; align-items:baseline; gap:8px; font-variant-numeric: tabular-nums; }
    .amount-in .cur{ color: var(--muted); font-weight: 950; font-size: 14px; }
    .amount-in input{
      width:100%; font-size: 34px; font-weight: 950; letter-spacing: -0.6px;
      border:0; outline:none; background: transparent; color: var(--text);
    }
    input, select, button{ font: inherit; color: var(--text); }
    .row2{ display:flex; gap:10px; margin-top: 12px; }
    .row2 .field{ flex:1; margin-top:0; }
    .field input[type="date"], .field select, .field input[type="text"], .field input[type="number"], .field input[type="password"]{
      width:100%; border:0; outline:none; background: transparent; color: var(--text); font-weight: 850;
    }
    .chips{ margin-top: 12px; display:flex; flex-wrap:wrap; gap: 10px; }
    .chipbtn{
      cursor:pointer; border-radius: 999px; border: 1px solid var(--border);
      background: var(--surface); padding: 10px 12px; font-size: 13px; color: var(--text);
      display:inline-flex; align-items:center; gap:8px; user-select:none;
    }
    .chipbtn:hover{ background: var(--surface2); border-color: var(--border2); }
    .chipbtn:active{ transform: translateY(1px) scale(.99); }
    .chipbtn.on{ border-color: rgba(109,94,247,.55); background: rgba(109,94,247,.14); }
    .cta{ margin-top: 14px; display:flex; gap:10px; }
    .btn{
      flex:1; padding: 12px 14px; border-radius: 16px; border: 1px solid var(--border);
      background: var(--surface); font-weight: 950; cursor:pointer; user-select:none;
    }
    .btn:hover{ background: var(--surface2); border-color: var(--border2); }
    .btn:active{ transform: translateY(1px) scale(.99); }
    .btn.primary{ border-color: rgba(255,255,255,.12); background: linear-gradient(135deg, rgba(109,94,247,.95), rgba(73,217,161,.85)); color: rgba(255,255,255,.96); }
    .btn.ghost{ background: rgba(255,255,255,.02); color: var(--muted); }
    html[data-theme="light"] .btn.primary{ border-color: rgba(10,15,25,.08); }
    .danger-btn{
      width:100%; margin-top: 10px; padding: 10px 12px; border-radius: 16px;
      border: 1px solid rgba(255,77,109,.35); background: rgba(255,77,109,.12);
      color: rgba(255,255,255,.92); font-weight: 950; cursor:pointer; user-select:none;
    }
    html[data-theme="light"] .danger-btn{ color: rgba(10,15,25,.92); }
    .danger-btn:hover{ background: rgba(255,77,109,.16); }

    /* Category manager rows + drag handle */
    .cm-row{
      display:flex; gap:10px; align-items:center; justify-content:space-between;
      padding: 10px 12px; border: 1px solid var(--border); border-radius: 16px;
      background: var(--surface); margin-top: 10px;
    }
    .cm-left{ display:flex; gap:10px; align-items:center; min-width:0; flex:1; }
    .cm-left input{ font-weight: 900; }
    .cm-mini{ width:52px; flex:0 0 auto; }
    .cm-actions{ display:flex; gap:8px; flex:0 0 auto; }
    .drag{
      width:34px;height:38px; border-radius: 14px;
      border:1px solid var(--border);
      display:flex; align-items:center; justify-content:center;
      background: var(--surface);
      cursor: grab; user-select:none;
    }
    .drag:active{ cursor: grabbing; }
    .cm-row.dragging{ opacity:.65; outline: 1px dashed rgba(255,255,255,.22); }

    #toast{
      position:fixed; left:50%; transform:translateX(-50%);
      bottom: 130px; z-index: 120;
      padding: 10px 12px; border-radius: 14px;
      border: 1px solid var(--border); background: var(--surface); color: var(--text);
      box-shadow: var(--shadowSoft); display:none; font-size: 13px;
      max-width: min(560px, calc(100% - 32px)); user-select:none;
    }

    /* Lock screen overlay */
    .lock{
      position: fixed; inset: 0; z-index: 200;
      background: radial-gradient(900px 500px at 15% 0%, rgba(109,94,247,.22), transparent 60%),
                  radial-gradient(800px 500px at 85% 10%, rgba(73,217,161,.12), transparent 65%),
                  rgba(10,12,18,.94);
      display:none;
      padding: calc(28px + env(safe-area-inset-top)) 16px calc(24px + env(safe-area-inset-bottom));
    }
    html[data-theme="light"] .lock{
      background: radial-gradient(900px 500px at 15% 0%, rgba(109,94,247,.18), transparent 60%),
                  radial-gradient(800px 500px at 85% 10%, rgba(73,217,161,.10), transparent 65%),
                  rgba(246,247,251,.96);
    }
    .lock.show{ display:flex; align-items:center; justify-content:center; }
    .lock-card{
      width: min(520px, 100%);
      border-radius: 28px;
      border: 1px solid var(--border2);
      background: var(--surface);
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .lock-card .inner{ padding: 16px; }
    .lock-title{ font-weight: 950; letter-spacing:-.2px; font-size: 16px; }
    .lock-sub{ color: var(--muted); font-size: 12.5px; margin-top:6px; line-height:1.35; }
    .pin-dots{ display:flex; gap:10px; margin: 14px 0 6px; }
    .pin-dot{ width:12px; height:12px; border-radius:999px; border:1px solid var(--border2); background: rgba(255,255,255,.06); }
    .pin-dot.on{ background: linear-gradient(135deg, rgba(109,94,247,.95), rgba(73,217,161,.85)); border-color: rgba(255,255,255,.12); }
    .pin-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 12px; }
    .pin-btn{
      height: 52px; border-radius: 18px; border: 1px solid var(--border);
      background: var(--surface); font-weight: 950; font-size: 16px;
      cursor:pointer;
    }
    .pin-btn:hover{ background: var(--surface2); border-color: var(--border2); }
    .pin-btn:active{ transform: translateY(1px) scale(.99); }
    .pin-btn.primary{ background: linear-gradient(135deg, rgba(109,94,247,.95), rgba(73,217,161,.85)); border-color: rgba(255,255,255,.14); color: rgba(255,255,255,.96); }
    .pin-actions{ display:flex; gap:10px; margin-top: 12px; }

    :focus-visible{ outline: 2px solid rgba(109,94,247,.8); outline-offset: 2px; border-radius: 12px; }
    @media (prefers-reduced-motion: reduce){ *{ transition:none !important; scroll-behavior:auto !important; } }

    /* =========================
       LIQUID GLASS OVERRIDES
       ========================= */

    /* the container glass */
    .field{
      background: linear-gradient(180deg, var(--glassBg2), var(--glassBg));
      border: 1px solid var(--glassStroke);
      box-shadow:
        0 16px 55px rgba(0,0,0,.25),
        inset 0 1px 0 rgba(255,255,255,.08);
      backdrop-filter: blur(16px) saturate(130%);
      -webkit-backdrop-filter: blur(16px) saturate(130%);
      position: relative;
      overflow: hidden;
    }
    .field::before{
      content:"";
      position:absolute; inset:-2px;
      background:
        radial-gradient(600px 220px at 20% 0%, var(--glassHi), transparent 55%),
        radial-gradient(500px 200px at 85% 10%, rgba(109,94,247,.16), transparent 60%);
      opacity:.35;
      pointer-events:none;
    }
    .field input[type="date"],
    .field input[type="month"],
    .field input[type="text"],
    .field input[type="number"],
    .field input[type="password"]{
      width:100%;
      border:0;
      outline:0;
      background: transparent;
      color: var(--text);
      font-weight: 850;
      -webkit-appearance: none;
      appearance: none;
      position: relative;
      z-index: 1;
    }
    .field:focus-within{
      border-color: var(--glassStroke2);
      box-shadow:
        0 18px 70px rgba(0,0,0,.32),
        0 0 0 3px rgba(109,94,247,.18),
        inset 0 1px 0 rgba(255,255,255,.10);
    }
    input:-webkit-autofill,
    select:-webkit-autofill{
      -webkit-text-fill-color: var(--text);
      -webkit-box-shadow: 0 0 0px 1000px transparent inset;
      transition: background-color 9999s ease-out 0s;
    }

    /* ---- Custom Liquid Glass Select (replaces native dropdown) ---- */
    .selectbtn{
      width:100%;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding: 12px 12px;
      border-radius: 14px;
      border: 1px solid transparent; /* field handles border */
      background: transparent;       /* field handles bg */
      color: var(--text);
      font-weight: 900;
      cursor: pointer;
      user-select:none;
      position: relative;
      z-index: 1;
      text-align: left;
    }
    .selectbtn .label{
      min-width:0;
      overflow:hidden;
      white-space:nowrap;
      text-overflow:ellipsis;
    }
    .selectbtn .chev{
      width: 26px; height: 26px;
      border-radius: 12px;
      display:flex; align-items:center; justify-content:center;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.03);
      opacity:.9;
      flex:0 0 auto;
    }
    .field:focus-within .selectbtn .chev{ border-color: var(--border2); }

    .select-list{ margin-top: 12px; display:flex; flex-direction:column; gap:10px; }
    .select-item{
      display:flex; align-items:center; justify-content:space-between;
      gap:12px;
      padding: 12px 12px;
      border-radius: 16px;
      border: 1px solid var(--border);
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
      box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
      cursor:pointer; user-select:none;
      backdrop-filter: blur(14px) saturate(130%);
      -webkit-backdrop-filter: blur(14px) saturate(130%);
    }
    html[data-theme="light"] .select-item{
      background: linear-gradient(180deg, rgba(10,15,25,.04), rgba(10,15,25,.02));
    }
    .select-item:hover{ border-color: var(--border2); background: var(--surface2); }
    .select-item:active{ transform: translateY(1px) scale(.99); }
    .select-left{ display:flex; align-items:center; gap:12px; min-width:0; }
    .select-ic{
      width: 38px; height: 38px; border-radius: 14px;
      display:flex; align-items:center; justify-content:center;
      border:1px solid var(--border);
      background: radial-gradient(60px 40px at 20% 10%, rgba(255,255,255,.12), transparent 60%), var(--surface);
      flex: 0 0 auto;
    }
    .select-meta{ min-width:0; }
    .select-meta .t{ font-weight: 950; font-size: 14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .select-meta .s{ margin-top:2px; font-size: 12px; color: var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .select-check{
      width: 26px; height: 26px;
      border-radius: 12px;
      border: 1px solid var(--border);
      display:flex; align-items:center; justify-content:center;
      background: rgba(255,255,255,.02);
      color: rgba(255,255,255,.92);
      flex: 0 0 auto;
    }
    html[data-theme="light"] .select-check{ color: rgba(10,15,25,.92); }
    .select-item.on{
      border-color: rgba(109,94,247,.50);
      background: rgba(109,94,247,.12);
    }
    .select-item.on .select-check{
      border-color: rgba(109,94,247,.55);
      background: linear-gradient(135deg, rgba(109,94,247,.95), rgba(73,217,161,.85));
      color: rgba(255,255,255,.96);
    }

    /* --- Make Select sheet scrollable (many categories) --- */
    #sheetSelect .sheet-card{
      max-height: calc(100vh - 120px); /* leave space for top/bottom safe areas */
    }

    #sheetSelect .sheet-body{
      max-height: calc(100vh - 140px);
      overflow: auto;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
    }

    /* the actual list scrolls nicely */
    #sheetSelect .select-list{
      max-height: 55vh;
      overflow: auto;
      -webkit-overflow-scrolling: touch;
      padding-bottom: 6px;
    }
  </style>
</head>

<body class="bg-glow">
  <!-- LOCK SCREEN -->
  <div class="lock" id="lock">
    <div class="lock-card">
      <div class="inner">
        <div class="lock-title" id="lockTitle">Unlock</div>
        <div class="lock-sub" id="lockSub">Enter your PIN to access your data.</div>

        <div class="pin-dots" id="pinDots"></div>

        <div class="pin-grid" id="pinGrid"></div>

        <div class="pin-actions">
          <button class="btn ghost" id="pinSecondary" onclick="pinSecondary()">Set PIN</button>
          <button class="btn primary" onclick="pinBackspace()">⌫</button>
        </div>
      </div>
    </div>
  </div>

  <div class="wrap" id="appWrap" style="opacity:1;">
    <div class="topbar">
      <div class="title">
        <h1 id="pageTitle">—</h1>
        <div class="sub">
          <span id="monthLabel">—</span>
          <span style="opacity:.5">•</span>
          <span class="pill" id="monthPickerBtn" role="button" tabindex="0" onclick="openMonthPickerSheet()">This month ⌄</span>
        </div>
      </div>

      <div class="actions">
        <a class="icon-btn" href="#" aria-label="Theme" title="Theme" onclick="event.preventDefault(); toggleTheme();">🌓</a>
        <a class="icon-btn" href="?page=settings" aria-label="Settings" title="Settings">⚙️</a>
        <div class="avatar" title="Single user">F</div>
      </div>
    </div>

    <!-- DASHBOARD -->
    <div id="view-dashboard" style="display:none;">
      <div class="grid">
        <div>
          <div class="card hero">
            <div class="inner">
              <div class="card-header">
                <div class="card-title">Balance</div>
              </div>

              <div class="amount">
                <span id="balanceAmt">—</span>
                <span class="minor">net</span>
              </div>

              <div class="row">
                <span class="chip"><span class="dot success"></span> Income <strong id="incomeAmt">—</strong></span>
                <span class="chip"><span class="dot danger"></span> Expense <strong id="expenseAmt">—</strong></span>
              </div>

              <div class="quick-row">
                <div class="quick" onclick="openTxSheet('income')"><span class="k">➕</span> Add income</div>
                <div class="quick" onclick="openTxSheet('expense')"><span class="k">➖</span> Add expense</div>
                <a class="quick" href="export.php"><span class="k">⬇️</span> Export JSON</a>
                <a class="quick" href="#" onclick="event.preventDefault(); exportCsvThisMonth()"><span class="k">📄</span> Export CSV</a>
                <div class="quick" onclick="document.getElementById('importFile').click()"><span class="k">⬆️</span> Import</div>
              </div>
              <input id="importFile" type="file" accept="application/json" style="display:none" />
            </div>
          </div>

          <div class="section-title">
            <span>Recent activity</span>
            <a class="pill" href="?page=transactions">View all →</a>
          </div>

          <div class="card soft">
            <div class="list">
              <div class="list-head">
                <h3>Latest</h3>
                <span id="latestCount">—</span>
              </div>
              <div id="latestList"></div>
            </div>
          </div>
        </div>

        <div>
          <div class="section-title"><span>Quick stats</span><span></span></div>
          <div class="kpi">
            <div class="card soft mini">
              <div class="lab">This month income</div>
              <div class="val" id="kpiIncome" style="color:var(--success)">—</div>
            </div>
            <div class="card soft mini">
              <div class="lab">This month expense</div>
              <div class="val" id="kpiExpense" style="color:var(--danger)">—</div>
            </div>
          </div>

          <div class="section-title"><span>Budgets</span><a class="pill" href="?page=budgets">Manage →</a></div>
          <div class="card soft"><div class="list" id="dashBudgets"></div></div>

          <div class="section-title"><span>Top spending</span><span></span></div>
          <div class="card soft"><div class="list" id="topSpending"></div></div>
        </div>
      </div>
    </div>

    <!-- TRANSACTIONS -->
    <div id="view-transactions" style="display:none;">
      <div class="section-title">
        <span>Search & Filters</span>
        <span class="pill" onclick="openFilterSheet()">Open →</span>
      </div>

      <div class="row" style="margin-bottom:12px;">
                <span class="chip" onclick="openFilterSheet()">Filters ⌄</span>
        <a class="chip" href="#" onclick="event.preventDefault(); exportCsvFiltered()">Export CSV ⬇️</a>
      </div>

      <div class="card soft">
        <div class="list">
          <div class="list-head">
            <h3 id="txHeader">—</h3>
            <span id="txCount">—</span>
          </div>
          <div id="txList"></div>
        </div>
      </div>
    </div>

    <?php require __DIR__ . '/budget.php'; ?>

    <!-- SETTINGS -->
    <div id="view-settings" style="display:none;">
      <div class="card soft">
        <div class="list">
          <div class="list-head">
            <h3>Preferences</h3>
            <span>Local-only</span>
          </div>

          <div class="item" onclick="toggleTheme()">
            <div class="left">
              <div class="ic">🌓</div>
              <div class="meta">
                <div class="t">Appearance</div>
                <div class="s">Dark / Light</div>
              </div>
            </div>
            <div class="right">
              <div class="amt">→</div>
              <div class="date" id="themeLabel">—</div>
            </div>
          </div>

          <div class="item" onclick="openPinSheet()">
            <div class="left">
              <div class="ic">🔒</div>
              <div class="meta">
                <div class="t">PIN lock</div>
                <div class="s">Set / change / disable</div>
              </div>
            </div>
            <div class="right">
              <div class="amt">→</div>
              <div class="date" id="pinStatus">—</div>
            </div>
          </div>

          <div class="item" onclick="openCategoryManagerSheet()">
            <div class="left">
              <div class="ic">🏷️</div>
              <div class="meta">
                <div class="t">Categories</div>
                <div class="s">Reorder / edit / delete</div>
              </div>
            </div>
            <div class="right">
              <div class="amt">→</div>
              <div class="date">Manage</div>
            </div>
          </div>

          <a class="item" href="export.php">
            <div class="left">
              <div class="ic">⬇️</div>
              <div class="meta">
                <div class="t">Backup</div>
                <div class="s">Export data as JSON</div>
              </div>
            </div>
            <div class="right">
              <div class="amt">→</div>
              <div class="date">Export</div>
            </div>
          </a>

          <div class="item" onclick="document.getElementById('settingsImport').click()">
            <div class="left">
              <div class="ic">⬆️</div>
              <div class="meta">
                <div class="t">Restore</div>
                <div class="s">Import JSON (replace)</div>
              </div>
            </div>
            <div class="right">
              <div class="amt">→</div>
              <div class="date">Import</div>
            </div>
          </div>
        </div>
      </div>

      <input id="settingsImport" type="file" accept="application/json" style="display:none" />
      <div style="margin-top:14px; color:var(--muted); font-size:12.5px; line-height:1.4;">
        Tip: κράτα συχνά backup (Export JSON). Το PIN αποθηκεύεται μόνο τοπικά (localStorage).
      </div>
    </div>

    <?php require __DIR__ . '/insights.php'; ?> 
    <?php require __DIR__ . '/invest.php'; ?>
  </div>

  <div class="add-fab" onclick="openTxSheet('expense')" aria-label="Add transaction" title="Add">+</div>

  <div class="bottom-nav">
    <div class="nav">
      <a href="?page=dashboard" class="<?= $page==='dashboard'?'active':'' ?>"><span class="i">🏠</span>Home</a>
      <a href="?page=transactions" class="<?= $page==='transactions'?'active':'' ?>"><span class="i">📄</span>List</a>
      <a href="?page=insights" class="<?= $page==='insights'?'active':'' ?>"><span class="i">📊</span>Insights</a>
      <a href="?page=budgets" class="<?= $page==='budgets'?'active':'' ?>"><span class="i">🎯</span>Budgets</a>
      <a href="?page=invest" class="<?= $page==='invest'?'active':'' ?>"><span class="i">💹</span>Invest</a>
    </div>
  </div>

  <!-- Overlay -->
  <div class="overlay" id="overlay" onclick="closeAllSheets()"></div>

  <!-- Transaction Sheet -->
  <div class="sheet" id="sheetTx" aria-hidden="true">
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Transaction">
      <div class="sheet-handle"></div>
      <div class="sheet-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div style="font-weight:950; letter-spacing:-.2px;" id="txSheetTitle">New transaction</div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetTx')" aria-label="Close">✕</button>
        </div>

        <div class="seg" style="margin-top:12px;">
          <button id="segExpense" class="on" onclick="setTxType('expense')">Expense</button>
          <button id="segIncome" onclick="setTxType('income')">Income</button>
        </div>

        <div class="field" style="margin-top:12px;">
          <div class="amount-in">
            <div class="cur" id="curSymbol">€</div>
            <input id="amount" inputmode="decimal" placeholder="0.00" />
          </div>
        </div>

        <div class="row2">
          <div class="field">
            <label>Date</label>
            <input id="date" type="date" />
          </div>

          <!-- CUSTOM SELECT: Account -->
          <div class="field">
            <label>Account</label>
            <input type="hidden" id="account" value="Card" />
            <button type="button" class="selectbtn" onclick="openAccountSelect()">
              <span class="label" id="accountLabel">Card</span>
              <span class="chev">⌄</span>
            </button>
          </div>
        </div>

        <div class="field">
          <label>Note</label>
          <input id="note" type="text" placeholder="Add note…" />
        </div>

        <div class="chips" id="catChips"></div>

        <div class="cta">
          <button class="btn ghost" onclick="resetTxSheet()">Reset</button>
          <button class="btn primary" onclick="saveTransaction()">Save</button>
        </div>

        <button class="danger-btn" id="deleteBtn" style="display:none" onclick="confirmDeleteTx()">Delete transaction</button>
      </div>
    </div>
  </div>

  <!-- Budget Sheet -->
  <div class="sheet" id="sheetBudget" aria-hidden="true">
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Budget">
      <div class="sheet-handle"></div>
      <div class="sheet-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div style="font-weight:950; letter-spacing:-.2px;" id="budgetSheetTitle">New budget</div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetBudget')" aria-label="Close">✕</button>
        </div>

        <!-- CUSTOM SELECT: Budget Category -->
        <div class="field">
          <label>Category (expense)</label>
          <input type="hidden" id="budCategory" value="" />
          <button type="button" class="selectbtn" onclick="openBudgetCategorySelect()">
            <span class="label" id="budCategoryLabel">—</span>
            <span class="chev">⌄</span>
          </button>
        </div>

        <div class="field">
          <label>Limit</label>
          <div class="amount-in">
            <div class="cur" id="budCur">€</div>
            <input id="budLimit" inputmode="decimal" placeholder="0.00" />
          </div>
        </div>

        <div class="cta">
          <button class="btn ghost" onclick="closeSheet('sheetBudget')">Cancel</button>
          <button class="btn primary" onclick="saveBudget()">Save</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Month Picker Sheet -->
  <div class="sheet" id="sheetMonth" aria-hidden="true">
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Month picker">
      <div class="sheet-handle"></div>
      <div class="sheet-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div style="font-weight:950; letter-spacing:-.2px;">Select month</div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetMonth')" aria-label="Close">✕</button>
        </div>

        <div class="field">
          <label>Month</label>
          <input id="monthInput" type="month" />
        </div>

        <div class="cta">
          <button class="btn ghost" onclick="closeSheet('sheetMonth')">Cancel</button>
          <button class="btn primary" onclick="applyMonth()">Apply</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters/Search Sheet -->
  <div class="sheet" id="sheetFilters" aria-hidden="true">
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Filters">
      <div class="sheet-handle"></div>
      <div class="sheet-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div style="font-weight:950; letter-spacing:-.2px;">Search & Filters</div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetFilters')" aria-label="Close">✕</button>
        </div>

        <div class="field">
          <label>Search</label>
          <input id="filterQ" type="text" placeholder="e.g. Lidl, rent, coffee…" />
        </div>

        <div class="row2">
          <!-- CUSTOM SELECT: Type -->
          <div class="field">
            <label>Type</label>
            <input type="hidden" id="filterType" value="" />
            <button type="button" class="selectbtn" onclick="openTypeSelect()">
              <span class="label" id="filterTypeLabel">All</span>
              <span class="chev">⌄</span>
            </button>
          </div>

          <!-- CUSTOM SELECT: Category -->
          <div class="field">
            <label>Category</label>
            <input type="hidden" id="filterCategory" value="" />
            <button type="button" class="selectbtn" onclick="openCategorySelect()">
              <span class="label" id="filterCategoryLabel">All</span>
              <span class="chev">⌄</span>
            </button>
          </div>
        </div>

        <div class="row2">
          <div class="field">
            <label>From</label>
            <input id="filterFrom" type="date" />
          </div>
          <div class="field">
            <label>To</label>
            <input id="filterTo" type="date" />
          </div>
        </div>

        <div class="cta">
          <button class="btn ghost" onclick="clearFilters()">Clear</button>
          <button class="btn primary" onclick="applyFilters()">Apply</button>
        </div>

        <div style="margin-top:10px; color:var(--muted2); font-size:12px;">
          Tip: Search looks in note, account, and category name.
        </div>
      </div>
    </div>
  </div>

  <!-- Category Manager Sheet -->
  <div class="sheet" id="sheetCats" aria-hidden="true">
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Categories">
      <div class="sheet-handle"></div>
      <div class="sheet-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div style="font-weight:950; letter-spacing:-.2px;">Manage categories</div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetCats')" aria-label="Close">✕</button>
        </div>

        <div class="seg" style="margin-top:12px;">
          <button id="catTabExpense" class="on" onclick="setCatTab('expense')">Expense</button>
          <button id="catTabIncome" onclick="setCatTab('income')">Income</button>
        </div>

        <div class="field">
          <label>Add new</label>
          <div class="row2" style="margin-top:0;">
            <div class="field cm-mini" style="margin-top:0;">
              <label>Icon</label>
              <input id="newCatIcon" type="text" maxlength="4" placeholder="🏷️" />
            </div>
            <div class="field" style="margin-top:0;">
              <label>Name</label>
              <input id="newCatName" type="text" placeholder="e.g. Health" />
            </div>
          </div>
          <div class="cta">
            <button class="btn primary" onclick="addCategory()">Add</button>
            <button class="btn ghost" onclick="reloadCategories()">Refresh</button>
          </div>
        </div>

        <div style="margin-top:10px; color:var(--muted); font-size:12.5px;">
          Existing (drag to reorder)
        </div>
        <div id="catList"></div>

        <div style="margin-top:10px; color:var(--muted2); font-size:12px;">
          Tip: Delete will ask you to move transactions if needed.
        </div>
      </div>
    </div>
  </div>

  <!-- PIN management sheet (simple) -->
  <div class="sheet" id="sheetPin" aria-hidden="true">
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="PIN">
      <div class="sheet-handle"></div>
      <div class="sheet-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div style="font-weight:950; letter-spacing:-.2px;">PIN lock</div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetPin')" aria-label="Close">✕</button>
        </div>

        <div class="field">
          <label>New PIN (4 digits)</label>
          <input id="pinNew" type="password" inputmode="numeric" maxlength="4" placeholder="••••" />
        </div>

        <div class="field">
          <label>Confirm PIN</label>
          <input id="pinConfirm" type="password" inputmode="numeric" maxlength="4" placeholder="••••" />
        </div>

        <div class="cta">
          <button class="btn ghost" onclick="disablePin()">Disable</button>
          <button class="btn primary" onclick="savePin()">Save PIN</button>
        </div>

        <div style="margin-top:10px; color:var(--muted2); font-size:12px;">
          Το PIN αποθηκεύεται ως hash στο localStorage (offline).
        </div>
      </div>
    </div>
  </div>

  <!-- Generic Select Sheet (liquid glass dropdown replacement) -->
  <div class="sheet" id="sheetSelect" aria-hidden="true">
    <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Select">
      <div class="sheet-handle"></div>
      <div class="sheet-body">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div style="font-weight:950; letter-spacing:-.2px;" id="selectTitle">Select</div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetSelect')" aria-label="Close">✕</button>
        </div>

        <div class="field" style="margin-top:12px;">
          <label>Search</label>
          <input id="selectSearch" type="text" placeholder="Type to filter…" oninput="renderSelectList()" />
        </div>

        <div class="select-list" id="selectList"></div>
      </div>
    </div>
  </div>

  <div id="toast"></div>

  <script>
    const PAGE = <?= json_encode($page) ?>;
    const API = 'api.php';

    const state = {
      settings: null,
      categories: [],
      month: (function(){ const saved = localStorage.getItem('fin_month_v1'); return (saved && /^\d{4}-\d{2}-01$/.test(saved)) ? saved : monthStart(new Date()); })(),
      filters: { q:'', type:'', category_id:'', from:'', to:'' },
      txSheet: { type:'expense', category_id:null, editingId:null },
      deleteTxId: null,
      catTab: 'expense',

      // pin flow
      pin: { mode:'unlock', entered:'', setup1:'', setup2:'' },

      // generic select sheet
      select: { title:'', items:[], value:'', onPick:null }
    };

    // ---------------- Theme ----------------
    function applyTheme(theme){
      const html = document.documentElement;
      if(theme === 'light') html.setAttribute('data-theme','light');
      else html.removeAttribute('data-theme');
      localStorage.setItem('fin_theme', theme);
      const label = document.getElementById('themeLabel');
      if(label) label.textContent = theme === 'light' ? 'Light' : 'Dark';
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

    // ---------------- Utils ----------------
    function monthStart(d){
      const y = d.getFullYear();
      const m = String(d.getMonth()+1).padStart(2,'0');
      return `${y}-${m}-01`;
    }
    function monthEnd(month){
      const d = new Date(month + "T00:00:00");
      const y = d.getFullYear();
      const m = d.getMonth();
      const last = new Date(y, m+1, 0);
      const mm = String(last.getMonth()+1).padStart(2,'0');
      const dd = String(last.getDate()).padStart(2,'0');
      return `${y}-${mm}-${dd}`;
    }
    function monthLabel(month){
      const d = new Date(month + "T00:00:00");
      return d.toLocaleDateString('en-US', { month:'long', year:'numeric' });
    }
    function symbol(){ return '€'; }
    function fmtMoneyFromCents(cents){
      const sign = cents < 0 ? '-' : '';
      const abs = Math.abs(cents);
      const v = (abs / 100).toFixed(2);
      return sign + symbol() + v.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    function escapeHtml(s){
      return (s ?? '').toString()
        .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
        .replaceAll('"','&quot;').replaceAll("'","&#039;");
    }
    async function apiGet(url){
      const r = await fetch(url);
      const j = await r.json();
      if(!r.ok || j.error) throw new Error(j.error || 'Request failed');
      return j;
    }
    async function apiSend(url, method, body){
      const r = await fetch(url, {
        method,
        headers: {'Content-Type':'application/json'},
        body: body ? JSON.stringify(body) : null
      });
      const j = await r.json();
      if(!r.ok || j.error) throw new Error(j.error || 'Request failed');
      return j;
    }

    // ---------------- Toast ----------------
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

    // ---------------- Sheets helpers ----------------
    function showOverlay(){ document.getElementById('overlay').classList.add('show'); }
    function hideOverlayIfNone(){
      const anyOpen = ['sheetTx','sheetBudget','sheetMonth','sheetFilters','sheetCats','sheetPin','sheetSelect']
        .some(id => document.getElementById(id).classList.contains('show'));
      if(!anyOpen) document.getElementById('overlay').classList.remove('show');
    }
    function openSheet(id){
      showOverlay();
      const el = document.getElementById(id);
      el.classList.add('show'); el.setAttribute('aria-hidden','false');
    }
    function closeSheet(id){
      const el = document.getElementById(id);
      el.classList.remove('show'); el.setAttribute('aria-hidden','true');
      hideOverlayIfNone();
    }
    function closeAllSheets(){
      ['sheetTx','sheetBudget','sheetMonth','sheetFilters','sheetCats','sheetPin','sheetSelect'].forEach(closeSheet);
      document.getElementById('overlay').classList.remove('show');
    }
    window.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeAllSheets(); });

    // ---------------- Views ----------------
    function setPageTitle(){
      const map = { dashboard:'Dashboard', transactions:'Transactions', budgets:'Budgets', insights:'Insights',  invest:'Invest', settings:'Settings' }; // CHANGED
      document.getElementById('pageTitle').textContent = map[PAGE] || 'Dashboard';
      document.getElementById('monthLabel').textContent = monthLabel(state.month);
      const mp = document.getElementById('monthPickerBtn');
      if(mp) mp.style.display = (PAGE === 'dashboard') ? 'inline-flex' : 'none';
    }
    function showView(){
      ['dashboard','transactions','budgets','insights','invest','settings'].forEach(v=>{ // CHANGED
        const el = document.getElementById('view-' + v);
        if(el) el.style.display = (PAGE === v ? 'block' : 'none');
      });
    }

    // ---------------- Generic Select Sheet (Liquid Glass) ----------------
    function openSelectSheet({ title, items, value, onPick }){
      state.select.title = title || 'Select';
      state.select.items = items || [];
      state.select.value = value ?? '';
      state.select.onPick = onPick || null;

      document.getElementById('selectTitle').textContent = state.select.title;
      document.getElementById('selectSearch').value = '';
      renderSelectList();
      openSheet('sheetSelect');
      setTimeout(()=> document.getElementById('selectSearch').focus(), 120);
    }

    function renderSelectList(){
      const q = (document.getElementById('selectSearch').value || '').trim().toLowerCase();
      const list = document.getElementById('selectList');

      const filtered = state.select.items.filter(it=>{
        const hay = (it.label + ' ' + (it.sub||'')).toLowerCase();
        return !q || hay.includes(q);
      });

      list.innerHTML = filtered.map(it=>{
        const on = (String(it.value) === String(state.select.value)) ? 'on' : '';
        const safe = String(it.value).replaceAll("\\","\\\\").replaceAll("'","\\'");
        return `
          <div class="select-item ${on}" onclick="pickSelectValue('${safe}')">
            <div class="select-left">
              <div class="select-ic">${escapeHtml(it.icon || '🏷️')}</div>
              <div class="select-meta">
                <div class="t">${escapeHtml(it.label)}</div>
                <div class="s">${escapeHtml(it.sub || '')}</div>
              </div>
            </div>
            <div class="select-check">${on ? '✓' : ''}</div>
          </div>
        `;
      }).join('') || `<div style="color:var(--muted); font-size:12.5px; padding:10px 2px;">No results</div>`;
    }

    function pickSelectValue(val){
      state.select.value = val;
      if(typeof state.select.onPick === 'function'){
        state.select.onPick(val);
      }
      closeSheet('sheetSelect');
    }

    // ---------------- Month picker ----------------
    function openMonthPickerSheet(){
      const mi = document.getElementById('monthInput');
      mi.value = state.month.slice(0,7);
      openSheet('sheetMonth');
    }
    function applyMonth(){
      const val = document.getElementById('monthInput').value;
      if(!val) return toast('Pick a month');

      state.month = val + '-01';
      localStorage.setItem('fin_month_v1', state.month);

      // CHANGED: always sync selected month to the active date range used by other pages
      state.filters.from = state.month;
      state.filters.to = monthEnd(state.month);

      setPageTitle();
      closeSheet('sheetMonth');
      refresh();
    }

    // ---------------- Filters (custom selects) ----------------
    function setFilterTypeLabel(){
      const v = document.getElementById('filterType').value || '';
      const lab = document.getElementById('filterTypeLabel');
      if(!lab) return;
      if(!v) lab.textContent = 'All';
      else if(v === 'expense') lab.textContent = 'Expense';
      else if(v === 'income') lab.textContent = 'Income';
      else lab.textContent = 'All';
    }

    function setFilterCategoryLabel(){
      const id = document.getElementById('filterCategory').value || '';
      const lab = document.getElementById('filterCategoryLabel');
      if(!lab) return;
      if(!id){ lab.textContent = 'All'; return; }
      const c = state.categories.find(x => String(x.id) === String(id));
      lab.textContent = c ? `${c.icon || '🏷️'} ${c.name}` : 'All';
    }

    function openTypeSelect(){
      const items = [
        { value:'', label:'All', sub:'No type filter', icon:'✨' },
        { value:'expense', label:'Expense', sub:'Only expenses', icon:'➖' },
        { value:'income', label:'Income', sub:'Only income', icon:'➕' }
      ];
      openSelectSheet({
        title: 'Type',
        items,
        value: document.getElementById('filterType').value || '',
        onPick: (val)=>{
          document.getElementById('filterType').value = val;
          setFilterTypeLabel();
        }
      });
    }

    function openCategorySelect(){
      const items = [{ value:'', label:'All', sub:'No category filter', icon:'✨' }].concat(
        state.categories
          .slice()
          .sort((a,b)=>{
            const ta = String(a.type), tb = String(b.type);
            if(ta !== tb) return ta.localeCompare(tb);
            return (a.sort_order??0) - (b.sort_order??0);
          })
          .map(c=> ({
            value: String(c.id),
            label: `${c.name}`,
            sub: `${c.type} • ${c.name}`,
            icon: c.icon || '🏷️'
          }))
      );

      openSelectSheet({
        title: 'Category',
        items,
        value: document.getElementById('filterCategory').value || '',
        onPick: (val)=>{
          document.getElementById('filterCategory').value = val;
          setFilterCategoryLabel();
        }
      });
    }

    function openFilterSheet(){
      document.getElementById('filterQ').value = state.filters.q;

      document.getElementById('filterType').value = state.filters.type || '';
      setFilterTypeLabel();

      document.getElementById('filterCategory').value = state.filters.category_id || '';
      setFilterCategoryLabel();

      document.getElementById('filterFrom').value = state.filters.from || state.month;
      document.getElementById('filterTo').value = state.filters.to || monthEnd(state.month);

      openSheet('sheetFilters');
      setTimeout(()=> document.getElementById('filterQ').focus(), 120);
    }

    function clearFilters(){
      state.filters = { q:'', type:'', category_id:'', from:'', to:'' };
      document.getElementById('filterQ').value = '';

      document.getElementById('filterType').value = '';
      setFilterTypeLabel();

      document.getElementById('filterCategory').value = '';
      setFilterCategoryLabel();

      document.getElementById('filterFrom').value = state.month;
      document.getElementById('filterTo').value = monthEnd(state.month);

      toast('Cleared');
    }

    function applyFilters(){
      state.filters.q = document.getElementById('filterQ').value.trim();
      state.filters.type = document.getElementById('filterType').value;
      state.filters.category_id = document.getElementById('filterCategory').value;
      state.filters.from = document.getElementById('filterFrom').value;
      state.filters.to = document.getElementById('filterTo').value;

      closeSheet('sheetFilters');
      refreshTransactions();
      toast('Applied');
    }

    // ---------------- Transactions sheet ----------------
    function setAccountLabel(){
      const v = document.getElementById('account').value || 'Card';
      const lab = document.getElementById('accountLabel');
      if(lab) lab.textContent = v;
    }

    function openAccountSelect(){
      const items = [
        { value:'Card', label:'Card', sub:'Card payments', icon:'💳' },
        { value:'Cash', label:'Cash', sub:'Cash expenses', icon:'💶' },
        { value:'Bank', label:'Bank', sub:'Bank transfer', icon:'🏦' }
      ];
      openSelectSheet({
        title: 'Account',
        items,
        value: document.getElementById('account').value || 'Card',
        onPick: (val)=>{
          document.getElementById('account').value = val || 'Card';
          setAccountLabel();
        }
      });
    }

    function openTxSheet(type){
      state.txSheet.type = type || 'expense';
      state.txSheet.editingId = null;
      state.txSheet.category_id = null;
      state.deleteTxId = null;
      document.getElementById('txSheetTitle').textContent = 'New transaction';
      document.getElementById('deleteBtn').style.display = 'none';
      setTxType(state.txSheet.type);
      renderCatChips();
      document.getElementById('curSymbol').textContent = symbol();
      document.getElementById('date').value = new Date().toISOString().slice(0,10);
      document.getElementById('amount').value = '';
      document.getElementById('note').value = '';

      document.getElementById('account').value = 'Card';
      setAccountLabel();

      openSheet('sheetTx');
      setTimeout(()=> document.getElementById('amount').focus(), 120);
    }
    function editTx(tx){
      state.txSheet.type = tx.type;
      state.txSheet.editingId = tx.id;
      state.txSheet.category_id = tx.category_id;
      state.deleteTxId = tx.id;
      document.getElementById('txSheetTitle').textContent = 'Edit transaction';
      document.getElementById('deleteBtn').style.display = 'block';
      setTxType(tx.type);
      renderCatChips();
      document.getElementById('curSymbol').textContent = symbol();
      document.getElementById('date').value = tx.date;
      document.getElementById('amount').value = (tx.amount_cents/100).toFixed(2);
      document.getElementById('note').value = tx.note || '';

      document.getElementById('account').value = tx.account || 'Card';
      setAccountLabel();

      openSheet('sheetTx');
      setTimeout(()=> document.getElementById('amount').focus(), 120);
    }
    function setTxType(t){
      state.txSheet.type = t;
      document.getElementById('segExpense').classList.toggle('on', t==='expense');
      document.getElementById('segIncome').classList.toggle('on', t==='income');
      renderCatChips();
    }
    function renderCatChips(){
      const wrap = document.getElementById('catChips');
      const cats = state.categories.filter(c=>c.type === state.txSheet.type).sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));
      wrap.innerHTML = cats.map(c=>{
        const on = (state.txSheet.category_id === c.id) ? 'on' : '';
        return `<div class="chipbtn ${on}" data-id="${c.id}" onclick="pickCat(${c.id})">${escapeHtml(c.icon||'🏷️')} ${escapeHtml(c.name)}</div>`;
      }).join('');
      if(!state.txSheet.category_id && cats.length){
        state.txSheet.category_id = cats[0].id;
        wrap.querySelectorAll('.chipbtn')[0]?.classList.add('on');
      }
    }
    function pickCat(id){
      state.txSheet.category_id = id;
      document.querySelectorAll('#catChips .chipbtn').forEach(el=>{
        el.classList.toggle('on', parseInt(el.getAttribute('data-id'),10) === id);
      });
    }
    function resetTxSheet(){
      if(state.txSheet.editingId){ toast('Reset disabled in edit mode'); return; }
      document.getElementById('amount').value = '';
      document.getElementById('note').value = '';
      document.getElementById('account').value = 'Card';
      setAccountLabel();
      document.getElementById('date').value = new Date().toISOString().slice(0,10);
      renderCatChips();
      toast('Reset');
    }
    async function saveTransaction(){
      try{
        const amount = document.getElementById('amount').value.trim();
        const date = document.getElementById('date').value;
        const account = document.getElementById('account').value || 'Card';
        const note = document.getElementById('note').value.trim();
        if(!amount) return toast('Βάλε ποσό');
        if(!state.txSheet.category_id) return toast('Διάλεξε κατηγορία');
        const payload = { type: state.txSheet.type, amount, date, category_id: state.txSheet.category_id, account, note };
        if(state.txSheet.editingId){
          await apiSend(`${API}?action=transaction&id=${state.txSheet.editingId}&_method=PUT`, 'POST', payload);
          toast('Updated');
        } else {
          await apiSend(`${API}?action=transactions`, 'POST', payload);
          toast('Saved');
        }
        closeSheet('sheetTx');
        await refresh();
      }catch(e){
        toast('Error: ' + e.message);
      }
    }
    function confirmDeleteTx(){
      if(!state.deleteTxId) return;
      if(!confirm('Delete this transaction?')) return;
      deleteTx(state.deleteTxId);
    }
    async function deleteTx(id){
      try{
        await apiSend(`${API}?action=transaction&id=${id}`, 'DELETE');
        toast('Deleted');
        closeSheet('sheetTx');
        await refresh();
      }catch(e){
        toast('Error: ' + e.message);
      }
    }

    // ---------------- Budgets (custom select) ----------------
    function setBudgetCategoryLabel(){
      const id = document.getElementById('budCategory').value || '';
      const lab = document.getElementById('budCategoryLabel');
      if(!lab) return;
      if(!id){ lab.textContent = '—'; return; }
      const c = state.categories.find(x => String(x.id) === String(id));
      lab.textContent = c ? `${c.icon || '🏷️'} ${c.name}` : '—';
    }

    function openBudgetCategorySelect(){
      const expenseCats = state.categories
        .filter(c=>c.type === 'expense')
        .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));

      const items = expenseCats.map(c=> ({
        value: String(c.id),
        label: c.name,
        sub: 'expense',
        icon: c.icon || '🏷️'
      }));

      openSelectSheet({
        title: 'Budget category',
        items,
        value: document.getElementById('budCategory').value || '',
        onPick: (val)=>{
          document.getElementById('budCategory').value = val;
          setBudgetCategoryLabel();
        }
      });
    }

    function openBudgetSheet(){
      const expenseCats = state.categories
        .filter(c=>c.type === 'expense')
        .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));

      if(!expenseCats.length){
        toast('No expense categories');
        return;
      }

      const tt = document.getElementById('budgetSheetTitle');
      if(tt) tt.textContent = 'New budget';
      document.getElementById('budCur').textContent = symbol();
      document.getElementById('budLimit').value = '';

      // default to first
      document.getElementById('budCategory').value = String(expenseCats[0].id);
      setBudgetCategoryLabel();

      openSheet('sheetBudget');
      setTimeout(()=> document.getElementById('budLimit').focus(), 120);
    }

    // Used by budget.php to open an existing budget for editing
    function openBudgetEdit(categoryId, limitCents){
      const cid = String(categoryId || '');
      if(!cid) return;
      const tt = document.getElementById('budgetSheetTitle');
      if(tt) tt.textContent = 'Edit budget';
      document.getElementById('budCur').textContent = symbol();
      document.getElementById('budCategory').value = cid;
      setBudgetCategoryLabel();
      const cents = parseInt(limitCents || 0, 10) || 0;
      document.getElementById('budLimit').value = (cents/100).toFixed(2);
      openSheet('sheetBudget');
      setTimeout(()=> document.getElementById('budLimit').focus(), 120);
    }

    async function saveBudget(){
      try{
        const cid = document.getElementById('budCategory').value;
        const category_id = parseInt(cid || '0', 10);
        const limit = document.getElementById('budLimit').value.trim();
        if(!category_id) return toast('Pick category');
        if(!limit) return toast('Βάλε όριο');
        await apiSend(`${API}?action=budgets`, 'POST', { month: state.month, category_id, limit });
        toast('Budget saved');
        closeSheet('sheetBudget');
        await refresh();
      }catch(e){
        toast('Error: ' + e.message);
      }
    }

    // ---------------- Category manager ----------------
    function openCategoryManagerSheet(){
      setCatTab(state.catTab);
      openSheet('sheetCats');
      renderCategoryManager();
    }
    function setCatTab(type){
      state.catTab = type;
      document.getElementById('catTabExpense').classList.toggle('on', type==='expense');
      document.getElementById('catTabIncome').classList.toggle('on', type==='income');
      renderCategoryManager();
    }
    async function reloadCategories(){
      const c = await apiGet(`${API}?action=categories`);
      state.categories = c.items;
      renderCategoryManager();
      toast('Refreshed');
      await refresh();
    }
    function renderCategoryManager(){
      const list = document.getElementById('catList');
      if(!list) return;
      const cats = state.categories.filter(c=>c.type === state.catTab).sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));
      if(!cats.length){
        list.innerHTML = `<div class="item" style="cursor:default"><div class="left"><div class="ic">🏷️</div><div class="meta"><div class="t">No categories</div><div class="s">Add one above</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;
        return;
      }
      list.innerHTML = cats.map(c=>{
        return `
          <div class="cm-row" draggable="true" data-id="${c.id}">
            <div class="cm-left">
              <div class="drag" title="Drag">⋮⋮</div>
              <input class="cm-mini" type="text" value="${escapeHtml(c.icon||'🏷️')}" maxlength="4" />
              <input type="text" value="${escapeHtml(c.name)}" style="min-width:0; width:100%;" />
            </div>
            <div class="cm-actions">
              <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" title="Save" onclick="saveCatRow(${c.id});">💾</button>
              <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" title="Delete" onclick="deleteCatFlow(${c.id});">🗑️</button>
            </div>
          </div>
        `;
      }).join('');

      enableDragReorder(list);
    }

    function enableDragReorder(container){
      let dragging = null;
      container.querySelectorAll('.cm-row').forEach(row=>{
        row.addEventListener('dragstart', (e)=>{
          dragging = row;
          row.classList.add('dragging');
          e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragend', ()=>{
          dragging?.classList.remove('dragging');
          dragging = null;
          saveCategoryOrder();
        });
        row.addEventListener('dragover', (e)=>{
          e.preventDefault();
          const target = row;
          if(!dragging || dragging === target) return;
          const rect = target.getBoundingClientRect();
          const after = (e.clientY - rect.top) > rect.height / 2;
          container.insertBefore(dragging, after ? target.nextSibling : target);
        });
      });
    }

    async function saveCategoryOrder(){
      try{
        const ids = [...document.querySelectorAll('#catList .cm-row')].map(r=> parseInt(r.getAttribute('data-id'),10));
        await apiSend(`${API}?action=categories_reorder`, 'POST', { type: state.catTab, ids });
        await reloadCategories();
        toast('Order saved');
      }catch(e){
        toast('Reorder error: ' + e.message);
      }
    }

    async function addCategory(){
      try{
        const icon = (document.getElementById('newCatIcon').value || '🏷️').trim();
        const name = (document.getElementById('newCatName').value || '').trim();
        if(!name) return toast('Give a name');
        await apiSend(`${API}?action=categories`, 'POST', { type: state.catTab, name, icon, sort_order: 0 });
        document.getElementById('newCatName').value = '';
        document.getElementById('newCatIcon').value = '';
        await reloadCategories();
        toast('Added');
      }catch(e){
        toast('Error: ' + e.message);
      }
    }
    async function saveCatRow(id){
      try{
        const row = document.querySelector(`.cm-row[data-id="${id}"]`);
        const inputs = row.querySelectorAll('input');
        const icon = inputs[0].value.trim() || '🏷️';
        const name = inputs[1].value.trim();
        if(!name) return toast('Name required');

        await apiSend(`${API}?action=category&id=${id}&_method=PUT`, 'POST', { name, icon });
        toast('Saved');
        await reloadCategories();
      }catch(e){
        toast('Error: ' + e.message);
      }
    }

    // Delete category flow: ask move if needed
    async function deleteCatFlow(id){
      const cat = state.categories.find(c=>c.id===id);
      if(!cat) return;

      if(!confirm(`Delete category “${cat.name}”?`)) return;

      try{
        await apiSend(`${API}?action=category_delete`, 'POST', { id });
        toast('Deleted');
        await reloadCategories();
        return;
      }catch(e){
        const sameType = state.categories
          .filter(c=>c.type===cat.type && c.id!==cat.id)
          .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));

        if(!sameType.length) return toast('No target category to move into.');

        const targetName = prompt(
          `Category is in use.\nType the NAME of a target category to move transactions into:\n\n` +
          sameType.slice(0,12).map(c=>`- ${c.name}`).join('\n')
        );
        if(!targetName) return;

        const target = sameType.find(c=> c.name.toLowerCase() === targetName.trim().toLowerCase());
        if(!target) return toast('Target not found (check spelling).');

        try{
          await apiSend(`${API}?action=category_delete`, 'POST', { id, move_to: target.id });
          toast('Moved & deleted');
          await reloadCategories();
          await refresh();
        }catch(e2){
          toast('Delete error: ' + e2.message);
        }
      }
    }

    // ---------------- CSV export helpers ----------------
    function exportCsvThisMonth(){
      const from = state.month;
      const to = monthEnd(state.month);
      window.location.href = `export_csv.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
    }
    function exportCsvFiltered(){
      const from = state.filters.from || state.month;
      const to = state.filters.to || monthEnd(state.month);
      window.location.href = `export_csv.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
    }

    // ---------------- PIN (local only) ----------------
    const PIN_KEY = 'fin_pin_hash_v1';
    const UNLOCK_KEY = 'fin_pin_unlocked_v1';

    function hasPin(){ return !!localStorage.getItem(PIN_KEY); }

    async function sha256(text){
      const enc = new TextEncoder().encode(text);
      const buf = await crypto.subtle.digest('SHA-256', enc);
      return [...new Uint8Array(buf)].map(b=>b.toString(16).padStart(2,'0')).join('');
    }

    function lockShow(){
      document.getElementById('lock').classList.add('show');
      document.getElementById('appWrap').style.filter = 'blur(10px)';
      document.getElementById('appWrap').style.pointerEvents = 'none';
    }
    function lockHide(){
      document.getElementById('lock').classList.remove('show');
      document.getElementById('appWrap').style.filter = '';
      document.getElementById('appWrap').style.pointerEvents = '';
    }

    function renderPinUI(){
      const dots = document.getElementById('pinDots');
      dots.innerHTML = '';
      const len = state.pin.entered.length;
      for(let i=0;i<4;i++){
        const d = document.createElement('div');
        d.className = 'pin-dot' + (i < len ? ' on' : '');
        dots.appendChild(d);
      }

      const grid = document.getElementById('pinGrid');
      const keys = ['1','2','3','4','5','6','7','8','9','', '0','OK'];
      grid.innerHTML = keys.map(k=>{
        if(k==='') return `<button class="pin-btn" disabled style="opacity:.35"> </button>`;
        if(k==='OK') return `<button class="pin-btn primary" onclick="pinSubmit()">OK</button>`;
        return `<button class="pin-btn" onclick="pinPress('${k}')">${k}</button>`;
      }).join('');

      const title = document.getElementById('lockTitle');
      const sub = document.getElementById('lockSub');
      const sec = document.getElementById('pinSecondary');

      if(!hasPin()){
        state.pin.mode = 'setup1';
        title.textContent = 'Set PIN';
        sub.textContent = 'Choose a 4-digit PIN (offline).';
        sec.textContent = 'Skip';
      } else {
        title.textContent = 'Unlock';
        sub.textContent = 'Enter your PIN to access your data.';
        sec.textContent = 'Set PIN';
      }
    }

    function pinPress(d){
      if(state.pin.entered.length >= 4) return;
      state.pin.entered += d;
      renderPinUI();
    }
    function pinBackspace(){
      state.pin.entered = state.pin.entered.slice(0,-1);
      renderPinUI();
    }
    function pinSecondary(){
      if(!hasPin()){
        localStorage.setItem(UNLOCK_KEY, '1');
        lockHide();
        toast('PIN skipped');
      } else {
        openPinSheet();
      }
    }

    async function pinSubmit(){
      if(state.pin.entered.length !== 4) return toast('4 digits');
      if(!hasPin()){
        state.pin.setup1 = state.pin.entered;
        state.pin.entered = '';
        state.pin.mode = 'setup2';
        document.getElementById('lockTitle').textContent = 'Confirm PIN';
        document.getElementById('lockSub').textContent = 'Enter the same PIN again.';
        renderPinUI();
        return;
      }

      const h = await sha256(state.pin.entered);
      const stored = localStorage.getItem(PIN_KEY);
      if(h === stored){
        localStorage.setItem(UNLOCK_KEY, '1');
        state.pin.entered = '';
        lockHide();
        toast('Unlocked');
      } else {
        state.pin.entered = '';
        renderPinUI();
        toast('Wrong PIN');
      }
    }

    async function handleSetupConfirm(){
      const pin2 = state.pin.entered;
      if(pin2.length !== 4) return toast('4 digits');
      if(pin2 !== state.pin.setup1){
        state.pin.entered = '';
        state.pin.setup1 = '';
        state.pin.mode = 'setup1';
        renderPinUI();
        return toast('PIN mismatch');
      }
      const h = await sha256(pin2);
      localStorage.setItem(PIN_KEY, h);
      localStorage.setItem(UNLOCK_KEY, '1');
      state.pin.entered = '';
      state.pin.setup1 = '';
      lockHide();
      refreshSettings();
      toast('PIN set');
    }

    const _pinSubmit = pinSubmit;
    pinSubmit = async function(){
      if(!hasPin() && state.pin.mode === 'setup2'){
        return handleSetupConfirm();
      }
      return _pinSubmit();
    }

    function openPinSheet(){
      document.getElementById('pinNew').value = '';
      document.getElementById('pinConfirm').value = '';
      openSheet('sheetPin');
    }

    async function savePin(){
      const a = document.getElementById('pinNew').value.trim();
      const b = document.getElementById('pinConfirm').value.trim();
      if(!/^\d{4}$/.test(a)) return toast('PIN must be 4 digits');
      if(a !== b) return toast('PIN mismatch');
      const h = await sha256(a);
      localStorage.setItem(PIN_KEY, h);
      localStorage.setItem(UNLOCK_KEY, '1');
      closeSheet('sheetPin');
      refreshSettings();
      toast('PIN saved');
    }

    function disablePin(){
      if(!confirm('Disable PIN lock?')) return;
      localStorage.removeItem(PIN_KEY);
      localStorage.setItem(UNLOCK_KEY, '1');
      closeSheet('sheetPin');
      refreshSettings();
      toast('PIN disabled');
    }

    function refreshSettings(){
      const current = localStorage.getItem('fin_theme') || 'dark';
      document.getElementById('themeLabel').textContent = current === 'light' ? 'Light' : 'Dark';
      const p = document.getElementById('pinStatus');
      if(p) p.textContent = hasPin() ? 'Enabled' : 'Off';
    }

    // ---------------- Render helpers ----------------
    function txRowHTML(t){
      const amount = fmtMoneyFromCents(t.amount_cents);
      const cls = t.type === 'income' ? 'income' : 'expense';
      const sub = (t.account || '') + (t.note ? ' • ' + t.note : '');
      return `
        <div class="item" data-tx="${escapeHtml(JSON.stringify(t))}">
          <div class="left">
            <div class="ic">${escapeHtml(t.category_icon || '🏷️')}</div>
            <div class="meta">
              <div class="t">${escapeHtml(t.category_name || '—')}</div>
              <div class="s">${escapeHtml(sub)}</div>
            </div>
          </div>
          <div class="right">
            <div class="amt ${cls}">${escapeHtml(amount)}</div>
            <div class="date">${escapeHtml(t.date)}</div>
          </div>
        </div>
      `;
    }
    function bindTxClicks(containerId){
      const c = document.getElementById(containerId);
      if(!c) return;
      c.querySelectorAll('.item[data-tx]').forEach(el=>{
        el.onclick = () => editTx(JSON.parse(el.getAttribute('data-tx')));
      });
    }

    // ---------------- Data loading ----------------
    async function bootstrap(){
      const b = await apiGet(`${API}?action=bootstrap`);
      state.settings = b.settings;
      state.categories = b.categories;
    }

    async function refresh(){
      setPageTitle();
      showView();
      if(PAGE === 'dashboard') await refreshDashboard();
      if(PAGE === 'transactions') await refreshTransactions();
      if(PAGE === 'budgets') await refreshBudgets();
      if(PAGE === 'insights') await refreshInsights(); // CHANGED: call insights refresh
      if(PAGE === 'settings') refreshSettings();
      if(PAGE === 'invest') await refreshInvest();
    }

    async function refreshDashboard(){
      const stats = await apiGet(`${API}?action=stats_month&month=${encodeURIComponent(state.month)}`);
      document.getElementById('balanceAmt').textContent = fmtMoneyFromCents(stats.balance_cents);
      document.getElementById('incomeAmt').textContent  = fmtMoneyFromCents(stats.income_cents);
      document.getElementById('expenseAmt').textContent = fmtMoneyFromCents(stats.expense_cents);
      document.getElementById('kpiIncome').textContent  = fmtMoneyFromCents(stats.income_cents);
      document.getElementById('kpiExpense').textContent = fmtMoneyFromCents(stats.expense_cents);

      const from = state.month;
      const to = monthEnd(state.month);
      const tx = await apiGet(`${API}?action=transactions&from=${from}&to=${to}`);
      const latest = tx.items.slice(0, 5);
      document.getElementById('latestCount').textContent = `${tx.items.length} items`;

      const latestList = document.getElementById('latestList');
      latestList.innerHTML = latest.length
        ? latest.map(t=> txRowHTML(t)).join('')
        : `<div class="item" style="cursor:default"><div class="left"><div class="ic">🧾</div><div class="meta"><div class="t">No transactions yet</div><div class="s">Add your first income/expense</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;
      bindTxClicks('latestList');

      const top = stats.top_expenses || [];
      const topWrap = document.getElementById('topSpending');
      topWrap.innerHTML = top.map(r=>{
        const cents = parseInt(r.sum_cents, 10) || 0;
        const amt = fmtMoneyFromCents(cents);
        return `
          <div class="item" style="cursor:default">
            <div class="left">
              <div class="ic">${escapeHtml(r.icon || '🏷️')}</div>
              <div class="meta">
                <div class="t">${escapeHtml(r.name || '—')}</div>
                <div class="s">This month</div>
              </div>
            </div>
            <div class="right">
              <div class="amt expense">${escapeHtml(amt)}</div>
              <div class="date">Expense</div>
            </div>
          </div>
        `;
      }).join('') || `<div class="item" style="cursor:default"><div class="left"><div class="ic">📊</div><div class="meta"><div class="t">No spending data</div><div class="s">Add some expenses</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;

      const budgets = await apiGet(`${API}?action=budgets&month=${encodeURIComponent(state.month)}`);
      const dash = document.getElementById('dashBudgets');
      if(!budgets.items.length){
        dash.innerHTML = `<div class="item" style="cursor:default"><div class="left"><div class="ic">🎯</div><div class="meta"><div class="t">No budgets</div><div class="s">Add a monthly budget</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;
      } else {
        const spentByCat = {};
        tx.items.filter(t=>t.type==='expense').forEach(t=>{
          spentByCat[t.category_id] = (spentByCat[t.category_id] || 0) + (parseInt(t.amount_cents,10)||0);
        });
        dash.innerHTML = budgets.items.slice(0,4).map(b=>{
          const spent = spentByCat[b.category_id] || 0;
          const limit = parseInt(b.limit_cents,10)||0;
          const pct = limit>0 ? Math.min(1, spent/limit) : 0;
          return `
            <div class="budget" style="cursor:default">
              <div class="top">
                <div class="name"><span style="font-size:18px">${escapeHtml(b.category_icon || '🏷️')}</span> ${escapeHtml(b.category_name)}</div>
                <div style="color:var(--muted); font-weight:950; font-variant-numeric:tabular-nums;">
                  ${escapeHtml(fmtMoneyFromCents(spent))} / ${escapeHtml(fmtMoneyFromCents(limit))}
                </div>
              </div>
              <div class="bar"><div style="width:${Math.round(pct*100)}%"></div></div>
              <div class="foot">
                <span>${Math.round(pct*100)}% used</span>
                <span>${escapeHtml(fmtMoneyFromCents(Math.max(0, limit-spent)))} left</span>
              </div>
            </div>
          `;
        }).join('');
      }
    }

    async function refreshTransactions(){
      const header = document.getElementById('txHeader');
      const count = document.getElementById('txCount');
      const list = document.getElementById('txList');
      header.textContent = monthLabel(state.month);

      const from = state.filters.from || state.month;
      const to = state.filters.to || monthEnd(state.month);
      const params = new URLSearchParams({ action: 'transactions', from, to });
      if(state.filters.type) params.set('type', state.filters.type);
      if(state.filters.category_id) params.set('category_id', state.filters.category_id);
      if(state.filters.q) params.set('q', state.filters.q);

      const tx = await apiGet(`${API}?` + params.toString());
      count.textContent = `${tx.items.length} items`;

      list.innerHTML = tx.items.length
        ? tx.items.map(t=> txRowHTML(t)).join('')
        : `<div class="item" style="cursor:default"><div class="left"><div class="ic">🧾</div><div class="meta"><div class="t">No results</div><div class="s">Change search/filters</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;

      bindTxClicks('txList');
    }

    async function refreshBudgets(){
      // budget.php overrides budgets rendering (thresholds, suggestions, groups, pacing)
      if (typeof window.__bud_refreshBudgets === 'function') {
        return window.__bud_refreshBudgets();
      }
      document.getElementById('budMonth').textContent = monthLabel(state.month);
      const budgets = await apiGet(`${API}?action=budgets&month=${encodeURIComponent(state.month)}`);
      const from = state.month;
      const to = monthEnd(state.month);
      const tx = await apiGet(`${API}?action=transactions&from=${from}&to=${to}&type=expense`);

      const spentByCat = {};
      tx.items.forEach(t=>{
        spentByCat[t.category_id] = (spentByCat[t.category_id] || 0) + (parseInt(t.amount_cents,10)||0);
      });

      const list = document.getElementById('budList');
      if(!budgets.items.length){
        list.innerHTML = `<div class="item" style="cursor:default"><div class="left"><div class="ic">🎯</div><div class="meta"><div class="t">No budgets yet</div><div class="s">Tap “Add budget”</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;
        return;
      }

      list.innerHTML = budgets.items.map(b=>{
        const spent = spentByCat[b.category_id] || 0;
        const limit = parseInt(b.limit_cents,10)||0;
        const pct = limit>0 ? Math.min(1, spent/limit) : 0;
        return `
          <div class="budget" style="cursor:default">
            <div class="top">
              <div class="name"><span style="font-size:18px">${escapeHtml(b.category_icon || '🏷️')}</span> ${escapeHtml(b.category_name)}</div>
              <div style="color:var(--muted); font-weight:950;">${escapeHtml(fmtMoneyFromCents(limit))}</div>
            </div>
            <div class="bar"><div style="width:${Math.round(pct*100)}%"></div></div>
            <div class="foot">
              <span>${escapeHtml(fmtMoneyFromCents(spent))} used</span>
              <span>${escapeHtml(fmtMoneyFromCents(Math.max(0, limit-spent)))} left</span>
            </div>
          </div>
        `;
      }).join('');
    }

    // ---------------- Import wiring ----------------
    async function importJsonFile(file){
      try{
        const text = await file.text();
        const r = await fetch('import.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: text });
        const j = await r.json();
        if(!r.ok || j.error) throw new Error(j.error || 'Import failed');
        toast('Imported');
        await bootstrap();
        await refresh();
      }catch(e){
        toast('Error: ' + e.message);
      }
    }

    // ---------------- Boot ----------------
    async function main(){
      try{
        setPageTitle(); showView();
        await bootstrap();
        setPageTitle(); showView();

        document.getElementById('curSymbol').textContent = symbol();
        document.getElementById('budCur').textContent = symbol();
        document.getElementById('date').value = new Date().toISOString().slice(0,10);

        // init labels for custom selects
        setAccountLabel();
        setFilterTypeLabel();
        setFilterCategoryLabel();
        setBudgetCategoryLabel();

        const imp1 = document.getElementById('importFile');
        if(imp1) imp1.addEventListener('change', async (e)=> {
          const f = e.target.files?.[0]; if(f) await importJsonFile(f);
          e.target.value = '';
        });
        const imp2 = document.getElementById('settingsImport');
        if(imp2) imp2.addEventListener('change', async (e)=> {
          const f = e.target.files?.[0]; if(f) await importJsonFile(f);
          e.target.value = '';
        });

        state.filters.from = state.month;
        state.filters.to = monthEnd(state.month);

        refreshSettings();
        await refresh();

        // PIN lock at startup
        if(hasPin() && localStorage.getItem(UNLOCK_KEY) !== '1'){
          state.pin.entered = '';
          renderPinUI();
          lockShow();
        } else if(!hasPin()){
          state.pin.entered = '';
          renderPinUI();
          lockShow();
        }
      } catch(e){
        toast('Init error: ' + e.message);
      }
    }
    main();
    // ---- expose core API to plugins (Fin+) ----
    window.state = state;
    window.PAGE = PAGE;
    window.API = API;

    window.apiGet = apiGet;
    window.apiSend = apiSend;

    window.refresh = refresh;
    window.refreshDashboard = refreshDashboard;
    window.refreshTransactions = refreshTransactions;
    window.refreshBudgets = refreshBudgets;

    window.openFilterSheet = openFilterSheet;
    window.clearFilters = clearFilters;
    window.applyFilters = applyFilters;

    window.openSelectSheet = openSelectSheet;
    window.setFilterTypeLabel = setFilterTypeLabel;
    window.setFilterCategoryLabel = setFilterCategoryLabel;

    window.txRowHTML = txRowHTML;
    window.bindTxClicks = bindTxClicks;
    window.editTx = editTx;

    window.monthEnd = monthEnd;
    window.fmtMoneyFromCents = fmtMoneyFromCents;
    window.escapeHtml = escapeHtml;

    window.renderPinUI = renderPinUI;
    window.lockShow = lockShow;
    window.lockHide = lockHide;

    window.toast = toast;
    window.openSheet = openSheet;
    window.closeSheet = closeSheet;

    // budgets helpers used by budget.php
    window.openBudgetEdit = openBudgetEdit;
  </script>

  <script src="fin_plus.php?mode=js"></script>
</body>
</html>