<?php
// Budgets view (minimal):
// 1) Threshold badges + progress color
// 2) Suggested budget (avg last 3 months + 10%) + 1-tap apply
// 3) Group budgets (multi-category) stored in settings.budget_groups (JSON)
// 4) Calendar pacing (on track / over pace)
?>

<style>
  /* Scoped styles */
  #view-budgets .badge{
    display:inline-flex; align-items:center; gap:6px;
    font-size:11px; font-weight:900; letter-spacing:.1px;
    padding:4px 8px; border-radius:999px;
    border:1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.06);
    color: var(--muted);
  }
  html[data-theme="light"] #view-budgets .badge{ border-color: rgba(10,15,25,.12); background: rgba(10,15,25,.05); }
  #view-budgets .badge.warn{ color: var(--warn); border-color: color-mix(in oklab, var(--warn) 25%, rgba(255,255,255,.10)); }
  #view-budgets .badge.danger{ color: var(--danger); border-color: color-mix(in oklab, var(--danger) 25%, rgba(255,255,255,.10)); }

  #view-budgets .bud-top{ display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
  #view-budgets .bud-rhs{ display:flex; flex-direction:column; align-items:flex-end; gap:6px; }
  #view-budgets .bud-badges{ display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
  #view-budgets .hint{ margin-top:8px; font-size:12px; color: var(--muted); font-weight:800; }
  #view-budgets .hint a{ color: var(--text); text-decoration:none; border-bottom:1px dashed rgba(255,255,255,.18); }
  html[data-theme="light"] #view-budgets .hint a{ border-bottom-color: rgba(10,15,25,.20); }

  /* Progress colors */
  #view-budgets .bar > div.ok{ background: var(--success); }
  #view-budgets .bar > div.warn{ background: var(--warn); }
  #view-budgets .bar > div.danger{ background: var(--danger); }

  #view-budgets .muted{ color: var(--muted); font-size:12.5px; }
</style>

<div id="view-budgets" style="display:none;">
  <div class="row" style="justify-content:space-between; margin: 8px 0 12px;">
    <span class="pill" onclick="openBudgetSheet()">Add budget ➕</span>
    <span class="pill" onclick="openBudgetGroupsPrompt()">Groups 🧩</span>
  </div>

  <div class="card soft">
    <div class="list">
      <div class="list-head">
        <h3>Monthly budgets</h3>
        <span id="budMonth">—</span>
      </div>
      <div id="budList"></div>
    </div>
  </div>

  <div style="height:12px"></div>

  <div class="card soft">
    <div class="list">
      <div class="list-head">
        <h3>Group budgets</h3>
        <span id="budGroupsHint" class="muted">—</span>
      </div>
      <div id="budGroupList"></div>
    </div>
  </div>
</div>

<script>
  // ===== Helpers (local) =====
  function _bud_daysInMonth(ym01){
    const [y,m] = ym01.split('-').map(n=>parseInt(n,10));
    return new Date(y, m, 0).getDate();
  }
  function _bud_isCurrentMonth(ym01){
    const d = new Date();
    const y = d.getFullYear();
    const mo = String(d.getMonth()+1).padStart(2,'0');
    return ym01 === `${y}-${mo}-01`;
  }
  function _bud_dayOfMonth(ym01){
    if(!_bud_isCurrentMonth(ym01)) return _bud_daysInMonth(ym01);
    return new Date().getDate();
  }
  function _bud_status(spent, limit){
    if(!limit) return { pct:0, cls:'ok', badge:null };
    const pct = spent/limit;
    if(pct >= 1) return { pct, cls:'danger', badge:{ text:'Over budget', cls:'danger' } };
    if(pct >= 0.85) return { pct, cls:'warn', badge:{ text:'Near limit', cls:'warn' } };
    return { pct, cls:'ok', badge:null };
  }
  function _bud_pace(spent, limit, ym01){
    if(!limit) return null;
    const days = _bud_daysInMonth(ym01);
    const day = Math.min(_bud_dayOfMonth(ym01), days);
    const expected = Math.round(limit * (day/days));
    const over = spent - expected;
    if(over > 0){
      return { cls:'warn', label:`Over pace (+${fmtMoneyFromCents(over)} vs ${fmtMoneyFromCents(expected)})` };
    }
    return { cls:'ok', label:`On track (≤ ${fmtMoneyFromCents(expected)} expected)` };
  }

  function _safeJsonParse(s, fallback){
    try{ const v = JSON.parse(s); return (v && typeof v === 'object') ? v : fallback; }catch(_e){ return fallback; }
  }

  // ===== Suggestions: avg last 3 months + 10% =====
  async function _bud_computeSuggestions(monthYm01){
    const base = new Date(monthYm01 + 'T00:00:00');
    const sums = {}; // cid -> total across 3 months

    for(let i=1;i<=3;i++){
      const d = new Date(base.getFullYear(), base.getMonth()-i, 1);
      const from = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-01`;
      const to = monthEnd(from);
      const tx = await apiGet(`${API}?action=transactions&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&type=expense`);
      (tx.items||[]).forEach(t=>{
        const cid = String(t.category_id);
        const c = parseInt(t.amount_cents,10)||0;
        sums[cid] = (sums[cid]||0) + c;
      });
    }

    const out = {};
    Object.keys(sums).forEach(cid=>{
      const avg = Math.round(sums[cid] / 3);
      const sugg = Math.round(avg * 1.10);
      if(sugg > 0) out[cid] = sugg;
    });
    return out;
  }

  // ===== Group budgets: stored in settings.budget_groups (JSON) =====
  function _bud_getGroups(){
    const raw = state?.settings?.budget_groups ? _safeJsonParse(state.settings.budget_groups, []) : [];
    return Array.isArray(raw) ? raw : [];
  }

  // Minimal manager: prompt-based (no extra sheets)
  async function openBudgetGroupsPrompt(){
    const groups = _bud_getGroups();
    const action = prompt(
      'Groups\n\nType:\n- list (to see groups)\n- add (new group)\n- edit:<id>\n- del:<id>\n\nExample: edit:food',
      'list'
    );
    if(action === null) return;
    const a = action.trim();
    if(a === 'list'){
      const lines = groups.map(g=>`- ${g.id}: ${g.name} (${(g.category_ids||[]).length} cats, limit ${fmtMoneyFromCents(parseInt(g.limit_cents||0,10)||0)})`);
      alert(lines.length ? lines.join('\n') : 'No groups yet');
      return;
    }
    if(a === 'add') return _bud_groupEditor(null);
    if(a.startsWith('edit:')) return _bud_groupEditor(a.slice(5).trim());
    if(a.startsWith('del:')){
      const id = a.slice(4).trim();
      const g = groups.find(x=>String(x.id)===String(id));
      if(!g) return alert('Group not found');
      if(!confirm(`Delete group “${g.name}”?`)) return;
      const next = groups.filter(x=>String(x.id)!==String(id));
      return _bud_saveGroups(next);
    }
    alert('Unknown action');
  }

  async function _bud_groupEditor(groupId){
    const groups = _bud_getGroups();
    const existing = groupId ? groups.find(x=>String(x.id)===String(groupId)) : null;

    const name = prompt('Group name', existing ? (existing.name||'') : '');
    if(name === null) return;
    const icon = prompt('Icon (emoji)', existing ? (existing.icon||'🧩') : '🧩');
    if(icon === null) return;
    const limitStr = prompt('Monthly limit (e.g. 400)', existing ? ((parseInt(existing.limit_cents||0,10)/100).toFixed(2)) : '');
    if(limitStr === null) return;

    const expenseCats = (state.categories||[])
      .filter(c=>c.type==='expense')
      .sort((a,b)=> (a.sort_order??0)-(b.sort_order??0));
    const help = expenseCats.map(c=>`${c.id}:${c.name}`).join(', ');
    const curIds = existing ? (existing.category_ids||[]).join(',') : '';
    const idsStr = prompt('Category IDs (comma separated). Available: ' + help, curIds);
    if(idsStr === null) return;
    const ids = idsStr.split(',').map(s=>parseInt(s.trim(),10)).filter(n=>n>0);

    const limitCents = Math.max(0, Math.round(parseFloat(String(limitStr).replace(',','.')) * 100) || 0);
    const id = existing ? existing.id : ((name||'group').toLowerCase().trim().replace(/[^a-z0-9_-]+/g,'-') || String(Date.now()));

    const next = groups.filter(x=>String(x.id)!==String(id));
    next.push({ id, name: name.trim(), icon: (icon||'🧩').trim(), limit_cents: limitCents, category_ids: ids });
    await _bud_saveGroups(next);
  }

  async function _bud_saveGroups(groups){
    await apiSend(`${API}?action=settings_set`, 'POST', { k:'budget_groups', v: JSON.stringify(groups) });
    state.settings = state.settings || {};
    state.settings.budget_groups = JSON.stringify(groups);
    toast('Saved');
    if(PAGE === 'budgets') await window.__bud_refreshBudgets();
  }

  // ===== Apply suggested limit (1 tap) =====
  async function setBudgetToSuggested(categoryId){
    const cid = String(categoryId);
    const sugg = window.__bud_sugg?.[cid] || 0;
    if(!sugg) return toast('No suggestion');
    try{
      await apiSend(`${API}?action=budgets`, 'POST', { month: state.month, category_id: parseInt(cid,10), limit: (sugg/100).toFixed(2) });
      toast('Set to suggested');
      await window.__bud_refreshBudgets();
    }catch(e){
      toast('Error: ' + e.message);
    }
  }

  // ===== Editing existing budgets with the existing sheetBudget (index.php) =====
  function editBudget(categoryId, currentLimitCents){
    const cid = String(categoryId);
    const c = (state.categories||[]).find(x=>String(x.id)===cid);
    if(!c) return toast('Cannot edit (missing category)');

    // open existing budget sheet
    document.getElementById('budCur').textContent = symbol();
    document.getElementById('budLimit').value = ((parseInt(currentLimitCents,10)||0)/100).toFixed(2);
    document.getElementById('budCategory').value = cid;
    if(typeof setBudgetCategoryLabel === 'function') setBudgetCategoryLabel();
    openSheet('sheetBudget');
    setTimeout(()=> document.getElementById('budLimit')?.focus(), 120);
  }

  // ===== Renderers =====
  function _bud_renderMonthlyBudgets(month, budgets, suggByCat){
    const list = document.getElementById('budList');
    if(!list) return;

    if(!budgets.length){
      list.innerHTML = `<div class="item" style="cursor:default"><div class="left"><div class="ic">🎯</div><div class="meta"><div class="t">No budgets yet</div><div class="s">Tap “Add budget”</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;
      return;
    }

    list.innerHTML = budgets.map(b=>{
      const spent = parseInt(b.used_cents,10)||0;
      const limit = parseInt(b.limit_cents,10)||0;
      const st = _bud_status(spent, limit);
      const pct = limit>0 ? Math.max(0, Math.min(1, st.pct)) : 0;
      const badgeHtml = st.badge ? `<span class="badge ${st.badge.cls}">${st.badge.text}</span>` : '';
      const pace = _bud_pace(spent, limit, month);
      const paceHtml = pace ? `<span class="badge ${pace.cls==='warn'?'warn':''}">${escapeHtml(pace.label)}</span>` : '';

      const sugg = parseInt(suggByCat[String(b.category_id)]||0,10)||0;
      const hintHtml = (sugg>0)
        ? `<div class="hint">Suggested: ${escapeHtml(fmtMoneyFromCents(sugg))} · <a href="#" onclick="event.preventDefault(); setBudgetToSuggested(${b.category_id})">Set to suggested</a></div>`
        : `<div class="hint">Suggested: —</div>`;

      return `
        <div class="budget" onclick="editBudget(${b.category_id}, ${limit})">
          <div class="bud-top">
            <div class="name"><span style="font-size:18px">${escapeHtml(b.category_icon || '🏷️')}</span> ${escapeHtml(b.category_name)}</div>
            <div class="bud-rhs">
              <div style="color:var(--muted); font-weight:950; font-variant-numeric:tabular-nums;">${escapeHtml(fmtMoneyFromCents(limit))}</div>
              <div class="bud-badges">${badgeHtml}</div>
            </div>
          </div>
          <div class="bar"><div class="${st.cls}" style="width:${Math.round(pct*100)}%"></div></div>
          <div class="foot">
            <span>${escapeHtml(fmtMoneyFromCents(spent))} used</span>
            <span>${escapeHtml(fmtMoneyFromCents(Math.max(0, limit-spent)))} left</span>
          </div>
          <div class="bud-badges" style="margin-top:8px;">${paceHtml}</div>
          ${hintHtml}
        </div>
      `;
    }).join('');
  }

  function _bud_renderGroupBudgets(month, groups, txExpenseItems){
    const list = document.getElementById('budGroupList');
    const hint = document.getElementById('budGroupsHint');
    if(hint) hint.textContent = groups.length ? `${groups.length} groups` : 'No groups';
    if(!list) return;

    if(!groups.length){
      list.innerHTML = `<div class="item" style="cursor:default"><div class="left"><div class="ic">🧩</div><div class="meta"><div class="t">No group budgets</div><div class="s">Tap “Groups” to add</div></div></div><div class="right"><div class="amt">—</div><div class="date">—</div></div></div>`;
      return;
    }

    const spentByCat = {};
    (txExpenseItems||[]).forEach(t=>{
      const cid = String(t.category_id);
      spentByCat[cid] = (spentByCat[cid]||0) + (parseInt(t.amount_cents,10)||0);
    });

    list.innerHTML = groups.map(g=>{
      const limit = parseInt(g.limit_cents||0,10)||0;
      const ids = (g.category_ids||[]).map(x=>String(x));
      const spent = ids.reduce((acc, cid)=> acc + (spentByCat[cid]||0), 0);
      const st = _bud_status(spent, limit);
      const pct = limit>0 ? Math.max(0, Math.min(1, st.pct)) : 0;
      const badgeHtml = st.badge ? `<span class="badge ${st.badge.cls}">${st.badge.text}</span>` : '';
      const pace = _bud_pace(spent, limit, month);
      const paceHtml = pace ? `<span class="badge ${pace.cls==='warn'?'warn':''}">${escapeHtml(pace.label)}</span>` : '';

      return `
        <div class="budget" onclick="openBudgetGroupsPrompt()">
          <div class="bud-top">
            <div class="name"><span style="font-size:18px">${escapeHtml(g.icon || '🧩')}</span> ${escapeHtml(g.name || 'Group')}</div>
            <div class="bud-rhs">
              <div style="color:var(--muted); font-weight:950; font-variant-numeric:tabular-nums;">${escapeHtml(fmtMoneyFromCents(limit))}</div>
              <div class="bud-badges">${badgeHtml}</div>
            </div>
          </div>
          <div class="bar"><div class="${st.cls}" style="width:${Math.round(pct*100)}%"></div></div>
          <div class="foot">
            <span>${escapeHtml(fmtMoneyFromCents(spent))} used</span>
            <span>${escapeHtml(fmtMoneyFromCents(Math.max(0, limit-spent)))} left</span>
          </div>
          <div class="bud-badges" style="margin-top:8px;">${paceHtml}</div>
          <div class="hint">Categories: ${(ids.length)} · Tap to manage</div>
        </div>
      `;
    }).join('');
  }

  // ===== Expose hook for index.php =====
  window.__bud_refreshBudgets = async function(){
    document.getElementById('budMonth').textContent = monthLabel(state.month);

    const month = state.month;
    const from = month;
    const to = monthEnd(month);

    const budgets = await apiGet(`${API}?action=budgets&month=${encodeURIComponent(month)}`);
    const tx = await apiGet(`${API}?action=transactions&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&type=expense`);

    // suggestions
    const suggByCat = await _bud_computeSuggestions(month);
    window.__bud_sugg = suggByCat;

    // groups
    const groups = _bud_getGroups();

    _bud_renderMonthlyBudgets(month, budgets.items||[], suggByCat);
    _bud_renderGroupBudgets(month, groups, tx.items||[]);
  };
</script>
