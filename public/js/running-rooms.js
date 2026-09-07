/* ════════════════════════════════════════════════════════════════════════
 * RUNNING ROOM REGISTER
 * Self-contained register: Dashboard / Check In-Out / Daily / Monthly /
 * Matters Arising / Challenges Summary (admin) / Settings (admin).
 * Data is loaded from the Laravel API in /running-rooms/api/* and every
 * mutation re-renders the active panel.
 * ════════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  const BOOT = window.__RR_BOOT__ || { isAttendant: false, isAdmin: false, initialTab: 'dashboard', data: null };
  const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  const state = {
    user: null,
    rooms: [],
    records: [],
    matters: [],
    designations: [],
    categories: [],
    tab: BOOT.initialTab || 'dashboard',
    // ui state
    dailyDate: todayStr(),
    monthKey: currentMonthKey(),
    monthRoom: 'all',
    mattersFilter: 'all',
    challengeFilters: { room: 'all', status: 'all', category: 'all', from: '', to: '' },
    editingMatter: null,
  };

  /* ── date helpers ─────────────────────────────────────────────────────── */
  function todayStr() {
    return new Date().toISOString().slice(0, 10);
  }

  function dateOnly(v) {
    return String(v || '').slice(0, 10);
  }

  function currentMonthKey() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
  }

  function daysInMonth(monthKey) {
    const [y, m] = monthKey.split('-').map(Number);
    return new Date(y, m, 0).getDate();
  }

  function monthLabel(monthKey) {
    const [y, m] = monthKey.split('-').map(Number);
    const name = new Date(y, m - 1, 1).toLocaleString('en-KE', { month: 'long' });
    return name + ' ' + y;
  }

  function fmtDate(iso) {
    if (!iso) return '—';
    const d = new Date(String(iso).slice(0, 10) + 'T00:00:00');
    return d.toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function fmtTime(t) {
    if (!t) return '—';
    return String(t).slice(0, 5);
  }

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  /* ── data helpers ─────────────────────────────────────────────────────── */
  function roomById(id) {
    return state.rooms.find((r) => Number(r.id) === Number(id));
  }

  function roomName(id) {
    const r = roomById(id);
    return r ? r.name : '—';
  }

  function roomLabel(r) {
    return r ? (r.depot ? `${r.name} (${r.depot})` : r.name) : '—';
  }

  function recordsForRoom(roomId) {
    return state.records.filter((r) => Number(r.room_id) === Number(roomId));
  }

  function currentlyIn(roomId) {
    return recordsForRoom(roomId).filter((r) => r.status === 'in');
  }

  function availableBedsFor(roomId, date) {
    const room = roomById(roomId);
    if (!room || !Array.isArray(room.bedList)) return [];
    const occupied = recordsForRoom(roomId)
      .filter((r) => r.bed_no && wasOccupiedOn(r, date))
      .map((r) => String(r.bed_no));
    return room.bedList
      .filter((b) => b.is_usable && !occupied.includes(String(b.bed_no)));
  }

  function wasOccupiedOn(record, date) {
    if (!record.arrival_date || dateOnly(record.arrival_date) > date) return false;
    let end = date;
    if (record.status === 'out' && record.departure_date) {
      end = dateOnly(record.departure_date);
    }
    return date <= end;
  }

  function occupiedOn(roomId, date) {
    return recordsForRoom(roomId).filter((r) => wasOccupiedOn(r, date)).length;
  }

  function vacantOn(roomId, date) {
    const room = roomById(roomId);
    return room ? Math.max(0, room.beds - occupiedOn(roomId, date)) : 0;
  }

  function mattersForRoom(roomId) {
    return state.matters.filter((m) => Number(m.room_id) === Number(roomId));
  }

  /* ── API ──────────────────────────────────────────────────────────────── */
  async function api(path, method = 'GET', body = null) {
    const opts = { method, headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json' } };
    if (body instanceof FormData) {
      opts.body = body;
    } else if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const resp = await fetch(path, opts);
    if (!resp.ok) {
      let msg = 'Request failed (' + resp.status + ')';
      try {
        const data = await resp.json();
        if (data.error) msg = data.error;
        else if (data.errors) msg = Object.values(data.errors).flat().join(' ');
      } catch (e) { /* ignore */ }
      throw new Error(msg);
    }
    return resp.json();
  }

  async function loadData() {
    let data;
    if (BOOT.data) {
      data = BOOT.data;
      BOOT.data = null;
    } else {
      data = await api('/running-rooms/api/data');
    }
    state.user = data.user;
    state.rooms = data.rooms || [];
    state.records = data.records || [];
    state.matters = data.matters || [];
    state.designations = data.designations || [];
    state.categories = data.categories || [];
  }

  /* ── toast ────────────────────────────────────────────────────────────── */
  function toast(msg, isError) {
    const el = document.getElementById('rrToast');
    if (el) {
      el.textContent = msg;
      el.className = 'rr-toast show' + (isError ? ' err' : '');
      clearTimeout(el.__t);
      el.__t = setTimeout(() => el.classList.remove('show'), 3000);
    }
  }

  /* ── navigation ───────────────────────────────────────────────────────── */
  function goTab(tab) {
    state.tab = tab;
    renderAll();
  }

  /* ══════════════════════════ DASHBOARD ═════════════════════════════════ */
  function renderDashboard() {
    const totalBeds = state.rooms.reduce((s, r) => s + r.beds, 0);
    const totalOcc = state.rooms.reduce((s, r) => s + currentlyIn(r.id).length, 0);
    const totalVacant = Math.max(0, totalBeds - totalOcc);

    const kpis = `
      <div class="rr-kpi-row">
        <div class="rr-kpi"><div class="rr-kpi-n">${totalBeds}</div><div class="rr-kpi-l">Beds</div></div>
        <div class="rr-kpi rr-k-red"><div class="rr-kpi-n">${totalOcc}</div><div class="rr-kpi-l">Occupied</div></div>
        <div class="rr-kpi rr-k-green"><div class="rr-kpi-n">${totalVacant}</div><div class="rr-kpi-l">Vacant</div></div>
      </div>`;

    const cards = state.rooms.map((room) => {
      const occ = currentlyIn(room.id).length;
      const vacant = Math.max(0, room.beds - occ);
      const full = vacant <= 0;
      const over = occ > room.beds;
      const openMatters = mattersForRoom(room.id).filter((m) => m.status === 'open').length;
      let berths = '';
      for (let i = 0; i < room.beds; i++) {
        berths += `<span class="rr-berth ${i < occ ? 'filled' : 'empty'}"></span>`;
      }
      const flags = [];
      if (full) flags.push('<span class="rr-flag red">Full</span>');
      if (over) flags.push('<span class="rr-flag red">Over capacity</span>');
      if (openMatters > 0) flags.push(`<span class="rr-flag amber">${openMatters} open ${openMatters === 1 ? 'matter' : 'matters'}</span>`);

      return `
        <div class="rr-room-card">
          <div class="rr-room-head">
            <span class="rr-room-name">${esc(room.name)}</span>
            <span class="rr-room-meta"><b>${occ}</b> / ${room.beds} beds</span>
          </div>
          ${room.depot ? `<div class="rr-room-depot">${esc(room.depot)}</div>` : ''}
          <div class="rr-berths">${berths}</div>
          <div class="rr-room-flags">${flags.join('') || '<span class="rr-flag gray">Open</span>'}</div>
        </div>`;
    }).join('');

    return `
      ${kpis}
      <h2>Rooms</h2>
      <div class="rr-sub">Live bed occupancy per running room.</div>
      ${state.rooms.length ? `<div class="rr-room-grid">${cards}</div>` : '<div class="rr-empty">No rooms available.</div>'}`;
  }

  /* ═══════════════════════════ CHECK IN / OUT ═══════════════════════════ */
  function renderCheckIn() {
    const attendantRoomId = state.user?.roomId;
    const roomOptions = state.rooms.map((r) =>
      `<option value="${r.id}"${Number(r.id) === Number(attendantRoomId) ? ' selected' : ''}>${esc(roomLabel(r))}</option>`
    ).join('');

    const defaultRoomId = state.rooms.length ? (Number(attendantRoomId) || state.rooms[0].id) : null;
    const defaultDate = todayStr();
    const defaultBeds = defaultRoomId ? availableBedsFor(defaultRoomId, defaultDate) : [];
    const bedOptions = defaultBeds.length
      ? defaultBeds.map((b, index) => `<option value="${esc(b.bed_no)}"${index === 0 ? ' selected' : ''}>${esc(b.bed_no)}</option>`).join('')
      : '<option value="">No available beds</option>';

    const form = `
      <h2>Check In / Out</h2>
      <div class="rr-sub">Search the crew by staff number or name, then allocate a bed. Only existing crew members eligible to use running rooms may check in.</div>
      <div class="rr-form-card">
        <form id="rrCheckinForm" class="rr-form-grid">
          <div class="rr-field rr-field-wide">
            <label>Search staff *</label>
            <div class="rr-combobox">
              <input type="text" name="staff_no" id="rrStaffNo" required maxlength="64" placeholder="Type staff number or name…" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list">
              <ul id="rrStaffSearchList" class="rr-search-list" hidden></ul>
            </div>
          </div>
          <div class="rr-field">
            <label>Name</label>
            <input type="text" name="name" id="rrMemberName" readonly placeholder="Auto-fills from staff number">
          </div>
          <div class="rr-field">
            <label>Designation</label>
            <input type="text" name="designation" id="rrMemberDesig" readonly placeholder="Auto-fills from staff number">
          </div>
          <div class="rr-field rr-field-wide">
            <div id="rrStaffStatus" class="rr-staff-status"></div>
          </div>
          <div class="rr-field">
            <label>Room *</label>
            <select name="room_id" id="rrCheckinRoom" required>${roomOptions}</select>
          </div>
          <div class="rr-field">
            <label>Bed</label>
            <select name="bed_no" id="rrCheckinBed">
              ${bedOptions}
            </select>
          </div>
          <div class="rr-field">
            <label>Arrival Date *</label>
            <input type="date" name="arrival_date" required value="${todayStr()}">
          </div>
          <div class="rr-field">
            <label>Arrival Time *</label>
            <input type="time" name="arrival_time" required value="${new Date().toTimeString().slice(0, 5)}">
          </div>
          <div class="rr-field">
            <label>Remarks</label>
            <textarea name="remarks" rows="1"></textarea>
          </div>
          <div class="rr-form-actions" style="grid-column:1/-1">
            <button type="submit" class="rr-btn" disabled>Check in</button>
          </div>
        </form>
      </div>`;

    const rows = state.rooms.map((room) => {
      const inList = currentlyIn(room.id);
      const body = inList.length
        ? inList.map((r) => `
          <tr data-record="${r.id}">
            <td>${esc(r.name)}</td>
            <td>${esc(r.staff_no || '—')}</td>
            <td>${esc(r.designation)}</td>
            <td>${esc(r.bed_no || '—')}</td>
            <td class="rr-cell-arr">${fmtDate(r.arrival_date)} ${fmtTime(r.arrival_time)}</td>
            <td><span class="rr-badge in">In</span></td>
            <td class="rr-cell-out">
              <button class="rr-btn amber sm" data-out="${r.id}">Check out</button>
            </td>
          </tr>`).join('')
        : `<tr><td colspan="7"><div class="rr-empty">Nobody currently checked in at ${esc(room.name)}.</div></td></tr>`;

      return `
        <div class="rr-sub" style="margin-top:18px"><b>${esc(room.name)}</b> — ${inList.length} currently in</div>
        <div class="rr-table-wrap">
          <table class="rr-table">
            <thead><tr><th>Name</th><th>Staff No.</th><th>Designation</th><th>Bed</th><th>Arrived</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>${body}</tbody>
          </table>
        </div>`;
    }).join('');

    return `
      ${form}
      <h2>Currently checked in</h2>
      <div class="rr-sub">Live list of crew members staying in the rooms.</div>
      ${state.rooms.length ? rows : '<div class="rr-empty">No rooms available.</div>'}`;
  }

  function bindCheckIn(root) {
    const form = $('#rrCheckinForm', root);
    const staffInput = $('#rrStaffNo', root);
    const nameEl = $('#rrMemberName', root);
    const desigEl = $('#rrMemberDesig', root);
    const statusEl = $('#rrStaffStatus', root);
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    let selectedMember = null;

    function setStaffStatus(className, html) {
      if (!statusEl) return;
      statusEl.className = 'rr-staff-status' + (className ? ' ' + className : '');
      statusEl.innerHTML = html || '';
    }

    function gateSubmit() {
      if (submitBtn) submitBtn.disabled = !(selectedMember && selectedMember.room_eligible);
    }

    async function lookupStaff() {
      const staffNo = staffInput ? staffInput.value.trim() : '';
      selectedMember = null;
      gateSubmit();
      if (nameEl) nameEl.value = '';
      if (desigEl) desigEl.value = '';
      if (!staffNo) { setStaffStatus('', ''); return; }
      setStaffStatus('', '<span class="rr-muted">Looking up…</span>');
      try {
        const res = await api('/running-rooms/api/crew/' + encodeURIComponent(staffNo));
        const m = res.member;
        selectedMember = m;
        if (nameEl) nameEl.value = m.name;
        if (desigEl) desigEl.value = m.designation;
        if (!m.room_eligible) {
          setStaffStatus('err', `${esc(m.name)} (${esc(m.designation)}) — <b>not eligible to use running rooms</b>`);
        } else {
          setStaffStatus('ok', `${esc(m.name)} — ${esc(m.designation)} <span class="rr-badge ok">Room eligible</span>`);
        }
        gateSubmit();
      } catch (err) {
        setStaffStatus('err', esc(err.message));
      }
    }

    if (staffInput) {
      const searchList = $('#rrStaffSearchList', root);
      let searchTimer = null;
      let searchResults = [];
      let searchIndex = -1;

      function closeSearch() {
        searchIndex = -1;
        if (searchList) {
          searchList.hidden = true;
          searchList.innerHTML = '';
        }
        if (staffInput) staffInput.setAttribute('aria-expanded', 'false');
      }

      function renderSearchList() {
        if (!searchList) return;
        if (!searchResults.length) {
          searchList.hidden = true;
          searchList.innerHTML = '';
          return;
        }
        searchList.innerHTML = searchResults.map((m, i) =>
          `<li data-search-index="${i}" class="${i === searchIndex ? 'rr-search-active' : ''}" ${m.room_eligible ? '' : 'data-room-ineligible="1"'}>
            <span class="rr-search-name">${esc(m.name)} <small>(${esc(m.staff_no)})</small></span>
            <span class="rr-search-meta">${esc(m.designation)}${m.room_eligible ? '' : ' · no room access'}</span>
          </li>`
        ).join('');
        searchList.hidden = false;
        if (staffInput) staffInput.setAttribute('aria-expanded', 'true');
      }

      async function runSearch(q) {
        if (q.length < 2) { closeSearch(); return; }
        try {
          const res = await api('/running-rooms/api/crew/search?q=' + encodeURIComponent(q) + '&limit=12');
          searchResults = Array.isArray(res.results) ? res.results : [];
          searchIndex = searchResults.length ? 0 : -1;
          renderSearchList();
        } catch (err) {
          closeSearch();
        }
      }

      function pickResult(index) {
        const m = searchResults[index];
        if (!m) return;
        staffInput.value = m.staff_no;
        closeSearch();
        lookupStaff();
      }

      staffInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = staffInput.value.trim();
        selectedMember = null;
        gateSubmit();
        if (nameEl) nameEl.value = '';
        if (desigEl) desigEl.value = '';
        if (!q) { setStaffStatus('', ''); closeSearch(); return; }
        setStaffStatus('', '<span class="rr-muted">Searching crew…</span>');
        searchTimer = setTimeout(() => runSearch(q), 220);
      });

      staffInput.addEventListener('keydown', (e) => {
        const open = searchList && !searchList.hidden;
        if (e.key === 'ArrowDown' && open) {
          e.preventDefault();
          searchIndex = searchIndex < searchResults.length - 1 ? searchIndex + 1 : 0;
          renderSearchList();
        } else if (e.key === 'ArrowUp' && open) {
          e.preventDefault();
          searchIndex = searchIndex > 0 ? searchIndex - 1 : searchResults.length - 1;
          renderSearchList();
        } else if (e.key === 'Enter' && open) {
          e.preventDefault();
          const exact = searchResults.find((r) => String(r.staff_no) === staffInput.value.trim());
          if (exact) pickResult(searchResults.indexOf(exact));
          else if (searchIndex >= 0) pickResult(searchIndex);
          else { closeSearch(); lookupStaff(); }
        } else if (e.key === 'Escape') {
          closeSearch();
        }
      });

      if (searchList) {
        searchList.addEventListener('mousedown', (e) => {
          const li = e.target.closest('li[data-search-index]');
          if (li) {
            e.preventDefault();
            pickResult(Number(li.dataset.searchIndex));
          }
        });
      }

      staffInput.addEventListener('blur', () => {
        setTimeout(() => closeSearch(), 150);
      });
    }
    gateSubmit();

    const roomSel = $('#rrCheckinRoom', root);
    const bedSel = $('#rrCheckinBed', root);
    const dateSel = form ? form.querySelector('[name="arrival_date"]') : null;

    function refreshBeds() {
      if (!roomSel || !bedSel) return;
      const roomId = roomSel.value;
      const date = dateSel ? dateSel.value : todayStr();
      const beds = availableBedsFor(roomId, date);
      bedSel.innerHTML = beds.length
        ? beds.map((b, index) => `<option value="${esc(b.bed_no)}"${index === 0 ? ' selected' : ''}>${esc(b.bed_no)}</option>`).join('')
        : '<option value="">No available beds</option>';
    }

    if (roomSel) roomSel.addEventListener('change', refreshBeds);
    if (dateSel) dateSel.addEventListener('change', refreshBeds);
    refreshBeds();

    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
          await api('/running-rooms/api/records', 'POST', payload);
          toast('Crew member checked in.');
          await loadData();
          renderAll();
          goTab('checkin');
        } catch (err) {
          toast(err.message, true);
          btn.disabled = false;
        }
      });
    }

    $$('[data-out]', root).forEach((btn) => {
      btn.addEventListener('click', () => {
        const recordId = btn.dataset.out;
        const tr = btn.closest('tr');
        const outCell = $('.rr-cell-out', tr);
        const today = todayStr();
        const now = new Date().toTimeString().slice(0, 5);
        outCell.innerHTML = `
          <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            <input type="date" value="${today}" data-out-date>
            <input type="time" value="${now}" data-out-time style="width:88px">
            <button class="rr-btn green sm" data-out-save="${recordId}">Save</button>
            <button class="rr-btn ghost sm" data-out-cancel>Cancel</button>
          </div>`;
        const saveBtn = $('[data-out-save]', tr);
        saveBtn.addEventListener('click', async () => {
          try {
            await api('/running-rooms/api/records/' + recordId + '/checkout', 'POST', {
              departure_date: $('[data-out-date]', tr).value,
              departure_time: $('[data-out-time]', tr).value,
            });
            toast('Checked out.');
            await loadData();
            renderAll();
            goTab('checkin');
          } catch (err) {
            toast(err.message, true);
          }
        });
        $('[data-out-cancel]', tr).addEventListener('click', () => {
          renderAll();
          goTab('checkin');
        });
      });
    });
  }

  /* ═══════════════════════════ DAILY REPORT ═════════════════════════════ */
  function renderDaily() {
    const date = state.dailyDate;
    const header = `
      <h2>Daily Report</h2>
      <div class="rr-sub">Occupancy and arrivals/departures for a selected date.</div>
      <div class="rr-toolbar rr-print-hide">
        <div class="rr-field"><label>Date</label><input type="date" id="rrDailyDate" value="${date}"></div>
        <button class="rr-btn" id="rrDailyPrint">Print</button>
      </div>
      <div class="rr-print-area">`;

    const rows = state.rooms.map((room) => {
      const occ = occupiedOn(room.id, date);
      const pct = room.beds ? Math.round((occ / room.beds) * 100) : 0;
      const arrivals = recordsForRoom(room.id).filter((r) => dateOnly(r.arrival_date) === date);
      const departures = recordsForRoom(room.id).filter((r) => r.status === 'out' && dateOnly(r.departure_date) === date);
      return `
        <div class="rr-sub" style="margin-top:16px"><b>${esc(room.name)}</b> — ${occ}/${room.beds} occupied (${pct}%)</div>
        <div class="rr-table-wrap">
          <table class="rr-table">
            <thead><tr><th>Name</th><th>Staff No.</th><th>Designation</th><th>Bed</th><th>Arrival</th><th>Departure</th></tr></thead>
            <tbody>${[
              ...arrivals.map((r) => `<tr><td>${esc(r.name)}</td><td>${esc(r.staff_no || '—')}</td><td>${esc(r.designation)}</td><td>${esc(r.bed_no || '—')}</td><td>${fmtDate(r.arrival_date)} ${fmtTime(r.arrival_time)}</td><td>—</td></tr>`),
              ...departures.map((r) => `<tr><td>${esc(r.name)}</td><td>${esc(r.staff_no || '—')}</td><td>${esc(r.designation)}</td><td>${esc(r.bed_no || '—')}</td><td>—</td><td>${fmtDate(r.departure_date)} ${fmtTime(r.departure_time)}</td></tr>`),
            ].join('') || '<tr><td colspan="6"><div class="rr-empty">No arrivals or departures on this date.</div></td></tr>'}
            </tbody>
          </table>
        </div>`;
    }).join('');

    return header + rows + '</div>';
  }

  function bindDaily(root) {
    const dateInput = $('#rrDailyDate', root);
    if (dateInput) {
      dateInput.addEventListener('change', (e) => {
        state.dailyDate = e.target.value;
        renderAll();
        goTab('daily');
      });
    }
    const printBtn = $('#rrDailyPrint', root);
    if (printBtn) printBtn.addEventListener('click', () => window.print());
  }

  /* ═════════════════════════ MONTHLY REPORT ═════════════════════════════ */
  function renderMonthly() {
    const header = `
      <h2>Monthly Report</h2>
      <div class="rr-sub">Monthly occupancy summary per room, or a day-by-day breakdown for one room.</div>
      <div class="rr-toolbar rr-print-hide">
        <div class="rr-field"><label>Month</label><input type="month" id="rrMonthKey" value="${state.monthKey}"></div>
        ${BOOT.isAdmin ? `<div class="rr-field"><label>Room</label><select id="rrMonthRoom">${['all', ...state.rooms].map((r) => r === 'all' ? '<option value="all">All rooms</option>' : `<option value="${r.id}"${Number(r.id) === Number(state.monthRoom) ? ' selected' : ''}>${esc(roomLabel(r))}</option>`).join('')}</select></div>` : ''}
        <button class="rr-btn" id="rrMonthlyPrint">Print</button>
      </div>
      <div class="rr-print-area">`;

    const month = state.monthKey;
    const days = daysInMonth(month);

    if (BOOT.isAdmin && state.monthRoom !== 'all') {
      const room = roomById(state.monthRoom);
      const body = buildMonthlyRoomBody(room, month, days);
      return header + body + '</div>';
    }

    const body = state.rooms.map((room) => {
      let bedNights = 0;
      let maxOcc = 0;
      let maxDay = null;
      let arrivals = 0;
      let departures = 0;
      for (let d = 1; d <= days; d++) {
        const date = month + '-' + String(d).padStart(2, '0');
        const occ = occupiedOn(room.id, date);
        bedNights += occ;
        if (occ > maxOcc) { maxOcc = occ; maxDay = d; }
        arrivals += recordsForRoom(room.id).filter((r) => dateOnly(r.arrival_date) === date).length;
        departures += recordsForRoom(room.id).filter((r) => r.status === 'out' && dateOnly(r.departure_date) === date).length;
      }
      const avgPct = room.beds && days ? Math.round((bedNights / (room.beds * days)) * 100) : 0;
      return `
        <tr>
          <td>${esc(room.name)}</td>
          <td>${avgPct}%</td>
          <td>${bedNights}</td>
          <td>${arrivals}</td>
          <td>${departures}</td>
          <td>${mattersForRoom(room.id).filter((m) => String(m.date).slice(0, 7) === month).length}</td>
        </tr>`;
    }).join('');

    return header + `
      <div class="rr-sub" style="margin-top:16px"><b>Network summary — ${monthLabel(month)}</b></div>
      <div class="rr-table-wrap">
        <table class="rr-table">
          <thead><tr><th>Room</th><th>Avg occupancy</th><th>Bed-nights</th><th>Arrivals</th><th>Departures</th><th>Matters</th></tr></thead>
          <tbody>${state.rooms.length ? body : '<tr><td colspan="6"><div class="rr-empty">No rooms available.</div></td></tr>'}</tbody>
        </table>
      </div>
      </div>`;
  }

  function buildMonthlyRoomBody(room, month, days) {
    let maxOcc = 0;
    let maxDay = null;
    let totalArrivals = 0;
    let totalDepartures = 0;
    const rows = [];
    for (let d = 1; d <= days; d++) {
      const date = month + '-' + String(d).padStart(2, '0');
      const occ = occupiedOn(room.id, date);
      const arr = recordsForRoom(room.id).filter((r) => dateOnly(r.arrival_date) === date);
      const dep = recordsForRoom(room.id).filter((r) => r.status === 'out' && dateOnly(r.departure_date) === date);
      if (occ > maxOcc) { maxOcc = occ; maxDay = d; }
      totalArrivals += arr.length;
      totalDepartures += dep.length;
      rows.push(`
        <tr>
          <td>${d}</td>
          <td>${occ}/${room.beds}</td>
          <td>${arr.length}</td>
          <td>${dep.length}</td>
        </tr>`);
    }

    const stats = `
      <div class="rr-kpi-row">
        <div class="rr-kpi"><div class="rr-kpi-n">${maxOcc}/${room.beds}</div><div class="rr-kpi-l">Peak day ${maxDay ?? '—'}</div></div>
        <div class="rr-kpi"><div class="rr-kpi-n">${totalArrivals}</div><div class="rr-kpi-l">Arrivals</div></div>
        <div class="rr-kpi"><div class="rr-kpi-n">${totalDepartures}</div><div class="rr-kpi-l">Departures</div></div>
        <div class="rr-kpi"><div class="rr-kpi-n">${mattersForRoom(room.id).filter((m) => String(m.date).slice(0, 7) === month).length}</div><div class="rr-kpi-l">Matters</div></div>
      </div>`;

    return `
      ${stats}
      <div class="rr-sub"><b>${esc(room.name)} — ${monthLabel(month)}</b></div>
      <div class="rr-table-wrap">
        <table class="rr-table">
          <thead><tr><th>Day</th><th>Occupancy</th><th>Arrivals</th><th>Departures</th></tr></thead>
          <tbody>${rows.join('')}</tbody>
        </table>
      </div>`;
  }

  function bindMonthly(root) {
    const monthInput = $('#rrMonthKey', root);
    if (monthInput) monthInput.addEventListener('change', (e) => { state.monthKey = e.target.value; renderAll(); goTab('monthly'); });
    const roomInput = $('#rrMonthRoom', root);
    if (roomInput) roomInput.addEventListener('change', (e) => { state.monthRoom = e.target.value; renderAll(); goTab('monthly'); });
    const printBtn = $('#rrMonthlyPrint', root);
    if (printBtn) printBtn.addEventListener('click', () => window.print());
  }

  /* ── rich text helpers ─────────────────────────────────────────────── */
  function sanitizeHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = String(html ?? '');
    div.querySelectorAll('script,style,iframe,object,embed,link,meta,form').forEach((el) => el.remove());
    div.querySelectorAll('*').forEach((el) => {
      Array.from(el.attributes).forEach((attr) => {
        if (/^on/i.test(attr.name)) el.removeAttribute(attr.name);
        if ((attr.name === 'href' || attr.name === 'src') && /^\s*javascript:/i.test(attr.value)) el.removeAttribute(attr.name);
      });
    });
    return div.innerHTML;
  }

  function richDesc(description) {
    const raw = String(description ?? '').trim();
    if (!raw) return '';
    if (raw.indexOf('<') !== -1) return sanitizeHtml(raw);
    return raw.split(/\r?\n/).map((line) => esc(line) || '<br>').join('<br>');
  }

  function richEditorMarkup(value) {
    return `
      <div class="rr-richtext">
        <div class="rr-richtext-toolbar">
          <button type="button" data-rt-cmd="bold" title="Bold"><b>B</b></button>
          <button type="button" data-rt-cmd="italic" title="Italic"><i>I</i></button>
          <button type="button" data-rt-cmd="insertUnorderedList" title="Bullet list">• List</button>
          <button type="button" data-rt-cmd="insertOrderedList" title="Numbered list">1. List</button>
        </div>
        <div class="rr-richtext-area" contenteditable="true" data-placeholder="Describe the issue..."></div>
        <textarea name="description" hidden>${esc(value || '')}</textarea>
      </div>`;
  }

  function initRichText(container) {
    $$('.rr-richtext', container).forEach((box) => {
      if (box.dataset.rtInit) return;
      box.dataset.rtInit = '1';
      const area = $('.rr-richtext-area', box);
      const input = $('textarea[name="description"]', box);
      if (!area || !input) return;

      if (input.value) {
        const plain = input.value.indexOf('<') === -1;
        area.innerHTML = plain ? esc(input.value).replace(/\r?\n/g, '<br>') : sanitizeHtml(input.value);
      }

      const sync = () => {
        input.value = area.textContent.trim() === '' ? '' : area.innerHTML;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      };
      area.addEventListener('input', sync);
      area.addEventListener('blur', sync);

      area.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
          e.preventDefault();
          document.execCommand('insertText', false, '    ');
        }
      });

      area.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, text);
      });

      $$('[data-rt-cmd]', box).forEach((btn) => {
        btn.addEventListener('mousedown', (e) => e.preventDefault());
        btn.addEventListener('click', () => {
          area.focus();
          document.execCommand(btn.dataset.rtCmd, false, null);
          sync();
        });
      });

      sync();
    });
  }

  /* ═════════════════════════ MATTERS ARISING ════════════════════════════ */
  function photoStrip(photos) {
    if (!Array.isArray(photos) || photos.length === 0) return '';
    return `<div class="rr-matter-photos">${photos.map((p) =>
      `<a class="rr-matter-photo" href="${esc(p.url)}" target="_blank" rel="noopener" title="Open photo"><img src="${esc(p.url)}" alt="Matter photo" loading="lazy"></a>`
    ).join('')}</div>`;
  }

  function renderMatters() {
    const catOptions = state.categories.map((c) => `<option value="${esc(c)}">${esc(c)}</option>`).join('');
    const roomOptions = state.rooms.map((r) => `<option value="${r.id}">${esc(roomLabel(r))}</option>`).join('');
    const attendantRoomId = state.user?.roomId;

    const filter = state.mattersFilter;
    let filtered = state.matters.slice();
    if (filter === 'open') filtered = filtered.filter((m) => m.status === 'open');
    if (filter === 'resolved') filtered = filtered.filter((m) => m.status === 'resolved');

    const form = `
      <h2>Matters Arising</h2>
      <div class="rr-sub">Log operational issues affecting the running rooms.</div>
      <div class="rr-form-card">
        <form id="rrMatterForm" class="rr-form-grid">
          <div class="rr-field"><label>Date *</label><input type="date" name="date" required value="${todayStr()}"></div>
          <div class="rr-field"><label>Room *</label><select name="room_id" required>${roomOptions}</select></div>
          <div class="rr-field"><label>Category *</label><select name="category" required>${catOptions}</select></div>
          <div class="rr-field"><label>Reported By</label><input type="text" name="reported_by" maxlength="128" value="${esc(state.user?.name || '')}"></div>
          <div class="rr-field" style="grid-column:1/-1"><label>Description *</label>${richEditorMarkup('')}</div>
          <div class="rr-field" style="grid-column:1/-1"><label>Photos</label><input type="file" name="photos[]" accept="image/*" multiple></div>
          <div class="rr-form-actions" style="grid-column:1/-1">
            <button type="submit" class="rr-btn">Log matter</button>
          </div>
        </form>
      </div>`;

    const toolbar = `
      <div class="rr-toolbar">
        <div class="rr-field"><label>Filter</label>
          <select id="rrMatterFilter">
            <option value="all"${filter === 'all' ? ' selected' : ''}>All</option>
            <option value="open"${filter === 'open' ? ' selected' : ''}>Open</option>
            <option value="resolved"${filter === 'resolved' ? ' selected' : ''}>Resolved</option>
          </select>
        </div>
      </div>`;

    const cards = filtered.map((m) => {
      const canEdit = !BOOT.isAttendant || Number(m.room_id) === Number(attendantRoomId);
      const roomSel = BOOT.isAdmin
        ? `<select data-m-room="${m.id}">${state.rooms.map((r) => `<option value="${r.id}"${Number(r.id) === Number(m.room_id) ? ' selected' : ''}>${esc(roomLabel(r))}</option>`).join('')}</select>`
        : `<b>${esc(roomName(m.room_id))}</b>`;
      return `
        <div class="rr-matter-card" data-matter="${m.id}">
          <div class="rr-matter-head">
            <span class="rr-matter-date">${esc(m.ticket_no || '—')} · ${fmtDate(m.date)}</span>
            <span class="rr-badge ${m.status}">${m.status}</span>
          </div>
          <div class="rr-matter-cat">${esc(m.category)} · ${roomSel}</div>
          <div class="rr-matter-desc">${richDesc(m.description)}</div>
          ${photoStrip(m.photos)}
          <div class="rr-matter-foot">
            <span>Reported by ${esc(m.reported_by || '—')}</span>
            <div class="rr-matter-actions">
              ${canEdit ? `<button class="rr-btn ghost sm" data-m-toggle="${m.id}">${m.status === 'open' ? 'Resolve' : 'Reopen'}</button>` : ''}
              ${canEdit ? `<button class="rr-btn ghost sm" data-m-edit="${m.id}">Edit</button>` : ''}
              ${canEdit ? `<button class="rr-btn ghost sm" data-m-del="${m.id}">Delete</button>` : ''}
            </div>
          </div>
        </div>`;
    }).join('');

    return form + toolbar + (cards ? `<div class="rr-matter-grid">${cards}</div>` : '<div class="rr-empty">No matters logged.</div>');
  }

  function bindMatters(root) {
    initRichText(root);
    const form = $('#rrMatterForm', root);
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = new FormData(form);
        try {
          const created = await api('/running-rooms/api/matters', 'POST', payload);
          toast('Matter logged as ' + (created.ticket_no || 'ticket').toUpperCase() + '.');
          await loadData();
          renderAll();
          goTab('matters');
        } catch (err) { toast(err.message, true); }
      });
    }

    const filterEl = $('#rrMatterFilter', root);
    if (filterEl) filterEl.addEventListener('change', (e) => { state.mattersFilter = e.target.value; renderAll(); goTab('matters'); });

    $$('[data-m-toggle]', root).forEach((btn) => {
      btn.addEventListener('click', async () => {
        const m = state.matters.find((x) => x.id == btn.dataset.mToggle);
        if (!m) return;
        try {
          await api('/running-rooms/api/matters/' + m.id, 'PUT', {
            room_id: m.room_id, date: String(m.date).slice(0, 10), category: m.category,
            description: m.description, reported_by: m.reported_by || '',
            status: m.status === 'open' ? 'resolved' : 'open',
          });
          toast('Matter updated.');
          await loadData(); renderAll(); goTab('matters');
        } catch (err) { toast(err.message, true); }
      });
    });

    $$('[data-m-del]', root).forEach((btn) => {
      btn.addEventListener('click', async () => {
        if (!confirm('Delete this matter?')) return;
        try {
          await api('/running-rooms/api/matters/' + btn.dataset.mDel, 'DELETE');
          toast('Matter deleted.');
          await loadData(); renderAll(); goTab('matters');
        } catch (err) { toast(err.message, true); }
      });
    });

    $$('[data-m-edit]', root).forEach((btn) => {
      btn.addEventListener('click', () => {
        const m = state.matters.find((x) => x.id == btn.dataset.mEdit);
        if (!m) return;
        state.editingMatter = m.id;
        renderAll();
        goTab('matters');
      });
    });

    // room reassign (admin only)
    $$('[data-m-room]', root).forEach((sel) => {
      sel.addEventListener('change', async () => {
        const m = state.matters.find((x) => x.id == sel.dataset.mRoom);
        if (!m) return;
        try {
          await api('/running-rooms/api/matters/' + m.id, 'PUT', {
            room_id: sel.value, date: String(m.date).slice(0, 10), category: m.category,
            description: m.description, reported_by: m.reported_by || '',
            status: m.status,
          });
          toast('Matter reassigned.');
          await loadData(); renderAll(); goTab('matters');
        } catch (err) { toast(err.message, true); }
      });
    });

    // inline edit form
    if (state.editingMatter) {
      const m = state.matters.find((x) => x.id == state.editingMatter);
      if (m) {
        const card = $(`[data-matter="${m.id}"]`, root);
        if (card) {
          const catOptions = state.categories.map((c) => `<option value="${esc(c)}"${c === m.category ? ' selected' : ''}>${esc(c)}</option>`).join('');
          const roomOptions = state.rooms.map((r) => `<option value="${r.id}"${Number(r.id) === Number(m.room_id) ? ' selected' : ''}>${esc(roomLabel(r))}</option>`).join('');
          card.innerHTML = `
            <form class="rr-form-grid" data-matter-edit="${m.id}">
              <div class="rr-field"><label>Ticket</label><input type="text" value="${esc(m.ticket_no || '')}" readonly></div>
              <div class="rr-field"><label>Date</label><input type="date" name="date" value="${String(m.date).slice(0, 10)}"></div>
              <div class="rr-field"><label>Room</label><select name="room_id">${roomOptions}</select></div>
              <div class="rr-field"><label>Category</label><select name="category">${catOptions}</select></div>
              <div class="rr-field"><label>Reported By</label><input type="text" name="reported_by" value="${esc(m.reported_by || '')}"></div>
              <div class="rr-field" style="grid-column:1/-1"><label>Description</label>${richEditorMarkup(m.description)}</div>
              ${Array.isArray(m.photos) && m.photos.length
                ? `<div class="rr-field" style="grid-column:1/-1"><label>Existing photos (tick to remove)</label><div class="rr-matter-photos">${m.photos.map((p) => `
                    <label class="rr-matter-photo rr-photo-remove"><img src="${esc(p.url)}" alt="" loading="lazy"><span><input type="checkbox" name="photo_deletes[]" value="${p.id}"> remove</span></label>`).join('')}</div></div>`
                : ''}
              <div class="rr-field" style="grid-column:1/-1"><label>Add photos</label><input type="file" name="photos[]" accept="image/*" multiple></div>
              <div class="rr-form-actions" style="grid-column:1/-1">
                <button type="submit" class="rr-btn green">Save</button>
                <button type="button" class="rr-btn ghost" data-m-cancel>Cancel</button>
              </div>
            </form>`;
          initRichText(root);
          $('form[data-matter-edit]', root).addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const payload = new FormData(ev.target);
            payload.append('status', m.status);
            try {
              await api('/running-rooms/api/matters/' + m.id, 'PUT', payload);
              state.editingMatter = null;
              toast('Matter saved.');
              await loadData(); renderAll(); goTab('matters');
            } catch (err) { toast(err.message, true); }
          });
          $('[data-m-cancel]', root).addEventListener('click', () => {
            state.editingMatter = null;
            renderAll(); goTab('matters');
          });
        }
      }
    }
  }

  /* ═══════════════════════ CHALLENGES SUMMARY (admin) ══════════════════ */
  function renderChallenges() {
    const f = state.challengeFilters;
    let filtered = state.matters.slice();
    if (f.room !== 'all') filtered = filtered.filter((m) => Number(m.room_id) === Number(f.room));
    if (f.status !== 'all') filtered = filtered.filter((m) => m.status === f.status);
    if (f.category !== 'all') filtered = filtered.filter((m) => m.category === f.category);
      if (f.from) filtered = filtered.filter((m) => dateOnly(m.date) >= f.from);
      if (f.to) filtered = filtered.filter((m) => dateOnly(m.date) <= f.to);

    const byRoom = {};
    filtered.forEach((m) => {
      const key = roomName(m.room_id);
      byRoom[key] = byRoom[key] || { total: 0, open: 0, resolved: 0 };
      byRoom[key].total++;
      byRoom[key][m.status]++;
    });
    const roomRows = Object.entries(byRoom).map(([name, s]) => {
      const rate = s.total ? Math.round((s.resolved / s.total) * 100) : 0;
      return `<tr><td>${esc(name)}</td><td>${s.total}</td><td>${s.open}</td><td>${s.resolved}</td><td>${rate}%</td></tr>`;
    }).join('');

    const byCat = {};
    filtered.forEach((m) => { byCat[m.category] = (byCat[m.category] || 0) + 1; });
    const catRows = Object.entries(byCat).map(([cat, n]) => `<tr><td>${esc(cat)}</td><td>${n}</td></tr>`).join('');

    const roomOptions = `<option value="all">All rooms</option>` + state.rooms.map((r) => `<option value="${r.id}"${Number(r.id) === Number(f.room) ? ' selected' : ''}>${esc(roomLabel(r))}</option>`).join('');
    const catOptions = `<option value="all">All categories</option>` + state.categories.map((c) => `<option value="${esc(c)}"${f.category === c ? ' selected' : ''}>${esc(c)}</option>`).join('');

    const list = filtered.map((m) => `
      <div class="rr-matter-card">
        <div class="rr-matter-head"><span class="rr-matter-date">${esc(m.ticket_no || '—')} · ${fmtDate(m.date)}</span><span class="rr-badge ${m.status}">${m.status}</span></div>
        <div class="rr-matter-cat">${esc(m.category)} · <b>${esc(roomName(m.room_id))}</b></div>
        <div class="rr-matter-desc">${richDesc(m.description)}</div>
        ${photoStrip(m.photos)}
        <div class="rr-matter-foot"><span>Reported by ${esc(m.reported_by || '—')}</span></div>
      </div>`).join('');

    return `
      <h2>Challenges Summary</h2>
      <div class="rr-sub">Aggregate matters statistics by room and category.</div>
      <div class="rr-toolbar rr-print-hide">
        <div class="rr-field"><label>Room</label><select id="rrChRoom">${roomOptions}</select></div>
        <div class="rr-field"><label>Status</label><select id="rrChStatus"><option value="all"${f.status === 'all' ? ' selected' : ''}>All</option><option value="open"${f.status === 'open' ? ' selected' : ''}>Open</option><option value="resolved"${f.status === 'resolved' ? ' selected' : ''}>Resolved</option></select></div>
        <div class="rr-field"><label>Category</label><select id="rrChCat">${catOptions}</select></div>
        <div class="rr-field"><label>From</label><input type="date" id="rrChFrom" value="${f.from}"></div>
        <div class="rr-field"><label>To</label><input type="date" id="rrChTo" value="${f.to}"></div>
        <button class="rr-btn" id="rrChPrint">Print</button>
      </div>
      <div class="rr-print-area">
        <div class="rr-sub" style="margin-top:14px"><b>By room</b></div>
        <div class="rr-table-wrap">
          <table class="rr-table"><thead><tr><th>Room</th><th>Total</th><th>Open</th><th>Resolved</th><th>Resolution rate</th></tr></thead>
          <tbody>${roomRows || '<tr><td colspan="5"><div class="rr-empty">No matching matters.</div></td></tr>'}</tbody></table>
        </div>
        <div class="rr-sub" style="margin-top:14px"><b>By category</b></div>
        <div class="rr-table-wrap">
          <table class="rr-table"><thead><tr><th>Category</th><th>Count</th></tr></thead>
          <tbody>${catRows || '<tr><td colspan="2"><div class="rr-empty">No matching matters.</div></td></tr>'}</tbody></table>
        </div>
        <div class="rr-sub" style="margin-top:14px"><b>Matters list</b></div>
        ${list ? `<div class="rr-matter-grid">${list}</div>` : '<div class="rr-empty">No matching matters.</div>'}
      </div>`;
  }

  function bindChallenges(root) {
    const bind = (sel, key) => {
      const el = $(sel, root);
      if (el) el.addEventListener('change', (e) => { state.challengeFilters[key] = e.target.value; renderAll(); goTab('challenges'); });
    };
    bind('#rrChRoom', 'room');
    bind('#rrChStatus', 'status');
    bind('#rrChCat', 'category');
    bind('#rrChFrom', 'from');
    bind('#rrChTo', 'to');
    const printBtn = $('#rrChPrint', root);
    if (printBtn) printBtn.addEventListener('click', () => window.print());
  }

  /* ═════════════════════════ SETTINGS (admin) ═══════════════════════════ */
  function renderSettings() {
    const desigList = (state.designations || []).join('\n');
    const catList = (state.categories || []).join('\n');
    const rows = state.rooms.map((room) => {
      const occ = currentlyIn(room.id).length;
      return `
        <div class="rr-set-row" data-room="${room.id}">
          <div>
            <div class="rr-set-name">${esc(room.name)}</div>
            <div class="rr-set-note">${occ} currently checked in</div>
          </div>
          <div class="rr-field"><label>Beds</label><input type="number" min="1" value="${room.beds}" data-beds></div>
          <div style="display:flex;gap:6px">
            <button class="rr-btn sm" data-save-beds="${room.id}">Save beds</button>
          </div>
        </div>`;
    }).join('');

    return `
      <h2>Settings</h2>
      <div class="rr-sub">Edit register options and bed capacity per room.</div>
      <div class="rr-set-options" style="background:#fff;border:1px solid var(--border);border-radius:var(--rl);padding:14px 16px;margin-bottom:16px">
        <div class="rr-set-name">Register options</div>
        <div class="rr-set-note">One value per line. Saved to the database and applied immediately to the register, matters and reports.</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:10px">
          <div class="rr-field"><label>Designations</label><textarea data-opt-desig rows="8" placeholder="One per line">${esc(desigList)}</textarea></div>
          <div class="rr-field"><label>Matter categories</label><textarea data-opt-cat rows="8" placeholder="One per line">${esc(catList)}</textarea></div>
        </div>
        <div style="margin-top:10px"><button class="rr-btn sm" data-save-options>Save options</button></div>
      </div>
      ${state.rooms.length ? rows : '<div class="rr-empty">No rooms available.</div>'}`;
  }

  function bindSettings(root) {
    const optBtn = $('[data-save-options]', root);
    if (optBtn) {
      optBtn.addEventListener('click', async () => {
        const designations = $('[data-opt-desig]', root).value.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
        const categories = $('[data-opt-cat]', root).value.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
        if (!designations.length || !categories.length) { toast('Both lists need at least one value.', true); return; }
        try {
          await api('/running-rooms/api/settings/options', 'POST', { designations, categories });
          toast('Register options saved.');
          await loadData(); renderAll(); goTab('settings');
        } catch (err) { toast(err.message, true); }
      });
    }

    $$('[data-save-beds]', root).forEach((btn) => {
      btn.addEventListener('click', async () => {
        const row = btn.closest('.rr-set-row');
        const beds = $('[data-beds]', row).value;
        try {
          const res = await api('/running-rooms/api/settings/beds', 'POST', { room_id: btn.dataset.saveBeds, beds: Number(beds) });
          toast(res.warning || 'Bed capacity updated.', !!res.warning);
          await loadData(); renderAll(); goTab('settings');
        } catch (err) { toast(err.message, true); }
      });
    });
  }

  /* ── render dispatcher ────────────────────────────────────────────────── */
  const RENDERERS = {
    dashboard: { render: renderDashboard, bind: null },
    checkin: { render: renderCheckIn, bind: bindCheckIn },
    daily: { render: renderDaily, bind: bindDaily },
    monthly: { render: renderMonthly, bind: bindMonthly },
    matters: { render: renderMatters, bind: bindMatters },
  };
  if (BOOT.isAdmin) {
    RENDERERS.challenges = { render: renderChallenges, bind: bindChallenges };
    RENDERERS.settings = { render: renderSettings, bind: bindSettings };
  }

  function renderPanel(tab) {
    const main = document.getElementById('rr-main');
    const spec = RENDERERS[tab];
    if (!spec) return;
    const panel = document.createElement('div');
    panel.className = 'rr-panel active';
    panel.id = 'rr-panel-' + tab;
    panel.innerHTML = spec.render();
    main.appendChild(panel);
    if (spec.bind) spec.bind(panel);
  }

  function renderAll() {
    const main = document.getElementById('rr-main');
    main.innerHTML = '';
    const current = state.tab;
    renderPanel(current);
    $$('.rr-sb-item').forEach((el) => el.classList.toggle('active', el.dataset.rrTab === current));
  }

  /* ── init ─────────────────────────────────────────────────────────────── */
  async function init() {
    $$('.rr-sb-item').forEach((btn) => {
      btn.addEventListener('click', () => goTab(btn.dataset.rrTab));
    });

    const toastEl = document.createElement('div');
    toastEl.id = 'rrToast';
    toastEl.className = 'rr-toast';
    document.body.appendChild(toastEl);

    try {
      await loadData();
    } catch (err) {
      toast('Failed to load register data: ' + err.message, true);
      document.getElementById('rr-main').innerHTML = `<div class="rr-empty">Failed to load register data. ${esc(err.message)}</div>`;
      return;
    }

    renderAll();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
