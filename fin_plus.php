<?php
declare(strict_types=1);

/**
 * fin_plus.php
 * One-file plugin that serves JavaScript to "plug into" your index.php app.
 *
 * Usage:
 *   <script src="fin_plus.php?mode=js"></script>
 */

$mode = $_GET['mode'] ?? 'js';

if ($mode !== 'js') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Unsupported mode'], JSON_UNESCAPED_UNICODE);
  exit;
}

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$js = <<<'JS'
/* Fin+ Plugin — extends existing index.php app (client-side) */
(function(){
  // Guard: wait until main app has loaded
  function ready(fn){
    if(document.readyState === 'complete' || document.readyState === 'interactive') return setTimeout(fn, 0);
    document.addEventListener('DOMContentLoaded', fn);
  }

  ready(async function(){
    // If index.js hasn't created these yet, bail quietly
    if(typeof window.state !== 'object' || typeof window.apiGet !== 'function' || typeof window.apiSend !== 'function'){
      console.warn('[fin+] base app not detected');
      return;
    }

    const API = window.API || 'api.php';

    // ---------------- Storage keys ----------------
    const LS = {
      savedFilters: 'fin_plus_saved_filters_v1',
      archivedCats: 'fin_plus_archived_categories_v1', // array of category ids (string)
      hideAmounts: 'fin_plus_hide_amounts_v1',
      sortMode: 'fin_plus_sort_mode_v1', // date_desc/date_asc/amount_desc/amount_asc
      lockTimeoutMin: 'fin_plus_lock_timeout_min_v1', // number
      recurring: 'fin_plus_recurring_v1', // array
      lastDeleted: 'fin_plus_last_deleted_v1', // object
    };

    // ---------------- Helpers ----------------
    const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
    const $ = (sel, root=document) => root.querySelector(sel);

    function nowISODate(){
      return new Date().toISOString().slice(0,10);
    }

    function toCents(amountStr){
      const v = parseFloat(String(amountStr).replace(',','.'));
      if(!isFinite(v)) return 0;
      return Math.round(v * 100);
    }

    function fromCents(c){
      return (c/100).toFixed(2);
    }

    function loadJSON(key, fallback){
      try{
        const raw = localStorage.getItem(key);
        if(!raw) return fallback;
        return JSON.parse(raw);
      }catch(e){ return fallback; }
    }
    function saveJSON(key, obj){
      localStorage.setItem(key, JSON.stringify(obj));
    }

    // ---------------- UI toggles: hide amounts ----------------
    function applyHideAmounts(on){
      const html = document.documentElement;
      html.classList.toggle('fin-hide-amounts', !!on);
      localStorage.setItem(LS.hideAmounts, on ? '1' : '0');
      window.toast?.(on ? 'Privacy: amounts hidden' : 'Privacy: amounts visible');
    }
    function initHideAmounts(){
      const on = localStorage.getItem(LS.hideAmounts) === '1';
      if(!$('#finPlusHideCSS')){
        const st = document.createElement('style');
        st.id = 'finPlusHideCSS';
        st.textContent = `
          .fin-hide-amounts .amt,
          .fin-hide-amounts .amount,
          .fin-hide-amounts #balanceAmt,
          .fin-hide-amounts #incomeAmt,
          .fin-hide-amounts #expenseAmt,
          .fin-hide-amounts #kpiIncome,
          .fin-hide-amounts #kpiExpense{
            filter: blur(10px);
            user-select:none;
          }
        `;
        document.head.appendChild(st);
      }
      applyHideAmounts(on);
    }

    // ---------------- Lock timeout (PIN) ----------------
    function initLockTimeout(){
      const PIN_KEY = 'fin_pin_hash_v1';
      const UNLOCK_KEY = 'fin_pin_unlocked_v1';
      const hasPin = () => !!localStorage.getItem(PIN_KEY);

      let mins = parseInt(localStorage.getItem(LS.lockTimeoutMin) || '5', 10);
      if(!isFinite(mins) || mins < 1) mins = 5;

      let t = null;
      function arm(){
        if(!hasPin()) return;
        if(t) clearTimeout(t);
        t = setTimeout(()=>{
          // force re-lock
          localStorage.removeItem(UNLOCK_KEY);
          // call existing lock UI if exists
          if(typeof window.renderPinUI === 'function' && typeof window.lockShow === 'function'){
            window.state.pin.entered = '';
            window.renderPinUI();
            window.lockShow();
            window.toast?.('Locked (timeout)');
          } else {
            window.toast?.('Locked (timeout) — reload');
          }
        }, mins * 60 * 1000);
      }

      ['click','keydown','touchstart','mousemove','scroll'].forEach(ev=>{
        window.addEventListener(ev, ()=> arm(), {passive:true});
      });

      arm();

      // expose small setter
      window.finPlusSetLockTimeout = function(newMin){
        const m = parseInt(newMin,10);
        if(!isFinite(m) || m < 1) return window.toast?.('Minutes >= 1');
        localStorage.setItem(LS.lockTimeoutMin, String(m));
        window.toast?.('Lock timeout updated');
        mins = m;
        arm();
      };
    }

    // ---------------- Saved Filters ----------------
    function getSavedFilters(){ return loadJSON(LS.savedFilters, []); }
    function setSavedFilters(list){ saveJSON(LS.savedFilters, list); }
    function saveCurrentFiltersAs(name){
      const f = window.state.filters || {q:'',type:'',category_id:'',from:'',to:''};
      const list = getSavedFilters();
      const id = String(Date.now());
      list.unshift({ id, name: name || 'Saved', filters: {...f} });
      setSavedFilters(list.slice(0, 30));
      window.toast?.('Saved filter');
    }
    function applySavedFilter(id){
      const it = getSavedFilters().find(x=>x.id===id);
      if(!it) return window.toast?.('Not found');
      window.state.filters = {...it.filters};
      if(typeof window.openFilterSheet === 'function'){
        // set inputs without opening (best effort)
        try{
          $('#filterQ').value = window.state.filters.q || '';
          $('#filterType').value = window.state.filters.type || '';
          $('#filterCategory').value = window.state.filters.category_id || '';
          $('#filterFrom').value = window.state.filters.from || '';
          $('#filterTo').value = window.state.filters.to || '';
          window.setFilterTypeLabel?.();
          window.setFilterCategoryLabel?.();
        }catch(e){}
      }
      window.refreshTransactions?.();
      window.toast?.('Filter applied');
    }
    function deleteSavedFilter(id){
      const list = getSavedFilters().filter(x=>x.id!==id);
      setSavedFilters(list);
      window.toast?.('Deleted filter');
    }

    // ---------------- Archived categories (local hide) ----------------
    function getArchivedCats(){
      const arr = loadJSON(LS.archivedCats, []);
      return new Set(arr.map(String));
    }
    function setArchivedCats(set){
      saveJSON(LS.archivedCats, Array.from(set));
    }
    function archiveCategory(id){
      const s = getArchivedCats();
      s.add(String(id));
      setArchivedCats(s);
      window.toast?.('Category archived');
      // refresh chips/labels
      window.renderCatChips?.();
      window.setFilterCategoryLabel?.();
    }
    function unarchiveCategory(id){
      const s = getArchivedCats();
      s.delete(String(id));
      setArchivedCats(s);
      window.toast?.('Category unarchived');
      window.renderCatChips?.();
      window.setFilterCategoryLabel?.();
    }

    // Patch category lists to skip archived (client-side)
    function filterOutArchived(cats){
      const arch = getArchivedCats();
      return cats.filter(c=>!arch.has(String(c.id)));
    }

    // ---------------- Sorting & quick chips ----------------
    function getSortMode(){
      return localStorage.getItem(LS.sortMode) || 'date_desc';
    }
    function setSortMode(m){
      localStorage.setItem(LS.sortMode, m);
      window.toast?.('Sort: ' + m.replace('_',' '));
      window.refreshTransactions?.();
    }

    function sortTx(items){
      const m = getSortMode();
      const arr = items.slice();
      if(m === 'date_asc') arr.sort((a,b)=> String(a.date).localeCompare(String(b.date)));
      else if(m === 'date_desc') arr.sort((a,b)=> String(b.date).localeCompare(String(a.date)));
      else if(m === 'amount_asc') arr.sort((a,b)=> (a.amount_cents||0)-(b.amount_cents||0));
      else if(m === 'amount_desc') arr.sort((a,b)=> (b.amount_cents||0)-(a.amount_cents||0));
      return arr;
    }

    // Inject quick chips + sort button inside Transactions view
    function injectTxTools(){
      const view = $('#view-transactions');
      if(!view || $('#finPlusTxTools')) return;

      const box = document.createElement('div');
      box.id = 'finPlusTxTools';
      box.style.margin = '10px 0 12px';
      box.innerHTML = `
        <div class="row" style="gap:10px; flex-wrap:wrap;">
          <span class="chip" role="button" tabindex="0" id="finPlusSortBtn">Sort ⌄</span>
          <span class="chip" role="button" tabindex="0" id="finPlusSavedBtn">Saved filters ⌄</span>
          <span class="chip" role="button" tabindex="0" id="finPlusMultiBtn">Multi-select</span>
          <span class="chip" role="button" tabindex="0" id="finPlusPrivacyBtn">Hide amounts</span>
        </div>
        <div class="quick-row" style="padding-top:10px;">
          <div class="quick" id="finPlusChipExpense"><span class="k">➖</span> Expense</div>
          <div class="quick" id="finPlusChipIncome"><span class="k">➕</span> Income</div>
          <div class="quick" id="finPlusChipClear"><span class="k">✨</span> Clear</div>
          <div class="quick" id="finPlusChipThisMonth"><span class="k">📅</span> This month</div>
        </div>
      `;
      view.insertBefore(box, view.firstChild);

      $('#finPlusPrivacyBtn').onclick = ()=> applyHideAmounts(!(localStorage.getItem(LS.hideAmounts)==='1'));

      $('#finPlusChipExpense').onclick = ()=>{
        window.state.filters.type = 'expense';
        $('#filterType') && ($('#filterType').value = 'expense');
        window.setFilterTypeLabel?.();
        window.refreshTransactions?.();
      };
      $('#finPlusChipIncome').onclick = ()=>{
        window.state.filters.type = 'income';
        $('#filterType') && ($('#filterType').value = 'income');
        window.setFilterTypeLabel?.();
        window.refreshTransactions?.();
      };
      $('#finPlusChipClear').onclick = ()=>{
        if(typeof window.clearFilters === 'function') window.clearFilters();
        window.refreshTransactions?.();
      };
      $('#finPlusChipThisMonth').onclick = ()=>{
        window.state.filters.from = window.state.month;
        window.state.filters.to = window.monthEnd(window.state.month);
        $('#filterFrom') && ($('#filterFrom').value = window.state.filters.from);
        $('#filterTo') && ($('#filterTo').value = window.state.filters.to);
        window.refreshTransactions?.();
      };

      $('#finPlusSortBtn').onclick = ()=> openSortSheet();
      $('#finPlusSavedBtn').onclick = ()=> openSavedFiltersSheet();
      $('#finPlusMultiBtn').onclick = ()=> toggleMultiSelect(true);
    }

    // Use existing "sheetSelect" generic to show options
    function openSortSheet(){
      const items = [
        {value:'date_desc', label:'Date (newest)', sub:'Default', icon:'🗓️'},
        {value:'date_asc', label:'Date (oldest)', sub:'', icon:'🗓️'},
        {value:'amount_desc', label:'Amount (high→low)', sub:'', icon:'💰'},
        {value:'amount_asc', label:'Amount (low→high)', sub:'', icon:'💰'},
      ];
      if(typeof window.openSelectSheet !== 'function') return window.toast?.('Select sheet missing');
      window.openSelectSheet({
        title: 'Sort',
        items,
        value: getSortMode(),
        onPick: (v)=> setSortMode(v)
      });
    }

    function openSavedFiltersSheet(){
      const list = getSavedFilters();
      const items = [
        {value:'__save__', label:'Save current…', sub:'Store current search/filters', icon:'💾'},
        {value:'__manage__', label:'Manage…', sub:'Delete saved filters', icon:'🧹'},
      ].concat(list.map(s=>({
        value: s.id,
        label: s.name,
        sub: `${(s.filters.type||'all')} • ${(s.filters.q||'')} ${(s.filters.category_id||'')}`,
        icon:'🔎'
      })));

      window.openSelectSheet({
        title: 'Saved filters',
        items,
        value: '',
        onPick: (val)=>{
          if(val === '__save__'){
            const name = prompt('Name for saved filter?','My filter');
            if(name) saveCurrentFiltersAs(name);
            return;
          }
          if(val === '__manage__'){
            manageSavedFilters();
            return;
          }
          applySavedFilter(val);
        }
      });
    }

    function manageSavedFilters(){
      const list = getSavedFilters();
      if(!list.length) return window.toast?.('No saved filters');
      const name = prompt(
        'Type the NAME of a saved filter to delete:\n\n' +
        list.slice(0,15).map(x=>`- ${x.name}`).join('\n')
      );
      if(!name) return;
      const it = list.find(x=>x.name.toLowerCase() === name.trim().toLowerCase());
      if(!it) return window.toast?.('Not found');
      if(!confirm(`Delete saved filter "${it.name}"?`)) return;
      deleteSavedFilter(it.id);
    }

    // ---------------- Multi-select & bulk actions ----------------
    let multi = { on:false, selected:new Set() };

    function toggleMultiSelect(forceOn){
      multi.on = forceOn ? true : !multi.on;
      multi.selected.clear();
      window.toast?.(multi.on ? 'Multi-select ON' : 'Multi-select OFF');
      renderMultiUI();
      window.refreshTransactions?.();
    }

    function renderMultiUI(){
      const view = $('#view-transactions');
      if(!view) return;

      // action bar
      let bar = $('#finPlusBulkBar');
      if(!bar){
        bar = document.createElement('div');
        bar.id = 'finPlusBulkBar';
        bar.style.margin = '10px 0 12px';
        bar.style.display = 'none';
        bar.innerHTML = `
          <div class="card soft" style="padding:12px 12px;">
            <div class="row" style="justify-content:space-between;">
              <div style="font-weight:950;">Selected: <span id="finPlusSelCount">0</span></div>
              <div class="row">
                <span class="pill" role="button" tabindex="0" id="finPlusBulkExit">Exit</span>
              </div>
            </div>
            <div class="row" style="margin-top:10px;">
              <span class="pill" role="button" tabindex="0" id="finPlusBulkDelete">Delete</span>
              <span class="pill" role="button" tabindex="0" id="finPlusBulkCat">Change category</span>
              <span class="pill" role="button" tabindex="0" id="finPlusBulkAcc">Change account</span>
              <span class="pill" role="button" tabindex="0" id="finPlusBulkExport">Export selected CSV</span>
            </div>
          </div>
        `;
        view.insertBefore(bar, view.firstChild);
        $('#finPlusBulkExit').onclick = ()=> toggleMultiSelect(false);
        $('#finPlusBulkDelete').onclick = ()=> bulkDelete();
        $('#finPlusBulkCat').onclick = ()=> bulkChangeCategory();
        $('#finPlusBulkAcc').onclick = ()=> bulkChangeAccount();
        $('#finPlusBulkExport').onclick = ()=> bulkExportCSV();
      }
      bar.style.display = multi.on ? 'block' : 'none';
      $('#finPlusSelCount').textContent = String(multi.selected.size);
    }

    function decorateTxRowForMulti(){
      if(!multi.on) return;
      $$('#txList .item[data-tx]').forEach(el=>{
        el.style.position = 'relative';
        if(el.querySelector('.finPlusTick')) return;
        const tick = document.createElement('div');
        tick.className = 'finPlusTick';
        tick.style.position = 'absolute';
        tick.style.left = '10px';
        tick.style.top = '50%';
        tick.style.transform = 'translateY(-50%)';
        tick.style.width = '22px';
        tick.style.height = '22px';
        tick.style.borderRadius = '10px';
        tick.style.border = '1px solid var(--border2)';
        tick.style.background = 'rgba(255,255,255,.04)';
        tick.style.display = 'flex';
        tick.style.alignItems = 'center';
        tick.style.justifyContent = 'center';
        tick.style.fontWeight = '950';
        tick.style.pointerEvents = 'none';
        tick.textContent = '';
        el.appendChild(tick);

        // shift content a bit
        const left = el.querySelector('.left');
        if(left) left.style.paddingLeft = '18px';

        el.onclick = ()=>{
          const tx = JSON.parse(el.getAttribute('data-tx'));
          const key = String(tx.id);
          if(multi.selected.has(key)) multi.selected.delete(key);
          else multi.selected.add(key);
          tick.textContent = multi.selected.has(key) ? '✓' : '';
          renderMultiUI();
        };
      });
    }

    async function getVisibleTxList(){
      // re-fetch current list (same as refreshTransactions uses)
      const from = window.state.filters.from || window.state.month;
      const to = window.state.filters.to || window.monthEnd(window.state.month);
      const params = new URLSearchParams({ action:'transactions', from, to });
      if(window.state.filters.type) params.set('type', window.state.filters.type);
      if(window.state.filters.category_id) params.set('category_id', window.state.filters.category_id);
      if(window.state.filters.q) params.set('q', window.state.filters.q);
      const tx = await window.apiGet(`${API}?` + params.toString());
      return sortTx(tx.items || []);
    }

    async function bulkDelete(){
      if(multi.selected.size === 0) return window.toast?.('Select items');
      if(!confirm(`Delete ${multi.selected.size} transactions?`)) return;

      const ids = Array.from(multi.selected);
      let ok=0, fail=0;
      for(const id of ids){
        try{ await window.apiSend(`${API}?action=transaction&id=${id}`, 'DELETE'); ok++; }
        catch(e){ fail++; }
      }
      window.toast?.(`Deleted ${ok}${fail?` • failed ${fail}`:''}`);
      toggleMultiSelect(false);
      await window.refreshTransactions?.();
      await window.refreshDashboard?.();
    }

    async function bulkChangeAccount(){
      if(multi.selected.size === 0) return window.toast?.('Select items');

      const items = [
        { value:'Card', label:'Card', sub:'Card payments', icon:'💳' },
        { value:'Cash', label:'Cash', sub:'Cash expenses', icon:'💶' },
        { value:'Bank', label:'Bank', sub:'Bank transfer', icon:'🏦' },
      ];
      window.openSelectSheet({
        title: 'Change account',
        items,
        value: 'Card',
        onPick: async (acc)=>{
          const ids = Array.from(multi.selected);
          let ok=0, fail=0;
          for(const id of ids){
            try{
              await window.apiSend(`${API}?action=transaction&id=${id}&_method=PUT`, 'POST', { account: acc });
              ok++;
            }catch(e){ fail++; }
          }
          window.toast?.(`Updated ${ok}${fail?` • failed ${fail}`:''}`);
          toggleMultiSelect(false);
          await window.refreshTransactions?.();
          await window.refreshDashboard?.();
        }
      });
    }

    async function bulkChangeCategory(){
      if(multi.selected.size === 0) return window.toast?.('Select items');

      // category select (not archived)
      const cats = filterOutArchived(window.state.categories || [])
        .slice()
        .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));

      const items = cats.map(c=>({
        value: String(c.id),
        label: c.name,
        sub: c.type,
        icon: c.icon || '🏷️'
      }));

      window.openSelectSheet({
        title: 'Change category',
        items,
        value: '',
        onPick: async (cid)=>{
          const ids = Array.from(multi.selected);
          let ok=0, fail=0;
          for(const id of ids){
            try{
              await window.apiSend(`${API}?action=transaction&id=${id}&_method=PUT`, 'POST', { category_id: parseInt(cid,10) });
              ok++;
            }catch(e){ fail++; }
          }
          window.toast?.(`Updated ${ok}${fail?` • failed ${fail}`:''}`);
          toggleMultiSelect(false);
          await window.refreshTransactions?.();
          await window.refreshDashboard?.();
        }
      });
    }

    async function bulkExportCSV(){
      if(multi.selected.size === 0) return window.toast?.('Select items');
      const list = await getVisibleTxList();
      const selected = list.filter(t=> multi.selected.has(String(t.id)));
      if(!selected.length) return window.toast?.('Nothing to export');

      const rows = [
        ['id','date','type','amount','account','category','note'].join(',')
      ].concat(selected.map(t=>{
        const amount = (t.amount_cents/100).toFixed(2);
        const cat = (t.category_name || '').replaceAll('"','""');
        const note = (t.note || '').replaceAll('"','""');
        const acc = (t.account || '').replaceAll('"','""');
        return [
          t.id,
          t.date,
          t.type,
          amount,
          `"${acc}"`,
          `"${cat}"`,
          `"${note}"`
        ].join(',');
      }));

      const blob = new Blob([rows.join('\n')], {type:'text/csv;charset=utf-8'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `fin_selected_${Date.now()}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
      window.toast?.('CSV downloaded');
    }

    // ---------------- Undo delete (soft) ----------------
    async function deleteWithUndo(tx){
      // store payload for quick restore
      saveJSON(LS.lastDeleted, { tx, at: Date.now() });
      window.toast?.('Deleted (undo available)');
      // show undo toast-ish
      showUndoBar();
      await window.apiSend(`${API}?action=transaction&id=${tx.id}`, 'DELETE');
      await window.refresh?.();
    }

    function showUndoBar(){
      let el = $('#finPlusUndo');
      if(!el){
        el = document.createElement('div');
        el.id = 'finPlusUndo';
        el.style.position = 'fixed';
        el.style.left = '50%';
        el.style.transform = 'translateX(-50%)';
        el.style.bottom = '190px';
        el.style.zIndex = '140';
        el.innerHTML = `
          <div class="card soft" style="padding:10px 12px; display:flex; gap:10px; align-items:center;">
            <div style="font-weight:950;">Transaction deleted</div>
            <span class="pill" role="button" tabindex="0" id="finPlusUndoBtn">Undo</span>
          </div>
        `;
        document.body.appendChild(el);
        $('#finPlusUndoBtn').onclick = ()=> undoDelete();
      }
      el.style.display = 'block';
      clearTimeout(window.__finPlusUndoTimer);
      window.__finPlusUndoTimer = setTimeout(()=> { el.style.display='none'; }, 7000);
    }

    async function undoDelete(){
      const d = loadJSON(LS.lastDeleted, null);
      if(!d || !d.tx) return window.toast?.('Nothing to undo');
      const tx = d.tx;
      // recreate transaction (new id)
      try{
        await window.apiSend(`${API}?action=transactions`, 'POST', {
          type: tx.type,
          amount: fromCents(tx.amount_cents),
          date: tx.date,
          category_id: tx.category_id,
          account: tx.account || 'Card',
          note: tx.note || ''
        });
        saveJSON(LS.lastDeleted, null);
        $('#finPlusUndo') && ($('#finPlusUndo').style.display='none');
        window.toast?.('Restored');
        await window.refresh?.();
      }catch(e){
        window.toast?.('Undo error: ' + e.message);
      }
    }

    // ---------------- Duplicate / Reverse / Transfer / Split ----------------
    async function duplicateTx(tx, opts={}){
      const date = opts.date || nowISODate();
      const note = (opts.notePrefix ? opts.notePrefix + ' ' : '') + (tx.note || '');
      await window.apiSend(`${API}?action=transactions`, 'POST', {
        type: tx.type,
        amount: fromCents(tx.amount_cents),
        date,
        category_id: tx.category_id,
        account: tx.account || 'Card',
        note: note.trim()
      });
      window.toast?.('Copied');
      await window.refresh?.();
    }

    async function reverseTx(tx){
      // create opposite type with same amount/category/account
      const newType = tx.type === 'expense' ? 'income' : 'expense';
      await window.apiSend(`${API}?action=transactions`, 'POST', {
        type: newType,
        amount: fromCents(tx.amount_cents),
        date: nowISODate(),
        category_id: tx.category_id,
        account: tx.account || 'Card',
        note: `Reversal of #${tx.id}` + (tx.note ? ` • ${tx.note}` : '')
      });
      window.toast?.('Reversed');
      await window.refresh?.();
    }

    async function transferTx(tx){
      // Transfer: create expense from source and income to target (net 0)
      const src = tx.account || 'Card';
      const items = [
        { value:'Card', label:'Card', sub:'Card', icon:'💳' },
        { value:'Cash', label:'Cash', sub:'Cash', icon:'💶' },
        { value:'Bank', label:'Bank', sub:'Bank', icon:'🏦' },
      ].filter(a=>a.value!==src);

      window.openSelectSheet({
        title: 'Transfer to…',
        items,
        value: items[0]?.value || 'Cash',
        onPick: async (target)=>{
          const amount = fromCents(tx.amount_cents);
          const date = nowISODate();
          // expense from source (category kept)
          await window.apiSend(`${API}?action=transactions`, 'POST', {
            type: 'expense',
            amount, date,
            category_id: tx.category_id,
            account: src,
            note: `Transfer to ${target}` + (tx.note ? ` • ${tx.note}` : '')
          });
          // income to target
          await window.apiSend(`${API}?action=transactions`, 'POST', {
            type: 'income',
            amount, date,
            category_id: tx.category_id,
            account: target,
            note: `Transfer from ${src}` + (tx.note ? ` • ${tx.note}` : '')
          });
          window.toast?.('Transfer created');
          await window.refresh?.();
        }
      });
    }

    async function splitTx(tx){
      const cats = filterOutArchived(window.state.categories || []).filter(c=>c.type==='expense')
        .slice().sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));
      if(!cats.length) return window.toast?.('No expense categories');

      const nStr = prompt('Split into how many parts? (2-5)', '2');
      const n = parseInt(nStr||'0',10);
      if(!isFinite(n) || n < 2 || n > 5) return window.toast?.('2–5 only');

      const total = tx.amount_cents;
      let remaining = total;

      for(let i=1;i<=n;i++){
        const partStr = prompt(`Part ${i}/${n} amount (e.g. 12.34)`, (remaining/100).toFixed(2));
        if(partStr === null) return window.toast?.('Cancelled');
        const part = toCents(partStr);
        if(part<=0) return window.toast?.('Invalid amount');
        if(part>remaining) return window.toast?.('Too large');
        remaining -= part;

        const pickName = prompt(
          `Part ${i} category NAME:\n\n` +
          cats.slice(0,15).map(c=>`- ${c.name}`).join('\n'),
          cats[0].name
        );
        if(!pickName) return window.toast?.('Cancelled');
        const cat = cats.find(c=>c.name.toLowerCase()===pickName.trim().toLowerCase()) || cats[0];

        await window.apiSend(`${API}?action=transactions`, 'POST', {
          type: tx.type,
          amount: fromCents(part),
          date: tx.date,
          category_id: cat.id,
          account: tx.account || 'Card',
          note: `Split of #${tx.id}` + (tx.note ? ` • ${tx.note}` : '')
        });
      }

      window.toast?.('Split created');
      await window.refresh?.();
    }

    // ---------------- Merge categories (client-side batch PUT) ----------------
    async function mergeCategories(fromId, toId){
      if(String(fromId) === String(toId)) return window.toast?.('Same category');
      const from = window.state.month;
      // pull wider range (last 12 months) so merge catches older too (best effort)
      const d = new Date(from + 'T00:00:00');
      const start = new Date(d.getFullYear(), d.getMonth()-12, 1);
      const startISO = start.toISOString().slice(0,10);
      const endISO = window.monthEnd(window.state.month);

      const tx = await window.apiGet(`${API}?action=transactions&from=${encodeURIComponent(startISO)}&to=${encodeURIComponent(endISO)}`);
      const affected = (tx.items||[]).filter(t=>String(t.category_id)===String(fromId));
      if(!affected.length) return window.toast?.('No transactions to move');
      if(!confirm(`Move ${affected.length} transactions to new category?`)) return;

      let ok=0, fail=0;
      for(const t of affected){
        try{
          await window.apiSend(`${API}?action=transaction&id=${t.id}&_method=PUT`, 'POST', { category_id: parseInt(String(toId),10) });
          ok++;
        }catch(e){ fail++; }
      }
      window.toast?.(`Merged ${ok}${fail?` • failed ${fail}`:''}`);
      await window.refresh?.();
    }

    // ---------------- Budgets edit/delete (best effort) ----------------
    async function editBudget(month, category_id, newLimitStr){
      const limit = String(newLimitStr||'').trim();
      if(!limit) return window.toast?.('Limit required');
      await window.apiSend(`${API}?action=budgets`, 'POST', { month, category_id: parseInt(String(category_id),10), limit });
      window.toast?.('Budget updated');
      await window.refresh?.();
    }
    async function deleteBudget(month, category_id){
      // If there is no delete endpoint, we "disable" by setting 0
      if(!confirm('Delete/disable this budget?')) return;
      try{
        await window.apiSend(`${API}?action=budget_delete`, 'POST', { month, category_id: parseInt(String(category_id),10) });
      }catch(e){
        // fallback: set limit=0
        await window.apiSend(`${API}?action=budgets`, 'POST', { month, category_id: parseInt(String(category_id),10), limit: '0' });
      }
      window.toast?.('Budget removed');
      await window.refresh?.();
    }

    // ---------------- Budget alerts (80/100) ----------------
    async function budgetAlerts(){
      try{
        const month = window.state.month;
        const budgets = await window.apiGet(`${API}?action=budgets&month=${encodeURIComponent(month)}`);
        if(!(budgets.items||[]).length) return;

        const from = month;
        const to = window.monthEnd(month);
        const tx = await window.apiGet(`${API}?action=transactions&from=${from}&to=${to}&type=expense`);

        const spentByCat = {};
        (tx.items||[]).forEach(t=>{
          spentByCat[t.category_id] = (spentByCat[t.category_id]||0) + (parseInt(t.amount_cents,10)||0);
        });

        for(const b of (budgets.items||[])){
          const spent = spentByCat[b.category_id] || 0;
          const limit = parseInt(b.limit_cents,10)||0;
          if(limit<=0) continue;
          const pct = spent/limit;
          if(pct >= 1.0) window.toast?.(`⚠️ Budget exceeded: ${b.category_name}`);
          else if(pct >= 0.8) window.toast?.(`Heads up: 80% used — ${b.category_name}`);
        }
      }catch(e){}
    }

    // ---------------- Insights sheet (lightweight) ----------------
    function ensureInsightsSheet(){
      if($('#sheetInsights')) return;

      const wrap = document.createElement('div');
      wrap.className = 'sheet';
      wrap.id = 'sheetInsights';
      wrap.setAttribute('aria-hidden','true');
      wrap.innerHTML = `
        <div class="sheet-card" role="dialog" aria-modal="true" aria-label="Insights">
          <div class="sheet-handle"></div>
          <div class="sheet-body">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
              <div style="font-weight:950; letter-spacing:-.2px;">Insights</div>
              <button class="icon-btn" style="width:38px;height:38px;border-radius:14px" onclick="closeSheet('sheetInsights')" aria-label="Close">✕</button>
            </div>

            <div class="section-title" style="margin-top:12px;"><span>Monthly compare</span><span></span></div>
            <div class="card soft" style="padding:12px 12px;">
              <div id="finPlusCompare">—</div>
            </div>

            <div class="section-title"><span>Trends</span><span></span></div>
            <div class="card soft" style="padding:12px 12px;">
              <div id="finPlusTrends">—</div>
            </div>

            <div class="section-title"><span>Calendar (list)</span><span></span></div>
            <div class="card soft" style="padding:12px 12px;">
              <div id="finPlusCalendar">—</div>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(wrap);
    }

    async function openInsights(){
      ensureInsightsSheet();
      await renderInsights();
      window.openSheet?.('sheetInsights');
    }

    async function renderInsights(){
      const month = window.state.month;
      const from = month;
      const to = window.monthEnd(month);

      const cur = await window.apiGet(`${API}?action=stats_month&month=${encodeURIComponent(month)}`);

      // prev month
      const d = new Date(month + 'T00:00:00');
      const prev = new Date(d.getFullYear(), d.getMonth()-1, 1);
      const prevMonth = prev.toISOString().slice(0,7) + '-01';
      const prevStats = await window.apiGet(`${API}?action=stats_month&month=${encodeURIComponent(prevMonth)}`);

      const diff = (a,b)=> (b===0? '—' : (((a-b)/Math.abs(b))*100).toFixed(1) + '%');
      const cmp = `
        <div style="display:grid; gap:10px;">
          <div><b>Income</b>: ${window.fmtMoneyFromCents(cur.income_cents)} <span style="color:var(--muted)">(${diff(cur.income_cents, prevStats.income_cents)} vs prev)</span></div>
          <div><b>Expense</b>: ${window.fmtMoneyFromCents(cur.expense_cents)} <span style="color:var(--muted)">(${diff(cur.expense_cents, prevStats.expense_cents)} vs prev)</span></div>
          <div><b>Net</b>: ${window.fmtMoneyFromCents(cur.balance_cents)} <span style="color:var(--muted)">(${diff(cur.balance_cents, prevStats.balance_cents)} vs prev)</span></div>
        </div>
      `;
      $('#finPlusCompare').innerHTML = cmp;

      // trends: top 5 expense categories this month
      const tx = await window.apiGet(`${API}?action=transactions&from=${from}&to=${to}&type=expense`);
      const by = {};
      (tx.items||[]).forEach(t=>{
        const k = t.category_name || '—';
        by[k] = (by[k]||0) + (parseInt(t.amount_cents,10)||0);
      });
      const top = Object.entries(by).sort((a,b)=>b[1]-a[1]).slice(0,5);
      $('#finPlusTrends').innerHTML = top.length
        ? top.map(([k,v])=> `<div style="display:flex; justify-content:space-between; padding:6px 0;"><span>${window.escapeHtml(k)}</span><b>${window.escapeHtml(window.fmtMoneyFromCents(v))}</b></div>`).join('')
        : `<div style="color:var(--muted)">No data</div>`;

      // calendar list: totals per day (expense)
      const perDay = {};
      (tx.items||[]).forEach(t=>{
        perDay[t.date] = (perDay[t.date]||0) + (parseInt(t.amount_cents,10)||0);
      });
      const days = Object.keys(perDay).sort();
      $('#finPlusCalendar').innerHTML = days.length
        ? days.map(d=> `<div style="display:flex; justify-content:space-between; padding:6px 0;"><span>${window.escapeHtml(d)}</span><b class="amt expense">${window.escapeHtml(window.fmtMoneyFromCents(perDay[d]))}</b></div>`).join('')
        : `<div style="color:var(--muted)">No expenses</div>`;
    }

    // ---------------- Patch existing click handlers ----------------
    function patchInsightsButton(){
      // In your dashboard hero you have pill with onclick="toast('More insights later ✨')"
      // We'll override by finding that pill and replacing handler
      const pills = $$('.card.hero .pill');
      const btn = pills.find(p=> (p.textContent||'').toLowerCase().includes('insights'));
      if(btn){
        btn.onclick = (e)=>{ e.preventDefault?.(); openInsights(); };
      }
    }

    // Patch refreshTransactions to apply sort + multi-select decoration
    const _refreshTransactions = window.refreshTransactions;
    if(typeof _refreshTransactions === 'function'){
      window.refreshTransactions = async function(){
        await _refreshTransactions();
        // sort only affects displayed order if we rebuild html; easiest: re-fetch, then rebuild list.
        // We'll rebuild list with sorted items (same renderer)
        try{
          const listEl = $('#txList');
          if(listEl){
            const items = await getVisibleTxList();
            // rebuild using original txRowHTML if exists
            if(typeof window.txRowHTML === 'function'){
              listEl.innerHTML = items.length
                ? items.map(t=> window.txRowHTML(t)).join('')
                : listEl.innerHTML;
              window.bindTxClicks?.('txList');
            }
          }
        }catch(e){}

        injectTxTools();
        renderMultiUI();
        decorateTxRowForMulti();
      };
    }

    // Patch bindTxClicks to add a "context menu" (prompt-based) with actions
    const _bindTxClicks = window.bindTxClicks;
    if(typeof _bindTxClicks === 'function'){
      window.bindTxClicks = function(containerId){
        _bindTxClicks(containerId);
        if(multi.on) return; // multi mode overrides clicks

        const c = document.getElementById(containerId);
        if(!c) return;

        $$('.item[data-tx]', c).forEach(el=>{
          el.oncontextmenu = (ev)=>{
            ev.preventDefault();
            const tx = JSON.parse(el.getAttribute('data-tx'));
            const act = prompt(
              `Action for tx #${tx.id}:\n`+
              `- edit\n- copy\n- reverse\n- transfer\n- split\n- delete\n\nType one:`,
              'edit'
            );
            if(!act) return;

            const a = act.trim().toLowerCase();
            if(a==='edit') return window.editTx?.(tx);
            if(a==='copy') return duplicateTx(tx);
            if(a==='reverse') return reverseTx(tx);
            if(a==='transfer') return transferTx(tx);
            if(a==='split') return splitTx(tx);
            if(a==='delete') return deleteWithUndo(tx);
            window.toast?.('Unknown action');
          };
        });
      };
    }

    // Patch category select list in filters to exclude archived & fix scroll issues:
    // (your current issue: can't scroll and can't pick category from all)
    // We'll override openCategorySelect to ensure the select sheet scrolls and items clickable
    if(typeof window.openCategorySelect === 'function'){
      window.openCategorySelect = function(){
        // use our archived filter
        const items = [{ value:'', label:'All', sub:'No category filter', icon:'✨' }].concat(
          filterOutArchived(window.state.categories || [])
            .slice()
            .sort((a,b)=>{
              const ta = String(a.type), tb = String(b.type);
              if(ta !== tb) return ta.localeCompare(tb);
              return (a.sort_order??0) - (b.sort_order??0);
            })
            .map(c=> ({
              value: String(c.id),
              label: `${c.name}`,
              sub: `${c.type}`,
              icon: c.icon || '🏷️'
            }))
        );

        window.openSelectSheet({
          title:'Category',
          items,
          value: document.getElementById('filterCategory').value || '',
          onPick: (val)=>{
            document.getElementById('filterCategory').value = val;
            window.setFilterCategoryLabel?.();
          }
        });

        // make sure select sheet can scroll
        const list = document.getElementById('selectList');
        if(list){
          list.style.maxHeight = '52vh';
          list.style.overflow = 'auto';
          list.style.paddingBottom = '6px';
          list.style.webkitOverflowScrolling = 'touch';
        }
      };
    }

    // Patch openBudgetCategorySelect to exclude archived
    if(typeof window.openBudgetCategorySelect === 'function'){
      window.openBudgetCategorySelect = function(){
        const expenseCats = filterOutArchived(window.state.categories || [])
          .filter(c=>c.type === 'expense')
          .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));
        const items = expenseCats.map(c=> ({
          value: String(c.id), label: c.name, sub:'expense', icon: c.icon || '🏷️'
        }));
        window.openSelectSheet({
          title:'Budget category',
          items,
          value: document.getElementById('budCategory').value || '',
          onPick: (val)=>{ document.getElementById('budCategory').value = val; window.setBudgetCategoryLabel?.(); }
        });
        const list = document.getElementById('selectList');
        if(list){
          list.style.maxHeight = '52vh';
          list.style.overflow = 'auto';
          list.style.webkitOverflowScrolling = 'touch';
        }
      };
    }

    // Patch category manager: add archive/unarchive + merge
    function patchCategoryManagerActions(){
      const old = window.renderCategoryManager;
      if(typeof old !== 'function') return;

      window.renderCategoryManager = function(){
        old();

        const list = document.getElementById('catList');
        if(!list) return;

        // add extra buttons per row (archive + merge)
        $$('.cm-row', list).forEach(row=>{
          const id = row.getAttribute('data-id');
          const actions = row.querySelector('.cm-actions');
          if(!actions) return;

          if(!actions.querySelector('.finPlusArchiveBtn')){
            const archBtn = document.createElement('button');
            archBtn.className = 'icon-btn finPlusArchiveBtn';
            archBtn.style.width='38px'; archBtn.style.height='38px'; archBtn.style.borderRadius='14px';
            archBtn.title = 'Archive';
            archBtn.textContent = '📦';
            archBtn.onclick = ()=> archiveCategory(id);
            actions.insertBefore(archBtn, actions.firstChild);
          }

          if(!actions.querySelector('.finPlusUnarchiveBtn')){
            const un = document.createElement('button');
            un.className = 'icon-btn finPlusUnarchiveBtn';
            un.style.width='38px'; un.style.height='38px'; un.style.borderRadius='14px';
            un.title = 'Unarchive';
            un.textContent = '↩️';
            un.onclick = ()=> unarchiveCategory(id);
            actions.insertBefore(un, actions.firstChild);
          }

          if(!actions.querySelector('.finPlusMergeBtn')){
            const mb = document.createElement('button');
            mb.className = 'icon-btn finPlusMergeBtn';
            mb.style.width='38px'; mb.style.height='38px'; mb.style.borderRadius='14px';
            mb.title = 'Merge into…';
            mb.textContent = '🔀';
            mb.onclick = ()=>{
              const cat = (window.state.categories||[]).find(c=>String(c.id)===String(id));
              if(!cat) return;
              const sameType = filterOutArchived(window.state.categories||[])
                .filter(c=>c.type===cat.type && String(c.id)!==String(cat.id))
                .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));
              if(!sameType.length) return window.toast?.('No merge target');
              const targetName = prompt(
                `Merge "${cat.name}" into which category NAME?\n\n` +
                sameType.slice(0,15).map(c=>`- ${c.name}`).join('\n')
              );
              if(!targetName) return;
              const target = sameType.find(c=>c.name.toLowerCase()===targetName.trim().toLowerCase());
              if(!target) return window.toast?.('Target not found');
              mergeCategories(cat.id, target.id);
            };
            actions.insertBefore(mb, actions.firstChild);
          }
        });
      };
    }

    // Patch budgets view: click budget to edit/delete
    function patchBudgetsUI(){
        const old = window.refreshBudgets;
        if(typeof old !== 'function') return;

        window.refreshBudgets = async function(){
            await old();

            const list = document.getElementById('budList');
            if(!list) return;

            $$('.budget', list).forEach(el=>{
            el.onclick = ()=>{
                // 1) Try to read budget object from data-bud (preferred)
                const raw = el.getAttribute('data-bud');
                let b = null;

                if(raw){
                try { b = JSON.parse(decodeURIComponent(raw)); } catch(e1){
                    try { b = JSON.parse(raw); } catch(e2){
                    b = null;
                    }
                }
                }

                // If we got a budget object and base editBudget exists → open edit sheet
                if(b && typeof window.editBudget === 'function'){
                return window.editBudget(b);
                }

                // 2) Fallback (if no data-bud): find by category_id from DOM is unreliable
                window.toast?.('Cannot open budget (missing data).');
            };
            });
        };
        }

    // Patch dashboard budgets list: on click go to budgets page or edit
    function patchDashboardBudgets(){
      const old = window.refreshDashboard;
      if(typeof old !== 'function') return;

      window.refreshDashboard = async function(){
        await old();
        // run alerts after dashboard stats
        budgetAlerts();
      };
    }

    // ---------------- Recurring transactions (basic, local definitions) ----------------
    function getRecurring(){ return loadJSON(LS.recurring, []); }
    function setRecurring(list){ saveJSON(LS.recurring, list); }

    async function runRecurringForMonth(month){
      const defs = getRecurring();
      if(!defs.length) return;

      const from = month;
      const to = window.monthEnd(month);

      // fetch existing tx for range to avoid duplicates by signature
      const existing = await window.apiGet(`${API}?action=transactions&from=${from}&to=${to}`);
      const sig = new Set((existing.items||[]).map(t=> `${t.date}|${t.type}|${t.amount_cents}|${t.category_id}|${t.account}|${t.note||''}`));

      for(const d of defs){
        // d: {name, type, amount_cents, category_id, account, dayOfMonth}
        const day = Math.min(28, Math.max(1, parseInt(d.dayOfMonth||'1',10)));
        const date = month.slice(0,8) + String(day).padStart(2,'0');
        const note = (d.name || 'Recurring');
        const signature = `${date}|${d.type}|${d.amount_cents}|${d.category_id}|${d.account}|${note}`;
        if(sig.has(signature)) continue;

        try{
          await window.apiSend(`${API}?action=transactions`, 'POST', {
            type: d.type,
            amount: fromCents(d.amount_cents),
            date,
            category_id: parseInt(String(d.category_id),10),
            account: d.account || 'Card',
            note
          });
          window.toast?.(`Recurring added: ${d.name}`);
        }catch(e){}
      }
      await window.refresh?.();
    }

    function exposeRecurringUI(){
      // add a small entry in settings: "Recurring"
      const settingsView = $('#view-settings');
      if(!settingsView) return;
      if($('#finPlusRecurringItem')) return;

      const list = settingsView.querySelector('.list');
      if(!list) return;

      const div = document.createElement('div');
      div.className = 'item';
      div.id = 'finPlusRecurringItem';
      div.innerHTML = `
        <div class="left">
          <div class="ic">🔁</div>
          <div class="meta">
            <div class="t">Recurring</div>
            <div class="s">Add monthly recurring transactions</div>
          </div>
        </div>
        <div class="right">
          <div class="amt">→</div>
          <div class="date">Manage</div>
        </div>
      `;
      div.onclick = ()=> manageRecurring();
      list.appendChild(div);
    }

    function manageRecurring(){
      const defs = getRecurring();
      const action = prompt(`Recurring:\n- add\n- list\n- delete\n- run\n\nType one:`,'list');
      if(!action) return;
      const a = action.trim().toLowerCase();

      if(a==='list'){
        if(!defs.length) return window.toast?.('No recurring');
        alert(defs.map(d=>`• ${d.name} — day ${d.dayOfMonth} — ${d.type} ${(d.amount_cents/100).toFixed(2)}`).join('\n'));
        return;
      }

      if(a==='run'){
        runRecurringForMonth(window.state.month);
        return;
      }

      if(a==='delete'){
        if(!defs.length) return window.toast?.('No recurring');
        const name = prompt('Type recurring NAME to delete:\n\n' + defs.map(d=>`- ${d.name}`).join('\n'));
        if(!name) return;
        const next = defs.filter(d=> d.name.toLowerCase() !== name.trim().toLowerCase());
        setRecurring(next);
        window.toast?.('Deleted recurring');
        return;
      }

      if(a==='add'){
        const name = prompt('Name (e.g. Rent)','Rent');
        if(!name) return;
        const type = prompt('Type: income or expense','expense');
        if(!type || !['income','expense'].includes(type.trim().toLowerCase())) return window.toast?.('Type invalid');
        const amount = prompt('Amount (e.g. 500.00)','100.00');
        if(!amount) return;
        const day = prompt('Day of month (1-28)','1');
        if(!day) return;

        const cats = filterOutArchived(window.state.categories||[])
          .filter(c=>c.type===type.trim().toLowerCase())
          .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));
        if(!cats.length) return window.toast?.('No categories for that type');

        const cname = prompt(
          `Category NAME:\n\n`+cats.slice(0,15).map(c=>`- ${c.name}`).join('\n'),
          cats[0].name
        );
        if(!cname) return;
        const cat = cats.find(c=>c.name.toLowerCase()===cname.trim().toLowerCase()) || cats[0];

        const account = prompt('Account: Card/Cash/Bank','Card') || 'Card';

        defs.push({
          name,
          type: type.trim().toLowerCase(),
          amount_cents: toCents(amount),
          category_id: cat.id,
          account,
          dayOfMonth: parseInt(day,10)
        });
        setRecurring(defs);
        window.toast?.('Recurring saved');
      }
    }

    // ---------------- Boot plugin ----------------
    initHideAmounts();
    initLockTimeout();
    ensureInsightsSheet();
    injectTxTools();
    patchInsightsButton();
    patchCategoryManagerActions();
    patchBudgetsUI();
    patchDashboardBudgets();
    exposeRecurringUI();

    // run recurring whenever month changes (hook applyMonth)
    if(typeof window.applyMonth === 'function'){
      const _applyMonth = window.applyMonth;
      window.applyMonth = async function(){
        await _applyMonth();
        runRecurringForMonth(window.state.month);
      };
    }

    // also make select sheet scrollable always
    const mo = new MutationObserver(()=>{
      const list = document.getElementById('selectList');
      if(list){
        list.style.maxHeight = '52vh';
        list.style.overflow = 'auto';
        list.style.webkitOverflowScrolling = 'touch';
      }
    });
    mo.observe(document.body, {subtree:true, childList:true});

    console.log('[fin+] loaded');
  });
})();
JS;

echo $js;