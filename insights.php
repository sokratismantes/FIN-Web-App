<?php
// insights.php — included from index.php
?>
<style>
  .insights-wrap{ display:flex; flex-direction:column; gap:14px; }
  .insights-grid{ display:grid; grid-template-columns:1fr; gap:12px; }
  @media(min-width:860px){ .insights-grid{ grid-template-columns:1fr 1fr; } }

  .insights-hero{ padding: 16px; }
  .insights-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom: 10px; }
  .insights-title{ display:flex; flex-direction:column; gap:4px; }
  .insights-title .h{ font-weight: 950; letter-spacing:-.2px; font-size: 16px; }
  .insights-title .p{ color: var(--muted); font-size: 12.5px; line-height: 1.35; }

  .kpi-strip{ display:flex; gap:10px; flex-wrap:wrap; margin-top: 12px; }
  .kpi-pill{
    display:inline-flex; align-items:center; gap:8px;
    padding: 10px 12px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
    font-size: 12.5px;
    user-select:none;
  }
  html[data-theme="light"] .kpi-pill{
    background: linear-gradient(180deg, rgba(10,15,25,.04), rgba(10,15,25,.02));
  }
  .kpi-pill b{ font-weight: 950; font-variant-numeric: tabular-nums; }
  .kpi-dot{ width:8px; height:8px; border-radius:999px; display:inline-block; }

  .ins-card{ padding: 16px; }
  .ins-sub{ color: var(--muted); font-size: 12.5px; margin-top: 4px; }

  .donut-row{ display:flex; gap:14px; align-items:center; }
  @media(max-width:620px){ .donut-row{ flex-direction:column; align-items:flex-start; } }

  /* ---- 3D Donut (SMALL / THIN) ---- */
  .donut{
    --size: 190px;
    --thick: 16px;
    --gap: 6deg;
    width: var(--size);
    height: var(--size);
    border-radius: 50%;
    position: relative;
    flex: 0 0 auto;
    transform: translateZ(0);
    filter:
      drop-shadow(0 14px 22px rgba(0,0,0,.28))
      drop-shadow(0 32px 90px rgba(0,0,0,.16));
    will-change: transform, opacity;
    overflow: hidden;
  }
  @media(min-width:980px){
    .donut{ --size: 210px; --thick: 18px; }
  }

  .donut::before{
    content:"";
    position:absolute; inset:0;
    border-radius: 50%;
    background: var(--grad, conic-gradient(from -90deg, rgba(255,255,255,.14) 0 360deg));
    -webkit-mask: radial-gradient(closest-side, transparent calc(50% - var(--thick)), #000 calc(50% - var(--thick) + 1px));
            mask: radial-gradient(closest-side, transparent calc(50% - var(--thick)), #000 calc(50% - var(--thick) + 1px));
    box-shadow:
      inset 0 10px 16px rgba(255,255,255,.10),
      inset 0 -18px 28px rgba(0,0,0,.24);
  }

  .donut::after{
    content:"";
    position:absolute; inset:-2px;
    border-radius: 50%;
    background:
      radial-gradient(circle at 28% 22%, rgba(255,255,255,.28), rgba(255,255,255,0) 45%),
      radial-gradient(circle at 65% 75%, rgba(0,0,0,.30), rgba(0,0,0,0) 55%),
      radial-gradient(circle at 50% 100%, rgba(0,0,0,.38), rgba(0,0,0,0) 58%);
    transform: translateY(12px) scale(.987);
    opacity: .92;
    pointer-events:none;
  }

  /* Center hole / glass — NO TEXT INSIDE */
  .donut .center{
    position:absolute; inset:0;
    border-radius: 50%;
    margin: var(--thick);
    background:
      radial-gradient(180px 120px at 30% 25%, rgba(255,255,255,.12), rgba(255,255,255,0) 60%),
      rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.12);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow:
      inset 0 16px 26px rgba(0,0,0,.22),
      inset 0 1px 0 rgba(255,255,255,.10);
    pointer-events:none;
  }
  html[data-theme="light"] .donut .center{
    background:
      radial-gradient(180px 120px at 30% 25%, rgba(10,15,25,.08), rgba(10,15,25,0) 60%),
      rgba(10,15,25,.03);
    border-color: rgba(10,15,25,.12);
  }

  /* kill any possible overlay labels */
  .donut .labels{ display:none !important; }
  .donut span, .donut p, .donut .badge, .donut .label, .donut .text, .donut .value, .donut .percent{
    display:none !important;
  }

  /* Legend */
  .legend{ display:flex; flex-direction:column; gap:10px; flex:1; width:100%; }
  .leg-item{
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding: 10px 12px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.06);
    background: rgba(255,255,255,.02);
    cursor:pointer; user-select:none;
    transition: transform .16s var(--tap), opacity .16s var(--tap);
  }
  html[data-theme="light"] .leg-item{
    border-color: rgba(10,15,25,.08);
    background: rgba(10,15,25,.02);
  }
  .leg-item:hover{ border-color: var(--border2); background: var(--surface2); }
  .leg-item:active{ transform: translateY(1px) scale(.99); }
  .leg-item.off{ opacity:.45; }
  .sw{ width: 10px; height: 10px; border-radius: 50%; display:inline-block; box-shadow: 0 10px 16px rgba(0,0,0,.22); }
  .leg-left{ display:flex; align-items:center; gap:10px; min-width:0; }
  .leg-left span.txt{ overflow:hidden; white-space:nowrap; text-overflow:ellipsis; font-size: 12.5px; font-weight: 900; }
  .leg-val{ font-size: 12.5px; color: var(--text); font-weight: 900; font-variant-numeric: tabular-nums; }
  .leg-val em{ color: var(--muted); font-style: normal; font-weight: 850; margin-left: 6px; }

  /* Messages cards */
  .msg-grid{ display:grid; grid-template-columns: 1fr; gap: 10px; margin-top: 10px; }
  @media(min-width:720px){ .msg-grid{ grid-template-columns: 1fr 1fr; } }
  .msg{
    border-radius: 18px;
    border: 1px solid var(--border);
    background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
    padding: 12px 12px;
    display:flex;
    gap: 10px;
    align-items:flex-start;
  }
  html[data-theme="light"] .msg{ background: linear-gradient(180deg, rgba(10,15,25,.04), rgba(10,15,25,.02)); }
  .msg .m-ic{
    width: 38px; height: 38px;
    border-radius: 14px;
    display:flex; align-items:center; justify-content:center;
    border: 1px solid var(--border);
    background: rgba(255,255,255,.03);
    flex: 0 0 auto;
  }
  .msg .m-t{ font-weight: 950; letter-spacing:-.1px; font-size: 13.5px; }
  .msg .m-s{ margin-top: 2px; color: var(--muted); font-size: 12.5px; line-height: 1.35; }

  .mini-grid{ display:grid; grid-template-columns:1fr; gap:10px; margin-top: 12px; }
  @media(min-width:720px){ .mini-grid{ grid-template-columns:1fr 1fr; } }
  .mini-card{
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.06);
    background: rgba(255,255,255,.02);
    padding: 12px 12px;
  }
  html[data-theme="light"] .mini-card{
    border-color: rgba(10,15,25,.08);
    background: rgba(10,15,25,.02);
  }
  .mini-card .t{ color: var(--muted); font-size: 12px; letter-spacing:.2px; text-transform: uppercase; }
  .mini-card .v{ margin-top: 6px; font-weight: 950; font-variant-numeric: tabular-nums; }
  .mini-card .s{ margin-top: 2px; color: var(--muted); font-size: 12.5px; }

  .bars{ display:grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 12px; align-items:end; }
  .barcol{ height: 74px; display:flex; align-items:flex-end; }
  .barfill{
    width:100%;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.10);
    background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.03));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
  }
  html[data-theme="light"] .barfill{
    border-color: rgba(10,15,25,.12);
    background: linear-gradient(180deg, rgba(10,15,25,.08), rgba(10,15,25,.03));
  }
  .barlabel{ margin-top: 8px; text-align:center; color: var(--muted); font-size: 11px; }

  .wk-grid{ display:grid; grid-template-columns:1fr; gap:10px; margin-top: 12px; }
  @media(min-width:720px){ .wk-grid{ grid-template-columns: repeat(5, 1fr); } }
  .wk{
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.06);
    background: rgba(255,255,255,.02);
    padding: 10px 10px;
  }
  html[data-theme="light"] .wk{
    border-color: rgba(10,15,25,.08);
    background: rgba(10,15,25,.02);
  }
  .wk .w{ color: var(--muted); font-size: 12px; font-weight: 900; }
  .wk .a{ margin-top: 6px; font-weight: 950; font-variant-numeric: tabular-nums; font-size: 13.5px; }
  .wk .p{ margin-top: 8px; height:8px; border-radius:999px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08); overflow:hidden; }
  html[data-theme="light"] .wk .p{ background: rgba(10,15,25,.06); border-color: rgba(10,15,25,.10); }
  .wk .p > div{ height:100%; width:40%; background: linear-gradient(90deg, rgba(109,94,247,.95), rgba(73,217,161,.85)); }

  .list-lite{ display:flex; flex-direction:column; gap:10px; margin-top: 12px; }
  .lite{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding: 10px 12px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.06);
    background: rgba(255,255,255,.02);
  }
  html[data-theme="light"] .lite{ border-color: rgba(10,15,25,.08); background: rgba(10,15,25,.02); }
  .lite .l{ display:flex; gap:10px; align-items:center; min-width:0; }
  .lite .l .b{ width:10px; height:10px; border-radius:999px; }
  .lite .l .t{ font-weight: 950; font-size: 12.8px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
  .lite .r{ font-weight: 950; font-variant-numeric: tabular-nums; font-size: 12.8px; color: var(--text); }
  .lite .r em{ color: var(--muted); font-style: normal; font-weight: 850; margin-left: 6px; }

  /* Loading */
  @keyframes shimmer{ 0%{ background-position: 200% 0; } 100%{ background-position: -200% 0; } }
  .sk{
    border-radius: 16px;
    background: linear-gradient(90deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.12) 45%, rgba(255,255,255,.06) 90%);
    background-size: 240% 100%;
    animation: shimmer 1.15s linear infinite;
  }
  html[data-theme="light"] .sk{
    background: linear-gradient(90deg, rgba(10,15,25,.05) 0%, rgba(10,15,25,.10) 45%, rgba(10,15,25,.05) 90%);
    background-size: 240% 100%;
  }
  .donut.loading::before{
    background: conic-gradient(from -90deg,
      rgba(255,255,255,.10) 0 120deg,
      rgba(255,255,255,.06) 120deg 240deg,
      rgba(255,255,255,.08) 240deg 360deg);
  }
  html[data-theme="light"] .donut.loading::before{
    background: conic-gradient(from -90deg,
      rgba(10,15,25,.10) 0 120deg,
      rgba(10,15,25,.06) 120deg 240deg,
      rgba(10,15,25,.08) 240deg 360deg);
  }
  .legend.loading .leg-item{ cursor: default; }
  .legend.loading .leg-item:hover{ background: rgba(255,255,255,.02); border-color: rgba(255,255,255,.06); }
  .leg-skel-left{ display:flex; align-items:center; gap:10px; min-width:0; }
  .sk-dot{ width:10px; height:10px; border-radius:999px; }
  .sk-txt{ height: 12px; width: 120px; border-radius: 999px; }
  .sk-val{ height: 12px; width: 70px; border-radius: 999px; }

  /* ===== Liquid Glass popup for "Other" (Spending mix) ===== */
  .ins-pop{
    position: fixed; inset: 0;
    display:none;
    align-items:center; justify-content:center;
    padding: 18px 14px calc(18px + env(safe-area-inset-bottom));
    z-index: 140;
    background: rgba(0,0,0,.45);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
  }
  html[data-theme="light"] .ins-pop{ background: rgba(10,15,25,.22); }
  .ins-pop.show{ display:flex; }

  .ins-pop-card{
    width: min(560px, 100%);
    border-radius: 26px;
    border: 1px solid var(--border2);
    background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.03));
    box-shadow: var(--shadow);
    overflow:hidden;
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
    position: relative;
  }
  html[data-theme="light"] .ins-pop-card{
    background: linear-gradient(180deg, rgba(10,15,25,.05), rgba(10,15,25,.02));
  }
  .ins-pop-card::before{
    content:"";
    position:absolute; inset:-2px;
    background:
      radial-gradient(700px 240px at 18% 0%, rgba(255,255,255,.22), transparent 55%),
      radial-gradient(600px 260px at 85% 10%, rgba(109,94,247,.18), transparent 60%);
    opacity:.35;
    pointer-events:none;
  }
  .ins-pop-head{
    display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
    padding: 14px 14px 10px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    position: relative; z-index: 1;
  }
  html[data-theme="light"] .ins-pop-head{ border-bottom-color: rgba(10,15,25,.10); }
  .ins-pop-title{ font-weight: 950; letter-spacing:-.2px; font-size: 15px; }
  .ins-pop-sub{ margin-top: 4px; color: var(--muted); font-size: 12.5px; }

  .ins-pop-list{
    padding: 12px 14px 14px;
    max-height: min(62vh, 520px);
    overflow:auto;
    -webkit-overflow-scrolling: touch;
    position: relative; z-index: 1;
  }
  .ins-pop-row{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding: 10px 12px;
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.02);
    margin-bottom: 10px;
  }
  html[data-theme="light"] .ins-pop-row{
    border-color: rgba(10,15,25,.10);
    background: rgba(10,15,25,.02);
  }
  .ins-pop-left{ display:flex; align-items:center; gap:10px; min-width:0; flex:1; }
  .ins-pop-dot{
    width:10px;height:10px;border-radius:999px;
    box-shadow: 0 10px 16px rgba(0,0,0,.22);
    flex:0 0 auto;
  }
  .ins-pop-name{
    font-weight: 950;
    font-size: 12.8px;
    overflow:hidden;
    white-space:nowrap;
    text-overflow:ellipsis;
  }
  .ins-pop-right{ text-align:right; flex:0 0 auto; }
  .ins-pop-amt{
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    font-size: 12.8px;
  }
  .ins-pop-pct{
    margin-top: 2px;
    color: var(--muted);
    font-size: 11.5px;
    font-variant-numeric: tabular-nums;
  }

  /* ===== NEW: mini progress bars per category (Apple glass) ===== */
  .ins-pop-mid{ flex:1; min-width:0; }
  .ins-pop-bar{
    margin-top: 7px;
    height: 7px;
    border-radius: 999px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.10);
    overflow:hidden;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
  }
  html[data-theme="light"] .ins-pop-bar{
    background: rgba(10,15,25,.06);
    border-color: rgba(10,15,25,.12);
    box-shadow: inset 0 1px 0 rgba(10,15,25,.06);
  }
  .ins-pop-bar > div{
    height: 100%;
    width: 0%;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(255,255,255,.14), rgba(255,255,255,.06));
    box-shadow: 0 10px 18px rgba(0,0,0,.18);
  }
</style>

<div id="view-insights" style="display:none;">
  <div class="insights-wrap">

    <div class="card hero">
      <div class="inner insights-hero">
        <div class="insights-head">
          <div class="insights-title">
            <div class="h">Insights</div>
          </div>
          <span class="pill" id="insightsMonthPill">—</span>
        </div>

        <div class="kpi-strip">
          <div class="kpi-pill"><span class="kpi-dot" style="background:#49D9A1"></span>Income <b id="kpiInsIncome">—</b></div>
          <div class="kpi-pill"><span class="kpi-dot" style="background:#FF5C7A"></span>Expense <b id="kpiInsExpense">—</b></div>
          <div class="kpi-pill"><span class="kpi-dot" style="background:#6D5EF7"></span>Saved <b id="kpiInsSaved">—</b></div>
        </div>

        <div class="msg-grid" id="smartIdeas" style="margin-top:12px;">—</div>
      </div>
    </div>

    <div class="insights-grid">
      <!-- Spending mix -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Spending mix</div>
          </div>
          <div class="ins-sub" id="mixSub">—</div>
          <div class="donut-row" style="margin-top:12px;">
            <div class="donut loading" id="donutMix"><div class="labels"></div><div class="center"></div></div>
            <div class="legend loading" id="legendMix"></div>
          </div>
        </div>
      </div>

      <!-- Budget pressure -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Budget pressure</div>
          </div>
          <div class="ins-sub" id="budSub">—</div>
          <div class="donut-row" style="margin-top:12px;">
            <div class="donut loading" id="donutBud"><div class="labels"></div><div class="center"></div></div>
            <div class="legend loading" id="legendBud"></div>
          </div>
        </div>
      </div>

      <!-- Income vs Expense -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Income vs Expense</div>
          </div>
          <div class="ins-sub" id="ivSub">—</div>
          <div class="donut-row" style="margin-top:12px;">
            <div class="donut loading" id="donutIV"><div class="labels"></div><div class="center"></div></div>
            <div class="legend loading" id="legendIV"></div>
          </div>
        </div>
      </div>

      <!-- Account split -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Account split</div>
          </div>
          <div class="ins-sub" id="accSub">—</div>
          <div class="donut-row" style="margin-top:12px;">
            <div class="donut loading" id="donutAcc"><div class="labels"></div><div class="center"></div></div>
            <div class="legend loading" id="legendAcc"></div>
          </div>
        </div>
      </div>

      <!-- Merchant mix -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Merchant mix</div>
          </div>
          <div class="ins-sub" id="merchSub">—</div>
          <div class="donut-row" style="margin-top:12px;">
            <div class="donut loading" id="donutMerch"><div class="labels"></div><div class="center"></div></div>
            <div class="legend loading" id="legendMerch"></div>
          </div>
        </div>
      </div>

      <!-- Impulse -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Impulse</div>
          </div>
          <div class="ins-sub" id="impSub">—</div>
          <div class="donut-row" style="margin-top:12px;">
            <div class="donut loading" id="donutImp"><div class="labels"></div><div class="center"></div></div>
            <div class="legend loading" id="legendImp"></div>
          </div>
        </div>
      </div>

      <!-- Trend -->
      <div class="card" style="grid-column:1 / -1;">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Trend vs last month</div>
          </div>
          <div class="mini-grid" id="trendGrid"></div>
        </div>
      </div>

      <!-- Forecast -->
      <div class="card" style="grid-column:1 / -1;">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Forecast</div>
          </div>
          <div class="mini-grid" id="forecastGrid"></div>
        </div>
      </div>

      <!-- Day of week -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Day-of-week</div>
          </div>
          <div class="ins-sub" id="dowSub">—</div>
          <div class="bars" id="dowBars"></div>
          <div class="bars" style="margin-top:8px" id="dowLabels"></div>
        </div>
      </div>

      <!-- Savings streak -->
      <div class="card">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Savings streak</div>
          </div>
          <div class="mini-grid" id="streakGrid"></div>
        </div>
      </div>

      <!-- Over budget -->
      <div class="card" style="grid-column:1 / -1;">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Over-budget</div>
          </div>
          <div class="ins-sub" id="overSub">—</div>
          <div class="list-lite" id="overList"></div>
        </div>
      </div>

      <!-- Weekly burn -->
      <div class="card" style="grid-column:1 / -1;">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Weekly burn-rate</div>
          </div>
          <div class="ins-sub" id="wkSub">—</div>
          <div class="wk-grid" id="wkGrid"></div>
        </div>
      </div>

      <!-- Recurring -->
      <div class="card" style="grid-column:1 / -1;">
        <div class="inner ins-card">
          <div class="card-header" style="margin-bottom:6px;">
            <div class="card-title">Recurring detector</div>
          </div>
          <div class="ins-sub" id="recSub">—</div>
          <div class="list-lite" id="recList"></div>
        </div>
      </div>
    </div>

    <!-- Liquid Glass popup (ONLY for "Other" in Spending mix) -->
    <div class="ins-pop" id="insOtherPop" aria-hidden="true" onclick="__insOtherClose(event)">
      <div class="ins-pop-card" role="dialog" aria-modal="true" onclick="event.stopPropagation()">
        <div class="ins-pop-head">
          <div>
            <div class="ins-pop-title" id="insOtherTitle">Other categories</div>
            <div class="ins-pop-sub" id="insOtherSub">—</div>
          </div>
          <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="__insOtherClose()" aria-label="Close">✕</button>
        </div>
        <div class="ins-pop-list" id="insOtherList"></div>
      </div>
    </div>

  </div>
</div>

<script>
  // ---------- helpers ----------
  function _safeParts(parts){ return (parts || []).filter(p=>p && p.value > 0); }

  function _setSkeletonLegend(el){
    el.classList.add('loading');
    el.innerHTML = `
      <div class="leg-item"><div class="leg-skel-left"><span class="sk sk-dot"></span><span class="sk sk-txt"></span></div><span class="sk sk-val"></span></div>
      <div class="leg-item"><div class="leg-skel-left"><span class="sk sk-dot"></span><span class="sk sk-txt" style="width:150px"></span></div><span class="sk sk-val" style="width:90px"></span></div>
      <div class="leg-item"><div class="leg-skel-left"><span class="sk sk-dot"></span><span class="sk sk-txt" style="width:110px"></span></div><span class="sk sk-val" style="width:80px"></span></div>
    `;
  }

  function _setLoadingUI(){
    ['kpiInsIncome','kpiInsExpense','kpiInsSaved'].forEach(id=>{
      const el = document.getElementById(id);
      if(el) el.textContent = '—';
    });

    const box = document.getElementById('smartIdeas');
    if(box){
      box.innerHTML = `
        <div class="msg"><div class="m-ic"><span class="sk" style="width:20px;height:20px;border-radius:8px;display:block"></span></div>
          <div style="width:100%">
            <div class="sk" style="height:12px;width:160px;border-radius:999px"></div>
            <div class="sk" style="height:12px;width:100%;border-radius:999px;margin-top:8px"></div>
          </div>
        </div>
        <div class="msg"><div class="m-ic"><span class="sk" style="width:20px;height:20px;border-radius:8px;display:block"></span></div>
          <div style="width:100%">
            <div class="sk" style="height:12px;width:140px;border-radius:999px"></div>
            <div class="sk" style="height:12px;width:85%;border-radius:999px;margin-top:8px"></div>
          </div>
        </div>
      `;
    }

    const donuts = ['donutMix','donutBud','donutIV','donutAcc','donutMerch','donutImp'];
    donuts.forEach(id=>{
      const d = document.getElementById(id);
      if(d){
        d.classList.add('loading');
        d.style.removeProperty('--grad');
      }
    });

    const legends = ['legendMix','legendBud','legendIV','legendAcc','legendMerch','legendImp'];
    legends.forEach(id=>{
      const el = document.getElementById(id);
      if(el) _setSkeletonLegend(el);
    });

    // reset blocks
    document.getElementById('trendGrid').innerHTML = '';
    document.getElementById('forecastGrid').innerHTML = '';
    document.getElementById('streakGrid').innerHTML = '';
    document.getElementById('wkGrid').innerHTML = '';
    document.getElementById('overList').innerHTML = '';
    document.getElementById('recList').innerHTML = '';
    document.getElementById('dowBars').innerHTML = '';
    document.getElementById('dowLabels').innerHTML = '';
  }

  function _buildGradient(parts, gapDeg){
    const safe = _safeParts(parts);
    const total = safe.reduce((s,p)=>s+p.value,0) || 1;
    const n = safe.length || 1;
    const gap = Math.max(0, gapDeg || 0);
    const usable = 360 - gap * n;
    let cur = 0;
    const stops = [];

    for(const p of safe){
      const sweep = (p.value/total) * usable;
      const a0 = cur;
      const a1 = cur + sweep;
      stops.push(`${p.color} ${a0}deg ${a1}deg`);
      stops.push(`rgba(255,255,255,.00) ${a1}deg ${a1 + gap}deg`);
      cur = a1 + gap;
    }
    return `conic-gradient(from -90deg, ${stops.join(',')})`;
  }

  function setDonut(el, parts){
    const grad = _buildGradient(parts, 6);
    el.style.setProperty('--grad', grad);
    el.classList.remove('loading');
    // keep hole
    if(!el.querySelector('.center')) el.insertAdjacentHTML('beforeend', '<div class="center"></div>');
  }

  // ===== "Other" popup data store =====
  window.__insMixOther = window.__insMixOther || { items:[], total:0, sum:0 };

  function __insOtherOpen(){
    const pop = document.getElementById('insOtherPop');
    const title = document.getElementById('insOtherTitle');
    const sub = document.getElementById('insOtherSub');
    const list = document.getElementById('insOtherList');

    const data = window.__insMixOther || { items:[], total:0, sum:0 };
    const total = Math.max(1, Number(data.total||0));
    const sum = Math.max(0, Number(data.sum||0));
    const items = (data.items || []).slice().sort((a,b)=> (b.value||0) - (a.value||0));

    title.textContent = 'Other categories';
    sub.textContent = `${items.length} categories • ${_money(sum)}`;

    list.innerHTML = items.map((it)=>{
      const v = Math.max(0, Number(it.value||0));
      const pct = (v/total)*100;
      const col = it.color || 'rgba(255,255,255,.14)';
      const w = Math.max(0, Math.min(100, pct));
      return `
        <div class="ins-pop-row">
          <div class="ins-pop-left">
            <span class="ins-pop-dot" style="background:${col}"></span>
            <div class="ins-pop-mid">
              <div class="ins-pop-name">${escapeHtml(it.label)}</div>
              <div class="ins-pop-bar"><div style="width:${w.toFixed(2)}%; background: linear-gradient(90deg, ${col}, rgba(255,255,255,.10));"></div></div>
            </div>
          </div>
          <div class="ins-pop-right">
            <div class="ins-pop-amt">${escapeHtml(_money(v))}</div>
            <div class="ins-pop-pct">${pct.toFixed(1)}%</div>
          </div>
        </div>
      `;
    }).join('') || `<div style="color:var(--muted); font-size:12.5px;">No data.</div>`;

    pop.classList.add('show');
    pop.setAttribute('aria-hidden','false');
  }

  function __insOtherClose(){
    const pop = document.getElementById('insOtherPop');
    pop.classList.remove('show');
    pop.setAttribute('aria-hidden','true');
  }

  window.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){
      const pop = document.getElementById('insOtherPop');
      if(pop && pop.classList.contains('show')) __insOtherClose();
    }
  });

  function setLegend(el, parts, formatter, onToggle){
    const safe = _safeParts(parts);
    const total = safe.reduce((s,p)=>s+p.value,0) || 1;
    el.classList.remove('loading');

    el.innerHTML = safe.map((p, idx)=>{
      const pct = (p.value/total)*100;
      return `
        <div class="leg-item" data-i="${idx}"
          ${(el.id === 'legendMix' && String(p.label) === 'Other')
            ? `onclick="__insOtherOpen()"`
            : `onclick="__insToggle('${el.id}', ${idx})"`}>
          <div class="leg-left">
            <span class="sw" style="background:${p.color}"></span>
            <span class="txt">${escapeHtml(p.label)}</span>
          </div>
          <div class="leg-val">${formatter(p.value)} <em>${pct.toFixed(0)}%</em></div>
        </div>
      `;
    }).join('') || `<div style="color:var(--muted); font-size:12.5px;">No data yet.</div>`;

    window.__insLegendHandlers = window.__insLegendHandlers || {};
    window.__insLegendHandlers[el.id] = { parts:safe, onToggle };
  }

  window.__insOff = window.__insOff || {};
  window.__insToggle = function(legendId, idx){
    window.__insOff[legendId] = window.__insOff[legendId] || new Set();
    const s = window.__insOff[legendId];
    if(s.has(idx)) s.delete(idx); else s.add(idx);

    const root = document.getElementById(legendId);
    root.querySelectorAll('.leg-item').forEach(it=>{
      const i = parseInt(it.getAttribute('data-i'),10);
      it.classList.toggle('off', s.has(i));
    });

    const h = (window.__insLegendHandlers||{})[legendId];
    if(h && typeof h.onToggle === 'function') h.onToggle(s);
  }

  function _monthPrev(month){
    const d = new Date(month + "T00:00:00");
    d.setMonth(d.getMonth()-1);
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    return `${y}-${m}-01`;
  }
  function _monthAdd(month, add){
    const d = new Date(month + "T00:00:00");
    d.setMonth(d.getMonth()+add);
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    return `${y}-${m}-01`;
  }
  function _daysInMonth(month){
    const d = new Date(month + "T00:00:00");
    return new Date(d.getFullYear(), d.getMonth()+1, 0).getDate();
  }
  function _todayISO(){
    return new Date().toISOString().slice(0,10);
  }
  function _isSameMonth(isoDate, month){
    return isoDate && month && isoDate.slice(0,7) === month.slice(0,7);
  }
  function _sum(arr){ return arr.reduce((s,x)=>s+x,0); }
  function _money(v){ return fmtMoneyFromCents(Math.max(0, Math.round(v||0))); }

  function _miniCardHTML(t, v, s){
    return `<div class="mini-card"><div class="t">${escapeHtml(t)}</div><div class="v">${escapeHtml(v)}</div><div class="s">${escapeHtml(s||'')}</div></div>`;
  }

  // ---------- main ----------
  async function refreshInsights(){
    _setLoadingUI();

    try{
      const month = state.month;
      document.getElementById('insightsMonthPill').textContent = monthLabel(month);

      // Base stats month
      const stats = await apiGet(`${API}?action=stats_month&month=${encodeURIComponent(month)}`);
      const income = Math.max(0, Number(stats.income_cents||0));
      const expense = Math.max(0, Number(stats.expense_cents||0));
      const saved  = Math.max(0, income - expense);

      document.getElementById('kpiInsIncome').textContent = _money(income);
      document.getElementById('kpiInsExpense').textContent = _money(expense);
      document.getElementById('kpiInsSaved').textContent  = _money(saved);

      // Load all tx for month (expense + income)
      const from = month;
      const to = monthEnd(month);
      const txAll = await apiGet(`${API}?action=transactions&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`);
      const txItems = (txAll.items || []).slice();

      const exp = txItems.filter(t=>t && t.type==='expense');
      const inc = txItems.filter(t=>t && t.type==='income');

      // Build spentByCat from expenses
      const spentByCat = {};
      exp.forEach(t=>{
        const cid = String(t.category_id||'');
        const v = Math.max(0, Number(t.amount_cents||0));
        if(!cid) return;
        spentByCat[cid] = (spentByCat[cid]||0) + v;
      });

      // ---------- 1) Spending mix (by category) ----------
      const byCatName = new Map();
      exp.forEach(t=>{
        const key = t.category_name || ('Category #' + (t.category_id||'?'));
        const v = Math.max(0, Number(t.amount_cents||0));
        byCatName.set(key, (byCatName.get(key)||0) + v);
      });
      const topCats = [...byCatName.entries()].sort((a,b)=>b[1]-a[1]);
      const top4Cats = topCats.slice(0,4);
      const otherCats = topCats.slice(4).reduce((s,[,v])=>s+v,0);
      const pal = ['#6D5EF7','#49D9A1','#FFB020','#FF5C7A','#8DA0FF'];

      const mixParts = top4Cats.map(([label,value],i)=>({ label, value, color: pal[i] || pal[4] }));
      if(otherCats>0) mixParts.push({ label:'Other', value: otherCats, color:'rgba(255,255,255,.14)' });

      // prepare popup data for "Other" (remaining categories + % of total expense)
      const otherItems = topCats.slice(4).map(([label,value],i)=>({
        label,
        value,
        color: pal[(i+4) % pal.length] || pal[4]
      }));
      window.__insMixOther = {
        items: otherItems,
        total: Math.max(1, Number(expense || (topCats.reduce((s,[,v])=>s+v,0) || 1))),
        sum: otherCats
      };

      document.getElementById('mixSub').textContent = expense ? `Total: ${_money(expense)}` : '—';
      setDonut(document.getElementById('donutMix'), mixParts);
      setLegend(document.getElementById('legendMix'), mixParts, (v)=>_money(v), (offSet)=>{
        const safe = _safeParts(mixParts).map((x,i)=> ({...x, value: offSet.has(i)?0:x.value}));
        setDonut(document.getElementById('donutMix'), safe);
      });

      // ---------- 2) Budget pressure (client-side, because API has no used_cents) ----------
      const b = await apiGet(`${API}?action=budgets&month=${encodeURIComponent(month)}`);
      const budgets = (b.items || []);

      let budTotal = 0;
      let budUsed = 0;

      // per-category used/limit for overshoot list
      const budgetRows = budgets.map(it=>{
        const limit = Math.max(0, Number(it.limit_cents||0));
        const used = Math.max(0, Number(spentByCat[String(it.category_id||'')] || 0));
        budTotal += limit;
        budUsed += Math.min(used, limit); // pressure up to limit
        return {
          category_id: it.category_id,
          name: it.category_name || '—',
          icon: it.category_icon || '🏷️',
          limit, used
        };
      });

      const budLeft = Math.max(0, budTotal - budUsed);
      const usedPct = budTotal > 0 ? (budUsed / budTotal) * 100 : 0;

      document.getElementById('budSub').textContent =
        budTotal ? `Used: ${usedPct.toFixed(0)}% • ${_money(budUsed)} / ${_money(budTotal)}` : 'No budgets this month';

      const budParts = [
        {label:'Used', value: budUsed, color:'#FF5C7A'},
        {label:'Left', value: budLeft, color:'#49D9A1'}
      ];
      setDonut(document.getElementById('donutBud'), budParts);
      setLegend(document.getElementById('legendBud'), budParts, (v)=>_money(v), (offSet)=>{
        const safe = _safeParts(budParts).map((x,i)=> ({...x, value: offSet.has(i)?0:x.value}));
        setDonut(document.getElementById('donutBud'), safe);
      });

      // ---------- 3) Income vs Expense ----------
      const saveRate = income>0 ? (saved/income)*100 : 0;
      document.getElementById('ivSub').textContent =
        income ? `Savings rate: ${saveRate.toFixed(0)}% • ${_money(saved)} saved` : 'Add income to compute savings';

      const ivParts = [
        {label:'Expense', value: expense, color:'#FFB020'},
        {label:'Saved', value: saved, color:'#6D5EF7'}
      ];
      setDonut(document.getElementById('donutIV'), ivParts);
      setLegend(document.getElementById('legendIV'), ivParts, (v)=>_money(v), (offSet)=>{
        const safe = _safeParts(ivParts).map((x,i)=> ({...x, value: offSet.has(i)?0:x.value}));
        setDonut(document.getElementById('donutIV'), safe);
      });

      // ---------- 4) Account split (expenses by account) ----------
      const accMap = new Map();
      exp.forEach(t=>{
        const a = (t.account || 'Card').trim() || 'Card';
        accMap.set(a, (accMap.get(a)||0) + Math.max(0, Number(t.amount_cents||0)));
      });
      const accPairs = [...accMap.entries()].sort((a,b)=>b[1]-a[1]).slice(0,4);
      const accColors = { Card:'#6D5EF7', Cash:'#49D9A1', Bank:'#FFB020' };
      const accParts = accPairs.map(([label,value],i)=>({ label, value, color: accColors[label] || pal[i] || pal[4] }));

      document.getElementById('accSub').textContent = expense ? `Expense allocation by account` : '—';
      setDonut(document.getElementById('donutAcc'), accParts);
      setLegend(document.getElementById('legendAcc'), accParts, (v)=>_money(v), (offSet)=>{
        const safe = _safeParts(accParts).map((x,i)=> ({...x, value: offSet.has(i)?0:x.value}));
        setDonut(document.getElementById('donutAcc'), safe);
      });

      // ---------- 5) Merchant mix (top notes/merchants) ----------
      const merchMap = new Map();
      exp.forEach(t=>{
        const raw = (t.note || '').trim();
        const key = raw ? raw : (t.category_name || '—');
        merchMap.set(key, (merchMap.get(key)||0) + Math.max(0, Number(t.amount_cents||0)));
      });
      const merchTop = [...merchMap.entries()].sort((a,b)=>b[1]-a[1]);
      const merchTop4 = merchTop.slice(0,4);
      const merchOther = merchTop.slice(4).reduce((s,[,v])=>s+v,0);
      const merchParts = merchTop4.map(([label,value],i)=>({ label, value, color: pal[i] || pal[4] }));
      if(merchOther>0) merchParts.push({ label:'Other', value: merchOther, color:'rgba(255,255,255,.14)' });

      document.getElementById('merchSub').textContent = merchTop.length ? 'Based on note (or category when empty)' : '—';
      setDonut(document.getElementById('donutMerch'), merchParts);
      setLegend(document.getElementById('legendMerch'), merchParts, (v)=>_money(v), (offSet)=>{
        const safe = _safeParts(merchParts).map((x,i)=> ({...x, value: offSet.has(i)?0:x.value}));
        setDonut(document.getElementById('donutMerch'), safe);
      });

      // ---------- 6) Impulse (<10€) ----------
      const TH = 1000; // 10.00€
      const impulseTx = exp.filter(t => Math.max(0, Number(t.amount_cents||0)) > 0 && Number(t.amount_cents||0) < TH);
      const impulseSum = _sum(impulseTx.map(t=>Math.max(0, Number(t.amount_cents||0))));
      const nonImpulse = Math.max(0, expense - impulseSum);

      const impParts = [
        { label:'< 10€', value: impulseSum, color:'#FF5C7A' },
        { label:'Other', value: nonImpulse, color:'#6D5EF7' }
      ];
      const impPct = expense>0 ? (impulseSum/expense)*100 : 0;
      document.getElementById('impSub').textContent =
        expense ? `${impulseTx.length} small transactions • ${impPct.toFixed(0)}% of expense` : '—';

      setDonut(document.getElementById('donutImp'), impParts);
      setLegend(document.getElementById('legendImp'), impParts, (v)=>_money(v), (offSet)=>{
        const safe = _safeParts(impParts).map((x,i)=> ({...x, value: offSet.has(i)?0:x.value}));
        setDonut(document.getElementById('donutImp'), safe);
      });

      // ---------- 7) Trend vs last month ----------
      const prev = _monthPrev(month);
      const prevStats = await apiGet(`${API}?action=stats_month&month=${encodeURIComponent(prev)}`);
      const prevIncome = Math.max(0, Number(prevStats.income_cents||0));
      const prevExpense = Math.max(0, Number(prevStats.expense_cents||0));

      const dInc = income - prevIncome;
      const dExp = expense - prevExpense;

      const arrow = (n)=> n>0 ? '↑' : (n<0 ? '↓' : '→');
      const absMoney = (n)=> _money(Math.abs(n));

      // top category change
      const prevTx = await apiGet(`${API}?action=transactions&from=${encodeURIComponent(prev)}&to=${encodeURIComponent(monthEnd(prev))}&type=expense`);
      const prevExp = (prevTx.items || []).filter(t=>t && t.type==='expense');

      const prevByCat = new Map();
      prevExp.forEach(t=>{
        const key = t.category_name || ('Category #' + (t.category_id||'?'));
        prevByCat.set(key, (prevByCat.get(key)||0) + Math.max(0, Number(t.amount_cents||0)));
      });
      const curTopCat = topCats[0]?.[0] || null;
      const curTopVal = topCats[0]?.[1] || 0;
      const prevTopVal = curTopCat ? (prevByCat.get(curTopCat)||0) : 0;
      const dTop = curTopVal - prevTopVal;

      const trendHTML = [
        _miniCardHTML('Total expense', `${arrow(dExp)} ${absMoney(dExp)}`, `This: ${_money(expense)} • Last: ${_money(prevExpense)}`),
        _miniCardHTML('Total income', `${arrow(dInc)} ${absMoney(dInc)}`, `This: ${_money(income)} • Last: ${_money(prevIncome)}`),
        _miniCardHTML('Top category', curTopCat ? `${curTopCat}` : '—', curTopCat ? `${arrow(dTop)} ${absMoney(dTop)} vs last month` : 'No data'),
        _miniCardHTML('Savings', `${arrow(saved - Math.max(0, prevIncome - prevExpense))} ${absMoney(saved - Math.max(0, prevIncome - prevExpense))}`, `This: ${_money(saved)}`)
      ].join('');
      document.getElementById('trendGrid').innerHTML = trendHTML;

      // ---------- 8) Forecast end-of-month (only meaningful if viewing current month) ----------
      const totalDays = _daysInMonth(month);
      const today = _todayISO();
      const isCurrent = _isSameMonth(today, month);

      let daysElapsed = totalDays;
      if(isCurrent){
        const dayNum = parseInt(today.slice(8,10),10);
        daysElapsed = Math.max(1, Math.min(totalDays, dayNum));
      } else {
        daysElapsed = totalDays;
      }

      let expenseSoFar = expense;
      if(isCurrent){
        expenseSoFar = _sum(exp.filter(t => t.date <= today).map(t=>Math.max(0, Number(t.amount_cents||0))));
      }

      const pacePerDay = daysElapsed>0 ? (expenseSoFar / daysElapsed) : 0;
      const projected = isCurrent ? (pacePerDay * totalDays) : expense;
      const diffProj = projected - expenseSoFar;

      const forecastHTML = [
        _miniCardHTML('Projected expense', _money(projected), isCurrent ? `At current pace (~${_money(pacePerDay)} / day)` : 'Past month (no projection)'),
        _miniCardHTML('Remaining (pace)', isCurrent ? _money(diffProj) : '—', isCurrent ? `Days left: ${totalDays - daysElapsed}` : ''),
        _miniCardHTML('Budget status', budTotal ? `${usedPct.toFixed(0)}% used` : 'No budgets', budTotal ? `${_money(budUsed)} / ${_money(budTotal)}` : 'Set budgets for better forecast'),
        _miniCardHTML('Savings rate', income ? `${saveRate.toFixed(0)}%` : '—', income ? `${_money(saved)} saved` : 'Add income')
      ].join('');
      document.getElementById('forecastGrid').innerHTML = forecastHTML;

      // ---------- 9) Day-of-week pattern ----------
      const dowNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      const dowSum = Array(7).fill(0);
      exp.forEach(t=>{
        const d = new Date((t.date || month) + "T00:00:00");
        const idx = d.getDay();
        dowSum[idx] += Math.max(0, Number(t.amount_cents||0));
      });
      const maxDow = Math.max(...dowSum, 1);

      document.getElementById('dowSub').textContent = expense ? 'Where spending concentrates' : '—';

      const bars = document.getElementById('dowBars');
      const labels = document.getElementById('dowLabels');

      bars.innerHTML = dowSum.map(v=>{
        const h = Math.max(10, Math.round((v/maxDow)*74));
        return `<div class="barcol"><div class="barfill" style="height:${h}px"></div></div>`;
      }).join('');

      labels.innerHTML = dowNames.map(n=>{
        return `<div style="text-align:center"><div class="barlabel">${n}</div></div>`;
      }).join('');

      // ---------- 10) Savings streak (days with 0 expense) ----------
      const dayHasExpense = {};
      exp.forEach(t=>{
        const d = t.date;
        dayHasExpense[d] = true;
      });

      const monthStart = new Date(month + "T00:00:00");
      const last = new Date(monthEnd(month) + "T00:00:00");

      const endForStreak = isCurrent ? new Date(today + "T00:00:00") : last;

      let curStreak = 0, bestStreak = 0;
      for(let d = new Date(monthStart); d <= endForStreak; d.setDate(d.getDate()+1)){
        const iso = d.toISOString().slice(0,10);
        if(dayHasExpense[iso]){
          bestStreak = Math.max(bestStreak, curStreak);
          curStreak = 0;
        } else {
          curStreak++;
        }
      }
      bestStreak = Math.max(bestStreak, curStreak);

      const streakHTML = [
        _miniCardHTML('Current streak', `${curStreak} days`, isCurrent ? 'No-expense streak up to today' : 'No-expense streak to month end'),
        _miniCardHTML('Best streak', `${bestStreak} days`, 'Best no-expense streak this month'),
        _miniCardHTML('Days with expenses', `${Object.keys(dayHasExpense).length}`, `Out of ${isCurrent ? daysElapsed : totalDays} days`),
        _miniCardHTML('Impulse count', `${impulseTx.length}`, '<10€ transactions')
      ].join('');
      document.getElementById('streakGrid').innerHTML = streakHTML;

      // ---------- 11) Over-budget categories ----------
      const over = budgetRows
        .filter(r => r.limit > 0 && r.used > r.limit)
        .sort((a,b)=> (b.used-b.limit) - (a.used-a.limit));

      document.getElementById('overSub').textContent =
        budgets.length ? (over.length ? `${over.length} categories over limit` : 'No category over limit') : 'No budgets set';

      const overList = document.getElementById('overList');
      if(!budgets.length){
        overList.innerHTML = `<div style="color:var(--muted); font-size:12.5px;">Set budgets to track overshoots.</div>`;
      } else if(!over.length){
        overList.innerHTML = `<div style="color:var(--muted); font-size:12.5px;">All good — nothing over budget.</div>`;
      } else {
        overList.innerHTML = over.slice(0,10).map(r=>{
          const overAmt = Math.max(0, r.used - r.limit);
          const pct = r.limit>0 ? (r.used/r.limit)*100 : 0;
          return `
            <div class="lite">
              <div class="l">
                <div class="b" style="background:#FF5C7A"></div>
                <div class="t">${escapeHtml((r.icon||'🏷️') + ' ' + r.name)}</div>
              </div>
              <div class="r">${escapeHtml(_money(overAmt))} <em>${pct.toFixed(0)}%</em></div>
            </div>
          `;
        }).join('');
      }

      // ---------- 12) Weekly burn-rate ----------
      const wkBuckets = Array(5).fill(0);
      exp.forEach(t=>{
        const day = parseInt((t.date||'').slice(8,10),10) || 1;
        const w = Math.min(4, Math.floor((day-1)/7));
        wkBuckets[w] += Math.max(0, Number(t.amount_cents||0));
      });

      const targetPerWeek = isCurrent
        ? (expenseSoFar / Math.max(1, Math.floor((daysElapsed+6)/7)))
        : (expense / Math.max(1, Math.floor((totalDays+6)/7)));

      document.getElementById('wkSub').textContent =
        expense ? `Weekly totals • target ~ ${_money(targetPerWeek)} / week` : '—';

      const wkGrid = document.getElementById('wkGrid');
      const wLabels = ['W1','W2','W3','W4','W5'];
      wkGrid.innerHTML = wkBuckets.map((v,i)=>{
        const pct = targetPerWeek>0 ? Math.min(1, v/targetPerWeek) : 0;
        return `
          <div class="wk">
            <div class="w">${wLabels[i]}</div>
            <div class="a">${escapeHtml(_money(v))}</div>
            <div class="p"><div style="width:${Math.round(pct*100)}%"></div></div>
          </div>
        `;
      }).join('');

      // ---------- 13) Recurring detector (look back 4 months, detect repeats) ----------
      const monthsBack = 4;
      const monthKeys = [];
      for(let i=0;i<monthsBack;i++){
        monthKeys.push(_monthAdd(month, -i));
      }

      const recMap = new Map();
      for(const mKey of monthKeys){
        const rr = await apiGet(`${API}?action=transactions&from=${encodeURIComponent(mKey)}&to=${encodeURIComponent(monthEnd(mKey))}&type=expense`);
        const items = (rr.items || []).filter(t=>t && t.type==='expense');

        items.forEach(t=>{
          const raw = (t.note || '').trim().toLowerCase();
          const label = (t.note || '').trim() || (t.category_name || '—');
          const key = raw ? raw : ('__cat__' + String(t.category_id||''));
          const v = Math.max(0, Number(t.amount_cents||0));

          if(!recMap.has(key)){
            recMap.set(key, { label, months:new Set(), sum:0, count:0, sample:v });
          }
          const r = recMap.get(key);
          r.months.add(mKey.slice(0,7));
          r.sum += v;
          r.count += 1;
        });
      }

      const recCandidates = [...recMap.values()]
        .map(r=>{
          const mcnt = r.months.size;
          const avg = r.count ? (r.sum / r.count) : 0;
          const okAvg = avg >= 500 && avg <= 30000;
          const score = mcnt*10 + (okAvg?6:0);
          return { ...r, mcnt, avg, score };
        })
        .filter(r=> r.mcnt >= 3)
        .sort((a,b)=> b.score - a.score);

      document.getElementById('recSub').textContent =
        recCandidates.length ? `Likely repeats across last ${monthsBack} months` : `No strong recurring patterns found (last ${monthsBack} months)`;

      const recList = document.getElementById('recList');
      if(!recCandidates.length){
        recList.innerHTML = `<div style="color:var(--muted); font-size:12.5px;">Tip: βάλε στα Notes ένα σταθερό όνομα (π.χ. Netflix) για καλύτερο detection.</div>`;
      } else {
        recList.innerHTML = recCandidates.slice(0,10).map(r=>{
          const conf = Math.min(100, Math.round((r.mcnt/monthsBack)*100));
          const dot = conf >= 80 ? '#49D9A1' : (conf >= 60 ? '#FFB020' : '#6D5EF7');
          return `
            <div class="lite">
              <div class="l">
                <div class="b" style="background:${dot}"></div>
                <div class="t">${escapeHtml(r.label)}</div>
              </div>
              <div class="r">${escapeHtml(_money(r.avg))} <em>${conf}%</em></div>
            </div>
          `;
        }).join('');
      }

      // ---------- Smart idea cards ----------
      const tips = [];

      if(topCats[0]){
        tips.push({ icon:'🏷️', title:`Top spending: ${topCats[0][0]}`, desc:`${_money(topCats[0][1])} αυτό το μήνα. Δοκίμασε cap 7 ημερών.` });
      }
      if(budTotal>0){
        if(usedPct >= 90) tips.push({ icon:'⚠️', title:'Budget pressure υψηλό', desc:`${usedPct.toFixed(0)}% used. Μείωσε 1–2 κατηγορίες ή αύξησε όρια.` });
        else if(usedPct >= 70) tips.push({ icon:'🟠', title:'Κοντά στο όριο', desc:`${usedPct.toFixed(0)}% used. Κράτα buffer για το υπόλοιπο μήνα.` });
        else tips.push({ icon:'✅', title:'Budgets σε καλή πορεία', desc:`${usedPct.toFixed(0)}% used. Αν θες, ανέβασε στόχο αποταμίευσης.` });
      }
      if(income>0){
        if(saveRate < 10) tips.push({ icon:'📉', title:'Χαμηλή αποταμίευση', desc:`Στόχευσε 10–20%. Τώρα είσαι ~${saveRate.toFixed(0)}%.` });
        else tips.push({ icon:'📈', title:'Καλή αποταμίευση', desc:`Είσαι ~${saveRate.toFixed(0)}%. Ανέβα σταδιακά +2%/μήνα.` });
      }
      if(recCandidates[0]){
        tips.push({ icon:'🔁', title:'Πιθανή συνδρομή', desc:`${recCandidates[0].label} ~${_money(recCandidates[0].avg)} (επαναλαμβάνεται).` });
      }
      if(isCurrent){
        tips.push({ icon:'🧭', title:'Forecast', desc:`Αν συνεχίσεις έτσι, projected expense ~${_money(projected)}.` });
      }

      const box = document.getElementById('smartIdeas');
      if(!tips.length){
        box.innerHTML = `
          <div class="msg">
            <div class="m-ic">✨</div>
            <div>
              <div class="m-t">No insights yet</div>
              <div class="m-s">Πρόσθεσε μερικές συναλλαγές για να εμφανιστούν τάσεις και προτάσεις.</div>
            </div>
          </div>
        `;
      } else {
        box.innerHTML = tips.slice(0,6).map(t=>`
          <div class="msg">
            <div class="m-ic">${escapeHtml(t.icon)}</div>
            <div>
              <div class="m-t">${escapeHtml(t.title)}</div>
              <div class="m-s">${escapeHtml(t.desc)}</div>
            </div>
          </div>
        `).join('');
      }

    }catch(e){
      if(typeof toast === 'function') toast('Insights error: ' + e.message);
      console.error(e);
    }
  }
</script>