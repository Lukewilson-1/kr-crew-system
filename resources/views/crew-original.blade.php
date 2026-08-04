@extends('layouts.app')

@section('content')
<div id="syncBadge" class="hide"><div class="sd sd-ok" id="syncDot"></div><span id="syncLabel">Connected</span></div>

<div id="loginPage">
<div class="lcard">
  <div class="llogo">
    <div class="llogo-mark"><img src="{{ asset('assets/logo.png') }}" alt="KR Logo"></div>
    <div class="llogo-text"><strong>Kenya Railways Corporation</strong><span>Crew Booking System - Live</span></div>
  </div>
  <div class="lh1">Sign in</div>
  <div class="lsub">Access your depot's crew booking board</div>
  <div class="lerr" id="loginErr"></div>
  <div class="frow"><label>Username</label><input type="text" id="lUser" placeholder="e.g. hq_admin" autocomplete="username"></div>
  <div class="frow"><label>Password</label><input type="password" id="lPass" placeholder="Password" autocomplete="current-password"></div>
  <button class="btn-login" onclick="doLogin()">Sign in →</button>
  <div class="lhint" id="loginHint"></div>
</div>
</div>

<div id="app">
<div id="topBar">
  <div class="tb-mark"><img src="{{ asset('assets/logo.png') }}" alt="KR Logo"></div>
  <span class="tb-title">KR Crew System</span>
  <div class="tb-sep"></div>
  <span class="tb-badge" id="tbBadge"></span>
  <div class="tb-live"><div class="tbl-dot" id="tbLiveDot"></div><span class="tbl-txt" id="tbLiveTxt">Live</span></div>
  <div class="tb-right">
    <span class="tb-user" id="tbUser"></span>
    <span class="tb-clock" id="tbClock"></span>
    <button class="btn-out" onclick="doLogout()">Sign out</button>
  </div>
</div>
<div id="shell">
<div id="sidebar">
  <div class="sb-group">
    <div class="sb-sec">Main Depot</div>
    <div class="sb-item active" onclick="goPage('dashboard')" id="sb-dashboard"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard</div>
    <div class="sb-item" onclick="goPage('roster')" id="sb-roster"><svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 3.13a4 4 0 010 7.75M21 21v-2a4 4 0 00-3-3.85"/></svg>Crew Roster</div>
    <div class="sb-item" onclick="goPage('rest')" id="sb-rest"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Rest Countdowns</div>
    <div class="sb-item" onclick="goPage('monthly')" id="sb-monthly"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9M16 3v2M8 3v2"/></svg>Monthly View</div>
    <div class="sb-item" onclick="goPage('reports')" id="sb-reports"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>Reports</div>
    <div id="depotSection" style="display:none">
      <div class="sb-sec">Depots</div>
      <div id="sbDepots"></div>
    </div>
  </div>
  <div class="sb-group" id="adminSection" style="display:none">
    <div class="sb-sec">Admin</div>
    <div class="sb-item" onclick="window.location.href='/admin'" id="sb-admin"><svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 9.7 8 10 4.6-.3 8-5 8-10V6z"/><path d="M9 12h6M12 9v6"/></svg>Admin Center</div>
  </div>
  <div class="sb-bottom">v4.0 Live · Kenya Railways</div>
</div>
<div id="main">
  <div id="phdr">
    <div><div class="ph-title" id="phTitle">Dashboard</div><div class="ph-sub" id="phSub"></div></div>
    <div class="ph-actions" id="phActions"></div>
  </div>
  <div id="pbody"></div>
</div>
</div>

<template id="adminPanelTpl">
  <div style="display:grid;gap:14px">
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
      <div style="font-size:14px;font-weight:800;margin-bottom:4px">MySQL maintenance</div>
      <div style="font-size:12px;color:var(--text2);margin-bottom:10px">Seed or refresh the data collections that drive the crew app.</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-primary" onclick="seedBackend()">Bootstrap superadmin</button>
        <button class="btn btn-ghost" onclick="reloadAdminData()">Reload admin data</button>
      </div>
    </div>
    <div class="admin-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
      <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
        <div style="font-size:13px;font-weight:800;margin-bottom:8px">Depots</div>
        <div id="adminDepotsList"></div>
      </div>
      <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
        <div style="font-size:13px;font-weight:800;margin-bottom:8px">Designations</div>
        <div id="adminDesignationsList"></div>
      </div>
    </div>
  </div>
</template>

<div id="logBar" style="background:#0F172A;padding:5px 18px;display:flex;align-items:center;gap:7px;flex-shrink:0">
  <div style="width:6px;height:6px;border-radius:50%;background:#69F0AE;animation:blink 2s infinite;flex-shrink:0"></div>
  <span id="logText" style="font-size:11px;color:rgba(255,255,255,.5);font-family:var(--mono)">System ready.</span>
</div>
</div>

<div class="modal-ov" id="modal">
<div class="modal-box modal">
  <div class="modal-title" id="mTitle">Update status</div>
  <div class="modal-sub" id="mSub"></div>
  <label>Status</label>
  <select id="mStatus" onchange="onStatusChange()">
    <option value="BK">BK - Booked</option>
    <option value="SB">SB - Standby</option>
    <option value="R">R - Resting</option>
    <option value="L">L - On Leave</option>
    <option value="SK">SK - Sick</option>
    <option value="T">T - Training</option>
    <option value="NTB">NTB - Not to be Booked</option>
    <option value="TO">TO - Trip Off</option>
  </select>
  <div id="statusHint" style="font-size:11px;color:#E53935;margin-top:6px;display:none"></div>
  <div id="trainTypeRow">
    <label>Train type</label>
    <select id="mTrainType">
      <option value="">- Select train type -</option>
      <option value="Freight">Freight</option>
      <option value="Commuter">Commuter</option>
      <option value="Passenger">Passenger</option>
      <option value="Engineering">Engineering Train</option>
      <option value="Shunting">Shunting</option>
    </select>
    <label style="margin-top:8px">Booked departure time (HH:MM)</label>
    <input type="time" id="mBookTime" value="">
    <div style="font-size:11px;color:var(--text2);margin-top:3px">Time the crew is booked to operate the train.</div>
  </div>
  <div id="restHoursRow">
    <label>Rest started at (HH:MM)</label>
    <input type="time" id="mRestStart" value="">
    <div style="font-size:11px;color:var(--text2);margin-top:3px" id="restDepotInfo"></div>
  </div>
  <div id="restLocationRow" style="display:none">
    <label>Rest location</label>
    <select id="mRestLocation" onchange="onRestLocationChange()">
      <option value="home">Home depot</option>
      <option value="away">Away depot</option>
    </select>
    <div id="awayDepotRow" style="margin-top:8px;display:none">
      <label>Select away depot</label>
      <select id="mAwayDepot"></select>
    </div>
    <div style="font-size:11px;color:var(--text2);margin-top:3px" id="restLocationHint"></div>
  </div>
  <div class="modal-grid">
    <div>
      <label>Route / Assignment</label>
      <input type="text" id="mRoute" placeholder="e.g. Changamwe–Mtito Andei">
    </div>
    <div>
      <label>Staff Number</label>
      <input type="text" id="mStaffNumber" placeholder="e.g. STAFF-001">
    </div>
    <div>
      <label>Shift</label>
      <select id="mShift"></select>
    </div>
  </div>
  <label>NTB reason / Notes (optional)</label>
  <textarea id="mNotes" placeholder="Reason for NTB, or any other notes…"></textarea>
  <div class="modal-btns">
    <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
    <button class="btn btn-danger" id="mRemoveBtn" onclick="confirmRemoveCrew()" style="display:none;background:#FFEBEE;color:#B71C1C;border:1px solid #EF9A9A">🗑 Remove crew</button>
    <button class="btn btn-primary" id="mSaveBtn" onclick="saveModal()">Save</button>
  </div>
</div>
</div>

<div class="modal-ov" id="addModal">
<div class="modal-box modal">
  <div class="modal-title">Add crew members</div>
  <div class="modal-sub" id="addModalSub">Add one crew member or paste a list</div>
  <div id="addTabs" style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:12px">
    <button onclick="switchAddTab('single')" id="tab-single" style="padding:7px 14px;font-size:12px;font-weight:700;border:none;border-bottom:2px solid var(--kr-red);background:transparent;color:var(--kr-red);cursor:pointer">Single</button>
    <button onclick="switchAddTab('bulk')" id="tab-bulk" style="padding:7px 14px;font-size:12px;font-weight:700;border:none;border-bottom:2px solid transparent;background:transparent;color:var(--text2);cursor:pointer">Bulk (paste list)</button>
  </div>
  <div id="addSingle">
    <label class="modal" style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:3px">Depot</label>
    <select id="addDepot" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;outline:none;margin-bottom:8px"></select>
    <label class="modal" style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:3px">Full Name *</label>
    <input type="text" id="addName" placeholder="e.g. John Kamau Njoroge" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;outline:none;margin-bottom:8px">
    <label class="modal" style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:3px">Staff Number</label>
    <input type="text" id="addStaffNumber" placeholder="e.g. 0001" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;outline:none;margin-bottom:8px">
    <label class="modal" style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:3px">Designation *</label>
    <select id="addGrade" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;outline:none;margin-bottom:8px"></select>
    <label class="modal" style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:3px">Route / Assignment</label>
    <input type="text" id="addRoute" placeholder="e.g. CGA-MTT" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;outline:none;margin-bottom:8px">
    <label class="modal" style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:3px">Initial Status</label>
    <select id="addStatus" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;outline:none"></select>
  </div>
  <div id="addBulk" style="display:none">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
      <button class="btn btn-ghost btn-sm" onclick="downloadCrewUploadTemplate()">Download CSV template</button>
    </div>
    <textarea class="bulk-area" id="bulkText" placeholder="Paste CSV rows here after downloading the template. The first row can be the header row."></textarea>
    <div class="bulk-hint">Use the CSV template, fill it, then paste the rows here. Name is required; the remaining columns will be imported when present.</div>
  </div>
  <div class="modal-btns" style="margin-top:14px">
    <button class="btn btn-ghost" onclick="closeAddModal()">Cancel</button>
    <button class="btn btn-green" onclick="saveAddCrew()" id="addSaveBtn">Add crew member</button>
  </div>
</div>
</div>
@endsection
