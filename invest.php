<?php
declare(strict_types=1);
/**
 * invest.php (FULL)
 * View include for index.php
 *
 * Assumptions:
 * - index.php already includes this file and routes ?page=invest
 * - index.php exposes JS globals:
 *   window.API, window.apiGet, window.openSelectSheet, window.toast,
 *   window.state (includes state.month), window.monthEnd, window.fmtMoneyFromCents, window.escapeHtml
 * - api.php provides: action=market_data (as we added previously) and action=stats_month
 */
?>

<!-- INVEST -->
<style>
  /* Invest layout overrides only */
  #view-invest .inv-charts{
    display:grid;
    grid-template-columns: 1fr;
    gap:14px;
  }
  @media (min-width: 880px){
    #view-invest .inv-charts{
      grid-template-columns: 1fr 1fr; /* 2 per row */
    }
  }

  #view-invest .inv-bottom{
    display:grid;
    grid-template-columns: 1fr;
    gap:14px;
    margin-top:14px;
    align-items:start;
  }
  @media (min-width: 880px){
    #view-invest .inv-bottom{
      grid-template-columns: 1.05fr .95fr;
    }
  }

  /* small utility */
  #view-invest .muted{ color: var(--muted); }
  #view-invest .muted2{ color: var(--muted2); }
  #view-invest .tiny{ font-size: 12px; }
  #view-invest .chipsline{ display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
  #view-invest .pilltag{
    display:inline-flex; align-items:center; gap:8px; padding: 6px 10px;
    border: 1px solid var(--border); border-radius: 999px;
    background: var(--surface); color: var(--text); font-size: 12px;
    user-select:none;
  }
  #view-invest .pilltag.warn{ border-color: rgba(255,176,32,.40); background: rgba(255,176,32,.10); }
  #view-invest .pilltag.danger{ border-color: rgba(255,77,109,.40); background: rgba(255,77,109,.10); }

  /* watchlist editor rows */
  #view-invest .wl-row{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,.06);
  }
  html[data-theme="light"] #view-invest .wl-row{ border-bottom-color: rgba(10,15,25,.08); }
  #view-invest .wl-row:last-child{ border-bottom:none; }
  #view-invest .wl-left{ min-width:0; }
  #view-invest .wl-sym{ font-weight:950; letter-spacing:-.1px; }
  #view-invest .wl-name{ color:var(--muted); font-size:12.5px; margin-top:2px; }

  /* compare chart fixed height */
  #invCompareCanvasWrap{
    margin-top:12px;
  }
  #invCompareCanvasWrap canvas{ width:100%; }

  /* keep “No market data” box spanning grid */
  #view-invest .inv-spanall{ grid-column:1 / -1; }
</style>

<div id="view-invest" style="display:none;">

  <!-- TOP: CHARTS -->
  <div class="section-title">
    <span>Watchlist charts</span>
    <span class="pill" onclick="refreshInvest(true)">Refresh →</span>
  </div>

  <div id="invGrid" class="inv-charts"></div>

  <!-- BOTTOM: LEFT Invest controls + Right Top picks -->
  <div class="inv-bottom">

    <!-- LEFT: INVEST CONTROL CENTER -->
    <div>
      <div class="card hero">
        <div class="inner">
          <div class="card-header">
            <div class="card-title">Invest</div>
          </div>

          <div class="amount" style="font-size:26px; margin-top:4px;">
            Planner <span class="minor">budget → picks → allocation</span>
          </div>

          <div class="row" style="gap:10px;">
            <div class="field" style="flex:1; min-width:220px; margin-top:0;">
              <label>Budget (€)</label>
              <input id="invBudget" type="number" min="0" step="10" placeholder="e.g. 500" />
            </div>

            <div class="field" style="flex:1; min-width:220px; margin-top:0;">
              <label>Risk</label>
              <input type="hidden" id="invRisk" value="balanced" />
              <button type="button" class="selectbtn" onclick="openInvestRiskSelect()">
                <span class="label" id="invRiskLabel">Balanced</span>
                <span class="chev">⌄</span>
              </button>
            </div>

            <div class="field" style="flex:1; min-width:220px; margin-top:0;">
              <label>Horizon</label>
              <input type="hidden" id="invHorizon" value="3m" />
              <button type="button" class="selectbtn" onclick="openInvestHorizonSelect()">
                <span class="label" id="invHorizonLabel">3 months</span>
                <span class="chev">⌄</span>
              </button>
            </div>

            <button class="btn primary" style="flex:0 0 auto; min-width:160px; margin-top:0;" onclick="investAnalyze()">
              Analyze
            </button>
          </div>

          <div class="row" style="gap:10px; margin-top:12px;">
            <div class="field" style="flex:1; min-width:220px; margin-top:0;">
              <label>Allocation</label>
              <input type="hidden" id="invAlloc" value="60-30-10" />
              <button type="button" class="selectbtn" onclick="openInvestAllocSelect()">
                <span class="label" id="invAllocLabel">60 / 30 / 10</span>
                <span class="chev">⌄</span>
              </button>
            </div>

            <div class="field" style="flex:1; min-width:220px; margin-top:0;">
              <label>Suggested budget (from this month)</label>
              <input type="hidden" id="invSuggestMode" value="net_20" />
              <button type="button" class="selectbtn" onclick="openInvestSuggestSelect()">
                <span class="label" id="invSuggestLabel">20% of net balance</span>
                <span class="chev">⌄</span>
              </button>
            </div>

            <button class="btn" style="flex:0 0 auto; min-width:160px; margin-top:0;" onclick="applySuggestedBudget()">
              Apply suggestion
            </button>
          </div>

          <div style="margin-top:10px; color:var(--muted2); font-size:12px; line-height:1.35;">
            Uses daily prices (server-side). Recommendation is heuristic (momentum + volatility + drawdown), not financial advice.
          </div>

          <div class="chipsline" id="invHealthChips"></div>
        </div>
      </div>

      <!-- Portfolio plan -->
      <div class="section-title">
        <span>Portfolio plan</span>
        <span id="invPlanMeta" class="muted tiny">—</span>
      </div>

      <div class="card soft">
        <div class="list">
          <div class="list-head">
            <h3>Allocation</h3>
            <span id="invPlanCount">—</span>
          </div>
          <div id="invPlanList"></div>
        </div>
      </div>

      <!-- Compare -->
      <div class="section-title">
        <span>Compare</span>
        <span class="pill" onclick="openComparePicker()">Pick →</span>
      </div>

      <div class="card soft">
        <div class="inner" style="position:relative; z-index:1;">
          <div class="row" style="gap:10px;">
            <div class="pill" id="invCmpA" onclick="pickCompare('a')" role="button" tabindex="0">A: —</div>
            <div class="pill" id="invCmpB" onclick="pickCompare('b')" role="button" tabindex="0">B: —</div>
            <div class="pill" onclick="renderCompareChart()" role="button" tabindex="0">Update</div>
          </div>

          <div id="invCompareCanvasWrap">
            <canvas id="invCompareCanvas" height="180"></canvas>
          </div>

          <div style="margin-top:8px;" class="muted2 tiny">
            Normalized performance (start = 100) for selected horizon.
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div class="section-title">
        <span>Alerts</span>
        <span class="pill" onclick="openAlertsManager()">Manage →</span>
      </div>

      <div class="card soft">
        <div class="inner" style="position:relative; z-index:1;">
          <div class="muted tiny" id="invAlertSummary">—</div>
          <div style="margin-top:10px;" class="chipsline" id="invAlertChips"></div>
          <div style="margin-top:8px;" class="muted2 tiny">
            Alerts are local (saved in localStorage). Trigger check runs when Invest loads/refreshed.
          </div>
        </div>
      </div>

      <!-- Watchlist editor -->
      <div class="section-title">
        <span>Watchlist</span>
        <span class="pill" onclick="openWatchlistSheet()">Edit →</span>
      </div>

      <div class="card soft">
        <div class="inner" style="position:relative; z-index:1;">
          <div class="muted tiny">Symbols are from Stooq (e.g. AAPL.US). Stored locally.</div>
          <div class="chipsline" style="margin-top:10px;">
            <span class="chipbtn" onclick="applyPreset('bigtech')">🚀 Big Tech</span>
            <span class="chipbtn" onclick="applyPreset('etfs')">📦 ETFs</span>
            <span class="chipbtn" onclick="applyPreset('dividend')">💵 Dividend-ish</span>
            <span class="chipbtn" onclick="resetWatchlistDefault()">↩︎ Reset default</span>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: TOP PICKS -->
    <div>
      <div class="section-title">
        <span>Top picks</span>
        <span id="invRecMeta" class="muted tiny">—</span>
      </div>

      <div class="card soft">
        <div class="list">
          <div class="list-head">
            <h3>Recommended</h3>
            <span id="invRecCount">—</span>
          </div>
          <div id="invRecList"></div>
        </div>
      </div>

      <div style="margin-top:14px;" class="card soft">
        <div class="inner" style="position:relative; z-index:1;">
          <div style="font-weight:950; letter-spacing:-.2px;">Risk guardrails</div>
          <div class="muted tiny" style="margin-top:8px; line-height:1.45;">
            Picks are scored using: momentum (horizon), volatility, and max drawdown.
            Conservative risk profile penalizes volatility and drawdown more.
          </div>
          <div class="chipsline" id="invGuardrailChips" style="margin-top:10px;"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Chart.js (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
/* =========================
   INVEST (client) — FULL
   ========================= */

// ---------- Storage keys ----------
const INV_KEYS = {
  WATCH: 'fin_inv_watch_v1',
  BUDGET: 'fin_inv_budget_v1',
  RISK: 'fin_inv_risk_v1',
  HORIZON: 'fin_inv_horizon_v1',
  ALLOC: 'fin_inv_alloc_v1',
  SUGGEST: 'fin_inv_suggest_v1',
  ALERTS: 'fin_inv_alerts_v1',
  CMP: 'fin_inv_compare_v1'
};

// ---------- Defaults ----------
const INVEST_DEFAULT_WATCH = [
  { s:'AAPL.US',  n:'Apple' },
  { s:'MSFT.US',  n:'Microsoft' },
  { s:'NVDA.US',  n:'NVIDIA' },
  { s:'AMZN.US',  n:'Amazon' },
  { s:'GOOGL.US', n:'Alphabet' },
  { s:'META.US',  n:'Meta' },
  { s:'TSLA.US',  n:'Tesla' },
  { s:'JPM.US',   n:'JPMorgan' },
  { s:'V.US',     n:'Visa' },
  { s:'KO.US',    n:'Coca-Cola' },
  { s:'PEP.US',   n:'PepsiCo' },
  { s:'DIS.US',   n:'Disney' },
  { s:'AMD.US',   n:'AMD' },
  { s:'SPY.US',   n:'S&P 500 ETF' },
  { s:'QQQ.US',   n:'Nasdaq 100 ETF' }
];

const INVEST = {
  watch: [],
  charts: new Map(),   // symbol -> Chart instance
  data: null,          // { items:[{symbol,last_close,series:[{d,c}]}] }
  statsMonth: null,    // {income/expense/balance}
  compare: { a:'', b:'' },
  compareChart: null
};

// ---------- Helpers ----------
function invLoadJSON(key, fallback){
  try{
    const raw = localStorage.getItem(key);
    if(!raw) return fallback;
    return JSON.parse(raw);
  }catch(e){ return fallback; }
}
function invSaveJSON(key, value){
  try{ localStorage.setItem(key, JSON.stringify(value)); }catch(e){}
}
function invLoadString(key, fallback){
  try{
    const v = localStorage.getItem(key);
    return (v === null || v === undefined || v === '') ? fallback : v;
  }catch(e){ return fallback; }
}
function invSaveString(key, value){
  try{ localStorage.setItem(key, String(value)); }catch(e){}
}
function clamp(n, a, b){ return Math.max(a, Math.min(b, n)); }

// ---------- Init UI defaults ----------
(function initInvestDefaults(){
  // watchlist
  const savedWatch = invLoadJSON(INV_KEYS.WATCH, null);
  INVEST.watch = Array.isArray(savedWatch) && savedWatch.length
    ? sanitizeWatchlist(savedWatch)
    : INVEST_DEFAULT_WATCH.slice();

  // budget/risk/horizon/alloc/suggest
  const bud = invLoadString(INV_KEYS.BUDGET, '');
  if(bud) document.getElementById('invBudget').value = bud;

  const risk = invLoadString(INV_KEYS.RISK, 'balanced');
  document.getElementById('invRisk').value = risk;
  document.getElementById('invRiskLabel').textContent = riskLabel(risk);

  const hz = invLoadString(INV_KEYS.HORIZON, '3m');
  document.getElementById('invHorizon').value = hz;
  document.getElementById('invHorizonLabel').textContent = horizonLabel(hz);

  const alloc = invLoadString(INV_KEYS.ALLOC, '60-30-10');
  document.getElementById('invAlloc').value = alloc;
  document.getElementById('invAllocLabel').textContent = allocLabel(alloc);

  const sug = invLoadString(INV_KEYS.SUGGEST, 'net_20');
  document.getElementById('invSuggestMode').value = sug;
  document.getElementById('invSuggestLabel').textContent = suggestLabel(sug);

  // compare
  const cmp = invLoadJSON(INV_KEYS.CMP, {a:'', b:''});
  if(cmp && typeof cmp === 'object'){
    INVEST.compare.a = String(cmp.a || '');
    INVEST.compare.b = String(cmp.b || '');
  }
  updateComparePills();

  // alerts summary render
  renderAlertSummary();

})();

// ---------- Select UIs ----------
function riskLabel(v){
  return v === 'conservative' ? 'Conservative' : (v === 'growth' ? 'Growth' : 'Balanced');
}
function horizonLabel(v){
  return v === '1m' ? '1 month' : (v === '6m' ? '6 months' : (v === '1y' ? '1 year' : '3 months'));
}
function allocLabel(v){
  if(v === 'single') return 'Single best';
  if(v === 'equal') return 'Equal weight';
  return '60 / 30 / 10';
}
function suggestLabel(v){
  // net_20, net_10, income_10, income_20, fixed_500
  const map = {
    'net_10':'10% of net balance',
    'net_20':'20% of net balance',
    'income_10':'10% of income',
    'income_20':'20% of income',
    'fixed_500':'Fixed €500'
  };
  return map[v] || '20% of net balance';
}

function openInvestRiskSelect(){
  const items = [
    { value:'conservative', label:'Conservative', sub:'Lower volatility bias', icon:'🛡️' },
    { value:'balanced',     label:'Balanced',     sub:'Return vs risk',        icon:'⚖️' },
    { value:'growth',       label:'Growth',       sub:'Higher momentum bias',  icon:'🚀' }
  ];
  openSelectSheet({
    title: 'Risk profile',
    items,
    value: document.getElementById('invRisk').value || 'balanced',
    onPick: (val)=>{
      document.getElementById('invRisk').value = val;
      document.getElementById('invRiskLabel').textContent = riskLabel(val);
      invSaveString(INV_KEYS.RISK, val);
      investAnalyze();
    }
  });
}
function openInvestHorizonSelect(){
  const items = [
    { value:'1m', label:'1 month', sub:'~21 trading days', icon:'🗓️' },
    { value:'3m', label:'3 months', sub:'~63 trading days', icon:'📅' },
    { value:'6m', label:'6 months', sub:'~126 trading days', icon:'🗓️' },
    { value:'1y', label:'1 year', sub:'~252 trading days', icon:'🗓️' }
  ];
  openSelectSheet({
    title: 'Horizon',
    items,
    value: document.getElementById('invHorizon').value || '3m',
    onPick: (val)=>{
      document.getElementById('invHorizon').value = val;
      document.getElementById('invHorizonLabel').textContent = horizonLabel(val);
      invSaveString(INV_KEYS.HORIZON, val);
      // refresh compare + analysis on horizon change
      investAnalyze();
      renderCompareChart();
    }
  });
}
function openInvestAllocSelect(){
  const items = [
    { value:'single', label:'Single best', sub:'100% in #1 pick', icon:'🎯' },
    { value:'60-30-10', label:'60 / 30 / 10', sub:'Top 3 weighted', icon:'⚖️' },
    { value:'equal', label:'Equal weight', sub:'Split across top 3', icon:'🟰' }
  ];
  openSelectSheet({
    title: 'Allocation',
    items,
    value: document.getElementById('invAlloc').value || '60-30-10',
    onPick: (val)=>{
      document.getElementById('invAlloc').value = val;
      document.getElementById('invAllocLabel').textContent = allocLabel(val);
      invSaveString(INV_KEYS.ALLOC, val);
      investAnalyze();
    }
  });
}
function openInvestSuggestSelect(){
  const items = [
    { value:'net_10', label:'10% of net balance', sub:'(income - expense) * 10%', icon:'🧮' },
    { value:'net_20', label:'20% of net balance', sub:'(income - expense) * 20%', icon:'🧮' },
    { value:'income_10', label:'10% of income', sub:'income * 10%', icon:'💰' },
    { value:'income_20', label:'20% of income', sub:'income * 20%', icon:'💰' },
    { value:'fixed_500', label:'Fixed €500', sub:'static suggestion', icon:'📌' }
  ];
  openSelectSheet({
    title: 'Suggested budget',
    items,
    value: document.getElementById('invSuggestMode').value || 'net_20',
    onPick: (val)=>{
      document.getElementById('invSuggestMode').value = val;
      document.getElementById('invSuggestLabel').textContent = suggestLabel(val);
      invSaveString(INV_KEYS.SUGGEST, val);
      renderHealthChips(); // update suggestion chip
    }
  });
}

// ---------- Budget suggestion from your monthly stats ----------
async function loadMonthStats(){
  try{
    if(!window.state || !window.state.month) return null;
    const month = window.state.month;
    const s = await apiGet(`${API}?action=stats_month&month=${encodeURIComponent(month)}`);
    INVEST.statsMonth = s;
    return s;
  }catch(e){
    INVEST.statsMonth = null;
    return null;
  }
}

function computeSuggestedBudget(){
  const mode = document.getElementById('invSuggestMode').value || 'net_20';
  const stats = INVEST.statsMonth;
  const income = stats ? (parseInt(stats.income_cents||0,10)||0) : 0;
  const expense = stats ? (parseInt(stats.expense_cents||0,10)||0) : 0;
  const net = income - expense;

  if(mode === 'fixed_500') return 500;

  if(mode.startsWith('income_')){
    const pct = mode.endsWith('_20') ? 0.20 : 0.10;
    return Math.max(0, (income/100) * pct);
  }
  // net_10/net_20
  const pct = mode.endsWith('_20') ? 0.20 : 0.10;
  return Math.max(0, (net/100) * pct);
}

async function applySuggestedBudget(){
  if(!INVEST.statsMonth) await loadMonthStats();
  const sug = computeSuggestedBudget();
  if(!isFinite(sug) || sug <= 0){
    toast('No suggestion available (need some income/expense this month)');
    return;
  }
  document.getElementById('invBudget').value = String(Math.round(sug));
  invSaveString(INV_KEYS.BUDGET, String(Math.round(sug)));
  toast('Applied suggested budget');
  investAnalyze();
}

function renderHealthChips(){
  const chips = document.getElementById('invHealthChips');
  if(!chips) return;

  const stats = INVEST.statsMonth;
  if(!stats){
    chips.innerHTML = `<span class="pilltag">📶 Month stats: —</span>`;
    return;
  }

  const income = parseInt(stats.income_cents||0,10)||0;
  const expense = parseInt(stats.expense_cents||0,10)||0;
  const net = income - expense;

  const sug = computeSuggestedBudget();
  const sugTxt = `Suggested ≈ €${Math.round(sug)}`;

  chips.innerHTML = `
    <span class="pilltag">💰 Income ${escapeHtml(fmtMoneyFromCents(income))}</span>
    <span class="pilltag">💸 Expense ${escapeHtml(fmtMoneyFromCents(expense))}</span>
    <span class="pilltag ${net<0?'danger':''}">🧾 Net ${escapeHtml(fmtMoneyFromCents(net))}</span>
    <span class="pilltag">✨ ${escapeHtml(sugTxt)}</span>
  `;
}

// ---------- Watchlist ----------
function sanitizeWatchlist(list){
  const out = [];
  const seen = new Set();
  for(const it of list){
    const s = String(it.s || it.symbol || '').trim().toUpperCase();
    if(!/^[A-Z0-9.\-]{1,20}$/.test(s)) continue;
    if(seen.has(s)) continue;
    seen.add(s);
    out.push({ s, n: String(it.n || it.name || s).trim() || s });
    if(out.length >= 20) break; // cap
  }
  return out.length ? out : INVEST_DEFAULT_WATCH.slice();
}
function saveWatchlist(){
  invSaveJSON(INV_KEYS.WATCH, INVEST.watch);
}

function applyPreset(kind){
  let list = [];
  if(kind === 'bigtech'){
    list = [
      { s:'AAPL.US', n:'Apple' },
      { s:'MSFT.US', n:'Microsoft' },
      { s:'NVDA.US', n:'NVIDIA' },
      { s:'AMZN.US', n:'Amazon' },
      { s:'GOOGL.US', n:'Alphabet' },
      { s:'META.US', n:'Meta' },
      { s:'TSLA.US', n:'Tesla' },
      { s:'AMD.US', n:'AMD' },
      { s:'NFLX.US', n:'Netflix' },
      { s:'INTC.US', n:'Intel' },
      { s:'CRM.US', n:'Salesforce' },
      { s:'ORCL.US', n:'Oracle' }
    ];
  } else if(kind === 'etfs'){
    list = [
      { s:'SPY.US', n:'S&P 500 ETF' },
      { s:'QQQ.US', n:'Nasdaq 100 ETF' },
      { s:'DIA.US', n:'Dow Jones ETF' },
      { s:'IWM.US', n:'Russell 2000 ETF' },
      { s:'VTI.US', n:'Total Market ETF' },
      { s:'VEA.US', n:'Developed Markets ETF' },
      { s:'VWO.US', n:'Emerging Markets ETF' },
      { s:'GLD.US', n:'Gold ETF' },
      { s:'TLT.US', n:'20Y Treasury ETF' },
      { s:'LQD.US', n:'Investment Grade Bonds ETF' },
      { s:'XLF.US', n:'Financials Sector ETF' },
      { s:'XLK.US', n:'Technology Sector ETF' }
    ];
  } else if(kind === 'dividend'){
    list = [
      { s:'KO.US', n:'Coca-Cola' },
      { s:'PEP.US', n:'PepsiCo' },
      { s:'PG.US', n:'Procter & Gamble' },
      { s:'JNJ.US', n:'Johnson & Johnson' },
      { s:'XOM.US', n:'Exxon Mobil' },
      { s:'CVX.US', n:'Chevron' },
      { s:'MCD.US', n:'McDonald’s' },
      { s:'VZ.US', n:'Verizon' },
      { s:'T.US', n:'AT&T' },
      { s:'JPM.US', n:'JPMorgan' },
      { s:'V.US', n:'Visa' },
      { s:'HD.US', n:'Home Depot' }
    ];
  } else {
    return;
  }
  INVEST.watch = sanitizeWatchlist(list);
  saveWatchlist();
  toast('Watchlist preset applied');
  refreshInvest(true);
}

function resetWatchlistDefault(){
  INVEST.watch = INVEST_DEFAULT_WATCH.slice();
  saveWatchlist();
  toast('Watchlist reset');
  refreshInvest(true);
}

// Watchlist sheet (uses existing generic sheetSelect as a quick “editor” flow)
// We’ll reuse openSelectSheet for delete and add via prompt to keep it lightweight.
function openWatchlistSheet(){
  const items = INVEST.watch.map(w=>({
    value: w.s,
    label: `${w.s}`,
    sub: w.n,
    icon: '⭐'
  }));
  openSelectSheet({
    title: 'Watchlist (tap to remove)',
    items: items.concat([{value:'__ADD__', label:'Add symbol…', sub:'e.g. AAPL.US', icon:'➕'}]),
    value: '',
    onPick: async (val)=>{
      if(val === '__ADD__'){
        const sym = prompt('Symbol (Stooq), e.g. AAPL.US:');
        if(!sym) return;
        const s = sym.trim().toUpperCase();
        if(!/^[A-Z0-9.\-]{1,20}$/.test(s)) return toast('Invalid symbol');
        if(INVEST.watch.some(x=>x.s===s)) return toast('Already in watchlist');

        const name = prompt('Name (optional):') || s;
        INVEST.watch.unshift({s, n: String(name).trim() || s});
        INVEST.watch = sanitizeWatchlist(INVEST.watch);
        saveWatchlist();
        toast('Added');
        refreshInvest(true);
        return;
      }

      const idx = INVEST.watch.findIndex(x=>x.s===val);
      if(idx >= 0){
        if(!confirm(`Remove ${val} from watchlist?`)) return;
        INVEST.watch.splice(idx,1);
        if(!INVEST.watch.length) INVEST.watch = INVEST_DEFAULT_WATCH.slice();
        saveWatchlist();
        toast('Removed');
        refreshInvest(true);
      }
    }
  });
}

// ---------- Metrics & scoring ----------
function mean(a){ return a.length ? a.reduce((s,x)=>s+x,0)/a.length : 0; }
function stddev(a){
  if(a.length < 2) return 0;
  const m = mean(a);
  const v = a.reduce((s,x)=>s+(x-m)*(x-m),0)/(a.length-1);
  return Math.sqrt(v);
}

function horizonDays(h){
  if(h === '1m') return 21;
  if(h === '6m') return 126;
  if(h === '1y') return 252;
  return 63; // 3m
}

function calcMetrics(series, horizon){
  // series: [{d:'YYYY-MM-DD', c:number}] ascending by date
  const closes = series.map(x=>x.c).filter(v=>isFinite(v) && v>0);
  const n = closes.length;

  const rets = [];
  for(let i=1;i<n;i++) rets.push((closes[i]/closes[i-1]) - 1);

  const vol = stddev(rets); // daily
  const hd = horizonDays(horizon);
  const startIdx = Math.max(0, n - (hd+1));
  const mom = (closes[n-1] / closes[startIdx]) - 1;

  let peak = closes[0], maxdd = 0;
  for(const v of closes){
    if(v > peak) peak = v;
    const dd = (peak - v) / peak;
    if(dd > maxdd) maxdd = dd;
  }

  // scale sharpe-like by sqrt(hd)
  const sharpeLike = vol > 0 ? mom / (vol * Math.sqrt(hd)) : mom;

  return { vol, mom, maxdd, sharpeLike };
}

function scoreStock(m, risk){
  if(risk === 'conservative'){
    return (m.sharpeLike * 1.25) - (m.maxdd * 1.2) - (m.vol * 0.9);
  }
  if(risk === 'growth'){
    return (m.mom * 1.6) + (m.sharpeLike * 0.85) - (m.maxdd * 0.35);
  }
  // balanced
  return (m.sharpeLike * 1.35) + (m.mom * 0.75) - (m.maxdd * 0.75) - (m.vol * 0.35);
}

function riskBadges(m){
  const out = [];
  // heuristics thresholds (daily vol)
  if(m.vol > 0.03) out.push({t:'High volatility', kind:'warn'});
  if(m.vol > 0.045) out.push({t:'Very volatile', kind:'danger'});
  if(m.maxdd > 0.25) out.push({t:'Large drawdown', kind:'warn'});
  if(m.maxdd > 0.40) out.push({t:'Severe drawdown', kind:'danger'});
  return out;
}

// ---------- Analysis + rendering ----------
function investAnalyze(){
  const budEl = document.getElementById('invBudget');
  const budget = parseFloat((budEl.value || '').toString());
  if(!isFinite(budget) || budget <= 0){
    toast('Βάλε budget > 0');
    return;
  }
  invSaveString(INV_KEYS.BUDGET, String(budget));

  if(!INVEST.data || !INVEST.data.items){
    toast('No market data yet');
    return;
  }

  const risk = document.getElementById('invRisk').value || 'balanced';
  const hz = document.getElementById('invHorizon').value || '3m';
  const alloc = document.getElementById('invAlloc').value || '60-30-10';

  const scored = INVEST.data.items
    .filter(x => x && x.series && x.series.length >= 60 && x.last_close > 0)
    .map(x => {
      const m = calcMetrics(x.series, hz);
      const score = scoreStock(m, risk);
      return { ...x, metrics: m, score };
    })
    .sort((a,b)=> b.score - a.score);

  const picks = scored.slice(0, 3);
  renderInvestRecommendations(picks, budget, risk, hz);
  renderPortfolioPlan(picks, budget, alloc);
  renderGuardrailChips(picks, risk);
  renderCompareChart(); // if compare chosen, update with current horizon

  // update meta
  const meta = document.getElementById('invRecMeta');
  meta.textContent = `Risk: ${riskLabel(risk)} • Horizon: ${horizonLabel(hz)} • Budget: €${budget.toFixed(0)}`;

  const planMeta = document.getElementById('invPlanMeta');
  planMeta.textContent = `Allocation: ${allocLabel(alloc)} • Horizon: ${horizonLabel(hz)}`;
}

function renderGuardrailChips(picks, risk){
  const wrap = document.getElementById('invGuardrailChips');
  if(!wrap) return;

  if(!picks || !picks.length){
    wrap.innerHTML = `<span class="pilltag">—</span>`;
    return;
  }

  // show badges for top pick
  const top = picks[0];
  const badges = riskBadges(top.metrics);
  const base = [`<span class="pilltag">Top pick: ${escapeHtml(top.symbol)}</span>`,
                `<span class="pilltag">Profile: ${escapeHtml(riskLabel(risk))}</span>`];

  const extra = badges.map(b=>`<span class="pilltag ${b.kind}">${b.kind==='danger'?'⛔':'⚠️'} ${escapeHtml(b.t)}</span>`);
  wrap.innerHTML = base.concat(extra).join('') || `<span class="pilltag">—</span>`;
}

function renderInvestRecommendations(picks, budget, risk, hz){
  const wrap = document.getElementById('invRecList');
  document.getElementById('invRecCount').textContent = picks.length ? `${picks.length} picks` : '—';

  if(!picks.length){
    wrap.innerHTML = `
      <div class="item" style="cursor:default">
        <div class="left">
          <div class="ic">💹</div>
          <div class="meta">
            <div class="t">No recommendations</div>
            <div class="s">Market data unavailable</div>
          </div>
        </div>
        <div class="right">
          <div class="amt">—</div>
          <div class="date">—</div>
        </div>
      </div>`;
    return;
  }

  wrap.innerHTML = picks.map(p=>{
    const price = p.last_close;
    const maxShares = Math.max(0, Math.floor(budget / price));
    const est = maxShares * price;

    const cls = (p.metrics.mom > 0) ? 'income' : 'expense';
    const momPct = (p.metrics.mom * 100).toFixed(1);
    const volPct = (p.metrics.vol * 100).toFixed(1);
    const ddPct  = (p.metrics.maxdd * 100).toFixed(1);

    const badges = riskBadges(p.metrics).slice(0,2)
      .map(b=>`<span class="pilltag ${b.kind}">${b.kind==='danger'?'⛔':'⚠️'} ${escapeHtml(b.t)}</span>`)
      .join('');

    return `
      <div class="item" style="cursor:default">
        <div class="left">
          <div class="ic">📈</div>
          <div class="meta">
            <div class="t">${escapeHtml(p.name || p.symbol)}</div>
            <div class="s">${escapeHtml(p.symbol)} • last €${price.toFixed(2)} • score ${p.score.toFixed(2)}</div>
            <div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:8px;">${badges || ''}</div>
          </div>
        </div>
        <div class="right">
          <div class="amt ${cls}">${momPct}%</div>
          <div class="date">${escapeHtml(horizonLabel(hz))} momentum</div>
          <div class="date">vol ${volPct}% • dd ${ddPct}%</div>
          <div class="date">≈ ${maxShares} shares (€${est.toFixed(0)})</div>
        </div>
      </div>
    `;
  }).join('');
}

// ---------- Portfolio allocation plan ----------
function renderPortfolioPlan(picks, budget, alloc){
  const wrap = document.getElementById('invPlanList');
  const cnt = document.getElementById('invPlanCount');

  if(!picks || !picks.length){
    cnt.textContent = '—';
    wrap.innerHTML = `
      <div class="item" style="cursor:default">
        <div class="left">
          <div class="ic">🧩</div>
          <div class="meta">
            <div class="t">No plan yet</div>
            <div class="s">Run Analyze</div>
          </div>
        </div>
        <div class="right"><div class="amt">—</div><div class="date">—</div></div>
      </div>`;
    return;
  }

  let weights = [];
  if(alloc === 'single'){
    weights = [1,0,0];
  } else if(alloc === 'equal'){
    weights = [1/3, 1/3, 1/3];
  } else {
    weights = [0.60, 0.30, 0.10];
  }

  // compute integer shares with budget constraint, keep leftover
  let remaining = budget;
  const rows = picks.map((p, idx)=>{
    const w = weights[idx] || 0;
    const target = remaining <= 0 ? 0 : (budget * w);
    const price = p.last_close;
    const shares = (price > 0) ? Math.floor(target / price) : 0;
    const cost = shares * price;
    remaining -= cost;
    return { p, w, shares, cost, price };
  });

  // use remaining to buy extra share of best pick if possible (greedy)
  const best = rows[0];
  if(best && best.price > 0){
    while(remaining >= best.price){
      best.shares += 1;
      best.cost += best.price;
      remaining -= best.price;
      // avoid endless due to float issues
      if(best.shares > 100000) break;
    }
  }

  const spent = rows.reduce((s,r)=>s+r.cost,0);
  cnt.textContent = `${rows.length} lines`;

  wrap.innerHTML = rows.map((r, idx)=>{
    const pct = Math.round((r.cost / (budget||1)) * 100);
    return `
      <div class="item" style="cursor:default">
        <div class="left">
          <div class="ic">${idx===0?'🥇':(idx===1?'🥈':'🥉')}</div>
          <div class="meta">
            <div class="t">${escapeHtml(r.p.name || r.p.symbol)}</div>
            <div class="s">${escapeHtml(r.p.symbol)} • €${r.price.toFixed(2)} • weight ${(r.w*100).toFixed(0)}%</div>
          </div>
        </div>
        <div class="right">
          <div class="amt">${r.shares} sh</div>
          <div class="date">€${r.cost.toFixed(0)} (${isFinite(pct)?pct:0}%)</div>
        </div>
      </div>
    `;
  }).join('') + `
    <div class="item" style="cursor:default">
      <div class="left">
        <div class="ic">💼</div>
        <div class="meta">
          <div class="t">Summary</div>
          <div class="s">Spent €${spent.toFixed(0)} • Cash left €${Math.max(0, remaining).toFixed(0)}</div>
        </div>
      </div>
      <div class="right">
        <div class="amt">—</div>
        <div class="date">—</div>
      </div>
    </div>
  `;
}

// ---------- Charts (watchlist) ----------
function renderInvestGrid(items){
  const grid = document.getElementById('invGrid');

  // destroy old charts
  for(const ch of INVEST.charts.values()){
    try{ ch.destroy(); }catch(e){}
  }
  INVEST.charts.clear();

  if(!items || !items.length){
    grid.innerHTML = `
      <div class="card soft inv-spanall">
        <div class="inner" style="position:relative; z-index:1;">
          <div style="font-weight:950; letter-spacing:-.2px;">No market data</div>
          <div class="muted tiny" style="margin-top:6px;">Try Refresh</div>
        </div>
      </div>`;
    return;
  }

  grid.innerHTML = items.map(it=>{
    const id = 'cv_' + it.symbol.replaceAll('.','_').replaceAll('-','_');
    return `
      <div class="card soft">
        <div class="inner" style="position:relative; z-index:1;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
            <div>
              <div style="font-weight:950; letter-spacing:-.2px;">${escapeHtml(it.name || it.symbol)}</div>
              <div style="color:var(--muted); font-size:12.5px; margin-top:2px;">
                ${escapeHtml(it.symbol)} • last ${isFinite(it.last_close) ? ('€' + it.last_close.toFixed(2)) : '—'}
              </div>
            </div>
            <div class="pill" style="cursor:default;">${it.series?.length ? `${it.series.length} pts` : '—'}</div>
          </div>

          <div style="margin-top:12px;">
            <canvas id="${id}" height="140"></canvas>
          </div>

          <div style="margin-top:8px; color:var(--muted2); font-size:12px;">
            Daily close
          </div>
        </div>
      </div>
    `;
  }).join('');

  // build charts
  items.forEach(it=>{
    const id = 'cv_' + it.symbol.replaceAll('.','_').replaceAll('-','_');
    const el = document.getElementById(id);
    if(!el) return;

    const labels = (it.series || []).map(x=>x.d);
    const data = (it.series || []).map(x=>x.c);

    const ch = new Chart(el.getContext('2d'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: it.symbol,
          data,
          tension: 0.25,
          pointRadius: 0,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { mode: 'index', intersect: false }
        },
        scales: {
          x: { display: false },
          y: { display: true, ticks: { maxTicksLimit: 4 } }
        }
      }
    });

    INVEST.charts.set(it.symbol, ch);
  });
}

// ---------- Compare ----------
function updateComparePills(){
  const a = document.getElementById('invCmpA');
  const b = document.getElementById('invCmpB');
  a.textContent = `A: ${INVEST.compare.a || '—'}`;
  b.textContent = `B: ${INVEST.compare.b || '—'}`;
}
function openComparePicker(){
  // quick choose two most recent watchlist
  const list = INVEST.watch.map(w=>({ value:w.s, label:w.s, sub:w.n, icon:'📌'}));
  openSelectSheet({
    title:'Pick compare A',
    items:list,
    value: INVEST.compare.a || '',
    onPick:(val)=>{
      INVEST.compare.a = val;
      invSaveJSON(INV_KEYS.CMP, INVEST.compare);
      updateComparePills();
      openSelectSheet({
        title:'Pick compare B',
        items:list,
        value: INVEST.compare.b || '',
        onPick:(val2)=>{
          INVEST.compare.b = val2;
          invSaveJSON(INV_KEYS.CMP, INVEST.compare);
          updateComparePills();
          renderCompareChart();
        }
      });
    }
  });
}
function pickCompare(which){
  const list = INVEST.watch.map(w=>({ value:w.s, label:w.s, sub:w.n, icon:'📌'}));
  openSelectSheet({
    title: which === 'a' ? 'Pick A' : 'Pick B',
    items:list,
    value: which === 'a' ? (INVEST.compare.a||'') : (INVEST.compare.b||''),
    onPick:(val)=>{
      if(which==='a') INVEST.compare.a = val;
      else INVEST.compare.b = val;
      invSaveJSON(INV_KEYS.CMP, INVEST.compare);
      updateComparePills();
      renderCompareChart();
    }
  });
}

function seriesForSymbol(sym){
  if(!INVEST.data || !INVEST.data.items) return null;
  const it = INVEST.data.items.find(x=>String(x.symbol)===String(sym));
  return it ? it.series : null;
}

function normalizeSeries(series, hz){
  if(!series || !series.length) return { labels:[], vals:[] };
  const hd = horizonDays(hz);
  const cut = series.slice(Math.max(0, series.length - (hd+1)));
  if(!cut.length) return { labels:[], vals:[] };
  const base = cut[0].c;
  const labels = cut.map(x=>x.d);
  const vals = cut.map(x=> (base>0 ? (x.c/base)*100 : 100));
  return { labels, vals };
}

function renderCompareChart(){
  const canvas = document.getElementById('invCompareCanvas');
  if(!canvas) return;

  const hz = document.getElementById('invHorizon').value || '3m';
  const aSym = INVEST.compare.a;
  const bSym = INVEST.compare.b;

  // destroy old
  if(INVEST.compareChart){
    try{ INVEST.compareChart.destroy(); }catch(e){}
    INVEST.compareChart = null;
  }

  if(!aSym || !bSym || aSym === bSym){
    INVEST.compareChart = new Chart(canvas.getContext('2d'), {
      type: 'line',
      data: { labels:[], datasets:[] },
      options: { plugins:{ legend:{ display:false } } }
    });
    return;
  }

  const aSeries = seriesForSymbol(aSym);
  const bSeries = seriesForSymbol(bSym);
  const A = normalizeSeries(aSeries, hz);
  const B = normalizeSeries(bSeries, hz);

  // align by labels intersection (simple: use shorter labels, pick matching indices)
  const labelSet = new Set(B.labels);
  const labels = A.labels.filter(d=>labelSet.has(d));
  const aMap = new Map(A.labels.map((d,i)=>[d, A.vals[i]]));
  const bMap = new Map(B.labels.map((d,i)=>[d, B.vals[i]]));
  const aVals = labels.map(d=>aMap.get(d));
  const bVals = labels.map(d=>bMap.get(d));

  INVEST.compareChart = new Chart(canvas.getContext('2d'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: aSym, data: aVals, tension:0.25, pointRadius:0, borderWidth:2 },
        { label: bSym, data: bVals, tension:0.25, pointRadius:0, borderWidth:2 }
      ]
    },
    options: {
      responsive:true,
      maintainAspectRatio:false,
      plugins:{
        legend:{ display:true },
        tooltip:{ mode:'index', intersect:false }
      },
      scales:{
        x:{ display:false },
        y:{ display:true, ticks:{ maxTicksLimit:4 } }
      }
    }
  });
}

// ---------- Alerts ----------
function getAlerts(){
  const a = invLoadJSON(INV_KEYS.ALERTS, []);
  return Array.isArray(a) ? a : [];
}
function setAlerts(a){
  invSaveJSON(INV_KEYS.ALERTS, a);
  renderAlertSummary();
}

function renderAlertSummary(){
  const summary = document.getElementById('invAlertSummary');
  const chips = document.getElementById('invAlertChips');
  if(!summary || !chips) return;

  const alerts = getAlerts();
  if(!alerts.length){
    summary.textContent = 'No alerts set.';
    chips.innerHTML = `<span class="pilltag">🔔 Add an alert</span>`;
    return;
  }
  summary.textContent = `${alerts.length} alert(s) active`;
  chips.innerHTML = alerts.slice(0,6).map(a=>{
    const t = `${a.symbol} ${a.type==='drop'?'≤':'≥'} ${a.pct}%`;
    return `<span class="pilltag">${escapeHtml(t)}</span>`;
  }).join('') + (alerts.length>6 ? `<span class="pilltag">+${alerts.length-6}</span>` : '');
}

function openAlertsManager(){
  const items = [
    { value:'__ADD__', label:'Add alert…', sub:'Pick symbol and threshold', icon:'➕' },
    { value:'__CLEAR__', label:'Clear all', sub:'Remove all alerts', icon:'🧹' }
  ].concat(getAlerts().map((a, idx)=>({
    value:`__DEL__${idx}`,
    label:`${a.symbol} ${a.type==='drop'?'drop':'rise'} ${a.pct}%`,
    sub:`baseline €${(a.base||0).toFixed(2)}`,
    icon:'🔔'
  })));

  openSelectSheet({
    title:'Alerts (tap to remove)',
    items,
    value:'',
    onPick:(val)=>{
      if(val === '__CLEAR__'){
        if(!confirm('Clear all alerts?')) return;
        setAlerts([]);
        toast('Cleared alerts');
        return;
      }
      if(val === '__ADD__'){
        const symItems = INVEST.watch.map(w=>({ value:w.s, label:w.s, sub:w.n, icon:'📌'}));
        openSelectSheet({
          title:'Pick symbol',
          items:symItems,
          value:'',
          onPick:(sym)=>{
            const type = (prompt('Type "drop" for -% or "rise" for +% (default drop):') || 'drop').trim().toLowerCase() === 'rise' ? 'rise' : 'drop';
            const pct = parseFloat((prompt('Percent threshold (e.g. 5):') || '5').trim());
            if(!isFinite(pct) || pct <= 0) return toast('Invalid percent');

            // baseline: current last_close if available
            const it = INVEST.data?.items?.find(x=>x.symbol===sym);
            const base = it && it.last_close ? it.last_close : 0;

            const alerts = getAlerts();
            alerts.push({ symbol:sym, type, pct: clamp(pct, 0.5, 50), base, armed:true, last_trigger:0 });
            setAlerts(alerts);
            toast('Alert added');
            return;
          }
        });
        return;
      }
      if(val.startsWith('__DEL__')){
        const idx = parseInt(val.replace('__DEL__',''),10);
        const alerts = getAlerts();
        if(idx>=0 && idx<alerts.length){
          if(!confirm(`Remove alert for ${alerts[idx].symbol}?`)) return;
          alerts.splice(idx,1);
          setAlerts(alerts);
          toast('Alert removed');
        }
      }
    }
  });
}

function checkAlerts(){
  const alerts = getAlerts();
  if(!alerts.length || !INVEST.data || !INVEST.data.items) return;

  const now = Date.now();
  let changed = false;

  for(const a of alerts){
    if(!a.armed) continue;
    const it = INVEST.data.items.find(x=>x.symbol===a.symbol);
    if(!it || !it.last_close || !isFinite(it.last_close)) continue;
    const price = it.last_close;
    const base = a.base || price;
    if(base <= 0) continue;

    const pctMove = ((price/base) - 1) * 100;
    const shouldTrigger = (a.type === 'rise') ? (pctMove >= a.pct) : (pctMove <= -a.pct);

    // cooldown 6h
    const cooldown = 6 * 3600 * 1000;
    const canTrigger = !a.last_trigger || (now - a.last_trigger > cooldown);

    if(shouldTrigger && canTrigger){
      a.last_trigger = now;
      changed = true;
      toast(`Alert: ${a.symbol} ${a.type==='rise'?'up':'down'} ${Math.abs(pctMove).toFixed(1)}%`);
    }
  }

  if(changed) setAlerts(alerts);
}

// ---------- Market data refresh ----------
async function refreshInvest(force=false){
  if(window.PAGE !== 'invest') return;

  try{
    // load month stats for suggestion chips
    if(!INVEST.statsMonth) await loadMonthStats();
    renderHealthChips();

    // cache in memory unless force
    if(INVEST.data && !force){
      renderInvestGrid(INVEST.data.items);
      investAnalyze();
      checkAlerts();
      return;
    }

    const symbols = INVEST.watch.map(x=>x.s).join(',');
    const days = 320; // keep enough for 1Y + buffer
    const data = await apiGet(`${API}?action=market_data&symbols=${encodeURIComponent(symbols)}&days=${days}`);
    INVEST.data = data;

    // map names from watchlist
    const nameMap = new Map(INVEST.watch.map(x=>[x.s, x.n]));
    data.items.forEach(it=>{
      it.name = it.name || nameMap.get(it.symbol) || it.symbol;
      // ensure series is ascending by date (stooq is usually ascending)
      if(Array.isArray(it.series) && it.series.length >= 2){
        const a = it.series[0].d, b = it.series[it.series.length-1].d;
        if(a > b) it.series.reverse();
      }
    });

    renderInvestGrid(data.items);
    investAnalyze();
    checkAlerts();

    // if compare not set, choose first two
    if(!INVEST.compare.a && INVEST.watch[0]) INVEST.compare.a = INVEST.watch[0].s;
    if(!INVEST.compare.b && INVEST.watch[1]) INVEST.compare.b = INVEST.watch[1].s;
    invSaveJSON(INV_KEYS.CMP, INVEST.compare);
    updateComparePills();
    renderCompareChart();

    toast('Market data loaded');
  }catch(e){
    const grid = document.getElementById('invGrid');
    grid.innerHTML = `
      <div class="card soft inv-spanall">
        <div class="inner" style="position:relative; z-index:1;">
          <div style="font-weight:950; letter-spacing:-.2px;">No market data</div>
          <div style="color:var(--muted); font-size:12.5px; margin-top:6px;">
            Error: ${escapeHtml(e.message || 'Failed')}
          </div>
          <div style="height:10px;"></div>
          <div style="color:var(--muted2); font-size:12px;">
            Tip: ensure server can reach Stooq and allow outbound HTTPS.
          </div>
        </div>
      </div>`;
    toast('Market data error');
  }
}

// ---------- Expose ----------
window.refreshInvest = refreshInvest;
window.openInvestRiskSelect = openInvestRiskSelect;
window.openInvestHorizonSelect = openInvestHorizonSelect;
window.openInvestAllocSelect = openInvestAllocSelect;
window.openInvestSuggestSelect = openInvestSuggestSelect;
window.applySuggestedBudget = applySuggestedBudget;

window.openWatchlistSheet = openWatchlistSheet;
window.applyPreset = applyPreset;
window.resetWatchlistDefault = resetWatchlistDefault;

window.investAnalyze = investAnalyze;

window.openComparePicker = openComparePicker;
window.pickCompare = pickCompare;
window.renderCompareChart = renderCompareChart;

window.openAlertsManager = openAlertsManager;

// Also: when user changes budget manually, re-run quickly
document.getElementById('invBudget')?.addEventListener('change', ()=> investAnalyze());
</script>