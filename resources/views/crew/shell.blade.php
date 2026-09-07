<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }} - Crew</title>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        @if (auth()->user())
            @php
                $authUser = auth()->user();
                $currentUserPayload = [
                    'username' => $authUser->username,
                    'depot' => $authUser->depot_code ?? 'HQ',
                    'name' => $authUser->name ?? $authUser->email ?? $authUser->username,
                    'isHQ' => (bool) $authUser->is_hq || (bool) $authUser->is_super_admin || $authUser->role_code === 'hq_admin' || ($authUser->depot_code === 'HQ'),
                    'isSuperAdmin' => (bool) $authUser->is_super_admin || $authUser->role_code === 'super_admin',
                    'role' => $authUser->role_code ?? '',
                    'canRunningRooms' => $authUser->canAccessRunningRooms(),
                    'canAdmin' => $authUser->isGlobalAccess(),
                ];
            @endphp
            <script>
                window.__CURRENT_USER__ = {!! json_encode($currentUserPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
            </script>
        @endif
        <script>window.__INITIAL_PAGE__ = '{{ $initialPage ?? 'dashboard' }}';</script>
        @php
            $manifest = public_path('build/manifest.json');
        @endphp
        @if (file_exists($manifest))
            @php
                $manifestData = json_decode(file_get_contents($manifest), true);
                $js = $manifestData['resources/js/app.js']['file'] ?? null;
            @endphp
            @if ($js)
                <script type="module" src="{{ asset('build/' . $js) }}"></script>
            @endif
        @else
            @vite(['resources/js/app.js'])
        @endif
        <script src="{{ asset('js/notification-bell.js') }}"></script>
    </head>
    <body>
        <div id="syncBadge" class="hide"><div class="sd sd-ok" id="syncDot"></div><span id="syncLabel">Connected</span></div>

        <div id="app">
            <div id="topBar">
                <div class="tb-mark"><img src="{{ asset('assets/logo.png') }}" alt="KR Logo"></div>
                <span class="tb-title">KR Crew System</span>
                <div class="tb-sep"></div>
                <span class="tb-badge" id="tbBadge"></span>
                <div class="tb-live"><div class="tbl-dot" id="tbLiveDot"></div><span class="tbl-txt" id="tbLiveTxt">Live</span></div>
                <div class="tb-right">
                    <div class="kr-bell" id="krBell">
                        <button type="button" class="kr-bell-btn" id="krBellBtn" aria-label="Notifications">
                            <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                            <span class="kr-bell-count" id="krBellCount" hidden>0</span>
                        </button>
                        <div class="kr-bell-panel" id="krBellPanel" hidden>
                            <div class="kr-bell-head">Notifications</div>
                            <div class="kr-bell-list" id="krBellList"></div>
                            <button type="button" class="kr-bell-mark" id="krBellMark">Mark all as read</button>
                        </div>
                    </div>
                    <span class="tb-user" id="tbUser"></span>
                    <span class="tb-clock" id="tbClock"></span>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn-out">Sign out</button>
                    </form>
                </div>
            </div>
            <div id="shell">
                <div id="sidebar">
                    <div class="sb-group">
                        <a href="/" class="sb-item"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Home</a>
                        <div class="sb-sec">Main Depot</div>
                        <a href="/crew-dashboard" class="sb-item active" onclick="if(typeof goPage==='function'){goPage('dashboard');return false;}" id="sb-dashboard"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard</a>
                        <a href="/crew-roster" class="sb-item" onclick="if(typeof goPage==='function'){goPage('roster');return false;}" id="sb-roster"><svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 3.13a4 4 0 010 7.75M21 21v-2a4 4 0 00-3-3.85"/></svg>Crew Roster</a>
                        <a href="/crew-rest" class="sb-item" onclick="if(typeof goPage==='function'){goPage('rest');return false;}" id="sb-rest"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Rest Countdowns</a>
                        <a href="/crew-monthly" class="sb-item" onclick="if(typeof goPage==='function'){goPage('monthly');return false;}" id="sb-monthly"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9M16 3v2M8 3v2"/></svg>Monthly View</a>
                        <a href="/crew-reports" class="sb-item" onclick="if(typeof goPage==='function'){goPage('reports');return false;}" id="sb-reports"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>Reports</a>
                        <div id="depotSection" style="display:none">
                            <div class="sb-sec">Depots</div>
                            <div id="sbDepots"></div>
                        </div>
                    </div>
                    @if ($currentUserPayload['canRunningRooms'])
                        <div class="sb-group">
                            <div class="sb-sec">Running Rooms</div>
                            <div class="sb-item" onclick="window.location.href='/running-rooms/monthly'"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9M16 3v2M8 3v2"/></svg>Monthly Report</div>
                            <div class="sb-item" onclick="window.location.href='/running-rooms/challenges'"><svg viewBox="0 0 24 24"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>Challenges Summary</div>
                            <div class="sb-item" onclick="window.location.href='/running-rooms/settings'"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>Settings</div>
                        </div>
                    @endif
                    @if ($currentUserPayload['canAdmin'])
                        <div class="sb-group" id="adminSection" style="display:none">
                            <div class="sb-sec">Admin</div>
                            <div class="sb-item" onclick="window.location.href='/admin'" id="sb-admin"><svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 9.7 8 10 4.6-.3 8-5 8-10V6z"/><path d="M9 12h6M12 9v6"/></svg>Admin Center</div>
                        </div>
                    @endif
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
            <div class="modal-box modal status-modal">
                <div class="modal-title" id="mTitle">Update crew status</div>
                <div class="modal-sub" id="mSub"></div>

                <div class="status-change-summary" id="statusChangeSummary"></div>

                <label>Status</label>
                <div class="status-picker" id="mStatusGrid"></div>
                <select id="mStatus" class="status-picker-select" onchange="onStatusChange()">
                    <option value="BK">BK - Booked</option>
                    <option value="SB">SB - Standby</option>
                    <option value="R">R - Resting</option>
                    <option value="L">L - On Leave</option>
                    <option value="SK">SK - Sick</option>
                    <option value="ABS">ABS - Absent</option>
                    <option value="T">T - Training</option>
                    <option value="NTB">NTB - Not to be Booked</option>
                    <option value="TO">TO - Trip Off</option>
                </select>
                <div id="statusHint" style="font-size:11px;color:#E53935;margin-top:6px;display:none"></div>

                <div id="trainTypeRow" class="modal-block">
                    <label>Train type</label>
                    <select id="mTrainType" required>
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

                <div id="restHoursRow" class="modal-block">
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
                    <button class="btn btn-primary" id="mSaveBtn" onclick="saveModal()">Save status</button>
                </div>
            </div>
        </div>

        <div class="modal-ov" id="crewModal">
            <div class="modal-box modal crew-modal">
                <div class="modal-title" id="crewModalTitle">Crew details</div>
                <div class="modal-sub" id="crewModalSub"></div>
                <div id="crewModalBody"></div>
                <div class="modal-btns" style="margin-top:14px">
                    <button class="btn btn-ghost" onclick="closeCrewDetails()">Close</button>
                    <button class="btn btn-primary" id="changeStatusBtn" onclick="changeStatusFromDetails()">Change status</button>
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
                    <label class="modal" style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:3px">Shift</label>
                    <select id="addShift" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r);font-size:12px;outline:none;margin-bottom:8px"></select>
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
    </body>
</html>
